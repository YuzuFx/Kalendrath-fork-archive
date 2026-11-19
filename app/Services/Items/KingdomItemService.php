<?php

namespace OGame\Services\Items;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use OGame\Enums\ItemOperationType;
use OGame\Enums\ItemSourceType;
use OGame\Enums\KingdomItemState;
use OGame\Exceptions\ItemRuleException;
use OGame\Models\ItemDefinition;
use OGame\Models\ItemOperation;
use OGame\Models\KingdomItem;

/**
 * Items owned by a kingdom: creation (loot, admin), discard, inventory.
 * Every change goes through the item journal (ItemLedger) and is idempotent.
 */
class KingdomItemService
{
    public function __construct(private readonly ItemLedger $ledger)
    {
    }

    /**
     * Creates one item in a kingdom's bag (state available). Replaying the
     * same idempotency key returns the item created the first time.
     *
     * The bag cap is soft here: an item granted by loot is never lost, even
     * if the bag is full (decision of 2026-11-19, to be confirmed).
     *
     * @param array<string, mixed> $meta
     */
    public function grant(
        int $userId,
        ItemDefinition $definition,
        ItemSourceType $sourceType,
        string|null $sourceRef,
        string $idempotencyKey,
        ItemOperationType $operationType = ItemOperationType::ADMIN_GRANT,
        array $meta = [],
        int|null $actorUserId = null,
    ): KingdomItem {
        [$operation] = $this->ledger->run(
            $idempotencyKey,
            $operationType,
            $userId,
            ['definition' => $definition->code, 'source_type' => $sourceType->value, 'source_ref' => $sourceRef],
            function (ItemOperation $operation) use ($userId, $definition, $sourceType, $sourceRef): void {
                $this->createItem($operation, $userId, $definition, $sourceType, $sourceRef);
            },
            $actorUserId,
            $sourceType,
            $sourceRef,
            $meta,
        );

        return KingdomItem::where('created_operation_id', $operation->id)->firstOrFail();
    }

    /**
     * Creates the item and its movement inside an already opened operation.
     */
    public function createItem(ItemOperation $operation, int $userId, ItemDefinition $definition, ItemSourceType $sourceType, string|null $sourceRef): KingdomItem
    {
        $item = KingdomItem::create([
            'item_definition_id' => $definition->id,
            'definition_version' => $definition->version,
            'owner_user_id' => $userId,
            'state' => KingdomItemState::AVAILABLE,
            'source_type' => $sourceType,
            'source_ref' => $sourceRef,
            'origin_server_key' => (string) config('server.key'),
            'created_operation_id' => $operation->id,
            'acquired_at' => Date::now(),
        ]);
        $item->refresh();

        $this->ledger->move($operation, $item, []);

        return $item;
    }

    /**
     * Throws an item away: state destroyed (logical deletion, the row and its
     * journal stay). Only an available item of the kingdom can be discarded;
     * a worn or reserved item cannot. Idempotent by key.
     */
    public function discard(int $userId, KingdomItem $item, string $idempotencyKey, int|null $actorUserId = null): KingdomItem
    {
        $this->ledger->run(
            $idempotencyKey,
            ItemOperationType::DISCARD,
            $userId,
            ['item' => $item->uid],
            function (ItemOperation $operation) use ($userId, $item): void {
                /** @var KingdomItem $locked */
                $locked = KingdomItem::whereKey($item->id)->lockForUpdate()->firstOrFail();

                if ($locked->owner_user_id !== $userId) {
                    throw ItemRuleException::because('item_not_owned');
                }
                if ($locked->state !== KingdomItemState::AVAILABLE) {
                    throw ItemRuleException::because('item_not_available');
                }

                $from = $this->ledger->locationOf($locked);
                $affected = KingdomItem::whereKey($locked->id)
                    ->where('state', KingdomItemState::AVAILABLE->value)
                    ->update(['state' => KingdomItemState::DESTROYED->value]);
                if ($affected !== 1) {
                    throw ItemRuleException::because('item_not_available');
                }

                $locked->refresh();
                $this->ledger->move($operation, $locked, $from);
            },
            $actorUserId,
        );

        return $item->refresh();
    }

    /**
     * Items of a kingdom that still exist (bag + worn), with their definition.
     *
     * @return Collection<int, KingdomItem>
     */
    public function inventory(int $userId): Collection
    {
        return KingdomItem::where('owner_user_id', $userId)
            ->whereIn('state', [KingdomItemState::AVAILABLE->value, KingdomItemState::EQUIPPED->value, KingdomItemState::RESERVED->value])
            ->with('definition.effects')
            ->orderBy('id')
            ->get();
    }

    /**
     * Places taken in the bag: worn items do not count.
     */
    public function bagCount(int $userId): int
    {
        return KingdomItem::where('owner_user_id', $userId)
            ->whereIn('state', [KingdomItemState::AVAILABLE->value, KingdomItemState::RESERVED->value])
            ->count();
    }

    public function bagCapacity(): int
    {
        return (int) config('items.inventory.capacity');
    }

    /**
     * True when the bag is at or over its cap (it can be over after loot).
     */
    public function isBagFull(int $userId): bool
    {
        return $this->bagCount($userId) >= $this->bagCapacity();
    }
}
