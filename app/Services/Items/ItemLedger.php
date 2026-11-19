<?php

namespace OGame\Services\Items;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use OGame\Enums\ItemOperationType;
use OGame\Enums\ItemSourceType;
use OGame\Exceptions\ItemIdempotencyConflictException;
use OGame\Models\ItemMovement;
use OGame\Models\ItemOperation;
use OGame\Models\KingdomItem;

/**
 * Item journal: every inventory change is one operation with a unique
 * idempotency key, detailed by append-only movements. Replaying a key with
 * the same payload has no effect; with another payload it is a conflict.
 */
class ItemLedger
{
    public function __construct(private readonly KingdomLock $lock)
    {
    }

    /**
     * Runs $body once per idempotency key, inside a transaction.
     *
     * @param string $idempotencyKey
     * @param ItemOperationType $type
     * @param int $subjectUserId kingdom concerned (0 = system)
     * @param array<string, mixed> $payload what makes two requests "the same"
     * @param Closure(ItemOperation): void $body applied only on first run
     * @param array<string, mixed> $meta
     * @return array{0: ItemOperation, 1: bool} the operation, and whether it was a replay
     */
    public function run(
        string $idempotencyKey,
        ItemOperationType $type,
        int $subjectUserId,
        array $payload,
        Closure $body,
        int|null $actorUserId = null,
        ItemSourceType|null $sourceType = null,
        string|null $sourceRef = null,
        array $meta = [],
    ): array {
        $hash = $this->payloadHash($type, $subjectUserId, $payload);

        return DB::transaction(function () use ($idempotencyKey, $type, $subjectUserId, $hash, $body, $actorUserId, $sourceType, $sourceRef, $meta): array {
            $existing = $this->existing($idempotencyKey, $hash);
            if ($existing !== null) {
                return [$existing, true];
            }

            try {
                $operation = ItemOperation::create([
                    'idempotency_key' => $idempotencyKey,
                    'payload_hash' => $hash,
                    'type' => $type,
                    'subject_user_id' => $subjectUserId,
                    'actor_user_id' => $actorUserId,
                    'server_key' => (string) config('server.key'),
                    'source_type' => $sourceType?->value,
                    'source_ref' => $sourceRef,
                    'meta' => $meta === [] ? null : $meta,
                ]);
            } catch (UniqueConstraintViolationException) {
                // A concurrent transaction committed the same key first. Read
                // the winner with a CURRENT read: the plain read above uses the
                // transaction's snapshot (REPEATABLE READ), taken before that
                // commit, and would not see it.
                $existing = $this->existing($idempotencyKey, $hash, true);
                if ($existing === null) {
                    throw ItemIdempotencyConflictException::forKey($idempotencyKey);
                }

                return [$existing, true];
            }

            // The key is reserved (row inserted) BEFORE any roll or mutation:
            // a replay finds this operation and its recorded result, "no
            // loot" included. Then the kingdom lock (KingdomLock, lock order
            // documented there) serialises this change with the kingdom's
            // other inventory paths.
            $this->lock->acquire($subjectUserId);

            $body($operation);

            return [$operation, false];
        });
    }

    /**
     * Writes one movement line of an operation for a unique item.
     *
     * @param ItemOperation $operation
     * @param KingdomItem $item the item in its NEW state
     * @param array{owner?: int|null, state?: string|null, hero?: int|null, slot?: string|null} $from previous location
     */
    public function move(ItemOperation $operation, KingdomItem $item, array $from): ItemMovement
    {
        return ItemMovement::create([
            'item_operation_id' => $operation->id,
            'kingdom_item_id' => $item->id,
            'item_uid' => $item->uid,
            'item_definition_id' => $item->item_definition_id,
            'quantity' => 1,
            'from_owner_user_id' => $from['owner'] ?? null,
            'to_owner_user_id' => $item->owner_user_id,
            'from_state' => $from['state'] ?? null,
            'to_state' => $item->state->value,
            'from_hero_id' => $from['hero'] ?? null,
            'to_hero_id' => $item->holder_hero_id,
            'from_slot' => $from['slot'] ?? null,
            'to_slot' => $item->equipped_slot?->value,
        ]);
    }

    /**
     * Previous location of an item, for move().
     *
     * @return array{owner: int, state: string, hero: int|null, slot: string|null}
     */
    public function locationOf(KingdomItem $item): array
    {
        return [
            'owner' => $item->owner_user_id,
            'state' => $item->state->value,
            'hero' => $item->holder_hero_id,
            'slot' => $item->equipped_slot?->value,
        ];
    }

    private function existing(string $idempotencyKey, string $hash, bool $currentRead = false): ItemOperation|null
    {
        $query = ItemOperation::where('idempotency_key', $idempotencyKey);
        $existing = $currentRead ? $query->lockForUpdate()->first() : $query->first();
        if ($existing !== null && $existing->payload_hash !== $hash) {
            throw ItemIdempotencyConflictException::forKey($idempotencyKey);
        }

        return $existing;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function payloadHash(ItemOperationType $type, int $subjectUserId, array $payload): string
    {
        ksort($payload);

        return hash('sha256', (string) json_encode([$type->value, $subjectUserId, $payload]));
    }
}
