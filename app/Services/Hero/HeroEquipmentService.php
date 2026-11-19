<?php

namespace OGame\Services\Hero;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use OGame\Enums\EquipmentSlot;
use OGame\Enums\HeroRole;
use OGame\Enums\ItemKind;
use OGame\Enums\ItemOperationType;
use OGame\Enums\KingdomItemState;
use OGame\Exceptions\HeroRuleException;
use OGame\Models\Hero;
use OGame\Models\ItemOperation;
use OGame\Models\KingdomItem;
use OGame\Services\Items\ItemLedger;
use OGame\Services\Items\KingdomItemService;

/**
 * Equips and unequips kingdom items on a hero's twelve positions (GDD 5.5,
 * decision of 2026-11-19). The kingdom owns the item, the hero wears it.
 *
 * Guarantees: only a hero of the item's kingdom can wear it (also enforced by
 * a composite foreign key); only an AVAILABLE item can be equipped; one item
 * per position (unique index); equipping over an occupied position is one
 * "swap" operation with two movements, never equipped -> equipped directly.
 * Lock order: the kingdom's planets (HeroEffectsChanged) -> hero -> items.
 * Every change settles the production at the old rate, then recalculates it.
 */
class HeroEquipmentService
{
    public function __construct(
        private readonly ItemLedger $ledger,
        private readonly HeroEffectsChanged $effectsChanged,
        private readonly KingdomItemService $items,
    ) {
    }

    /**
     * Wears an item of the hero's kingdom, on the requested position or the
     * first free one allowed by its type (a ring goes to ring_1 then ring_2).
     */
    public function equip(Hero $hero, KingdomItem $item, EquipmentSlot|null $position = null, string|null $idempotencyKey = null, int|null $actorUserId = null): KingdomItem
    {
        $idempotencyKey ??= 'equip:' . Str::ulid();

        $this->effectsChanged->apply($hero->user_id, function () use ($hero, $item, $position, $idempotencyKey, $actorUserId): void {
            $this->ledger->run(
                $idempotencyKey,
                ItemOperationType::EQUIP,
                $hero->user_id,
                ['hero' => $hero->id, 'item' => $item->uid, 'position' => $position?->value],
                function (ItemOperation $operation) use ($hero, $item, $position): void {
                    $lockedHero = Hero::whereKey($hero->id)->lockForUpdate()->firstOrFail();
                    $locked = $this->lockItems($lockedHero, $item);
                    $target = $locked->firstWhere('id', $item->id);
                    if (!$target instanceof KingdomItem) {
                        throw HeroRuleException::because('item_not_owned');
                    }

                    $slot = $this->checkEquippable($lockedHero, $target, $position);

                    $occupant = $locked->first(fn (KingdomItem $worn) => $worn->id !== $target->id
                        && $worn->holder_hero_id === $lockedHero->id
                        && $worn->equipped_slot === $slot);

                    if ($occupant instanceof KingdomItem) {
                        $this->takeOff($operation, $occupant);
                        $operation->type = ItemOperationType::SWAP;
                        $operation->save();
                    }

                    $from = $this->ledger->locationOf($target);
                    $affected = KingdomItem::whereKey($target->id)
                        ->where('state', KingdomItemState::AVAILABLE->value)
                        ->where('owner_user_id', $lockedHero->user_id)
                        ->update([
                            'state' => KingdomItemState::EQUIPPED->value,
                            'holder_hero_id' => $lockedHero->id,
                            'equipped_slot' => $slot->value,
                        ]);
                    if ($affected !== 1) {
                        throw HeroRuleException::because('item_not_available');
                    }

                    $target->refresh();
                    $this->ledger->move($operation, $target, $from);
                },
                $actorUserId,
            );
        });

        return $item->refresh();
    }

    /**
     * Sends a worn item back to the kingdom's bag. Refused when the bag is
     * full (the player discards something first).
     */
    public function unequip(Hero $hero, KingdomItem $item, string|null $idempotencyKey = null, int|null $actorUserId = null): KingdomItem
    {
        $idempotencyKey ??= 'unequip:' . Str::ulid();

        $this->effectsChanged->apply($hero->user_id, function () use ($hero, $item, $idempotencyKey, $actorUserId): void {
            $this->ledger->run(
                $idempotencyKey,
                ItemOperationType::UNEQUIP,
                $hero->user_id,
                ['hero' => $hero->id, 'item' => $item->uid],
                function (ItemOperation $operation) use ($hero, $item): void {
                    $lockedHero = Hero::whereKey($hero->id)->lockForUpdate()->firstOrFail();
                    $target = $this->lockItems($lockedHero, $item)->firstWhere('id', $item->id);

                    if (!$target instanceof KingdomItem || $target->holder_hero_id !== $lockedHero->id || $target->state !== KingdomItemState::EQUIPPED) {
                        throw HeroRuleException::because('item_not_equipped');
                    }
                    if ($this->items->isBagFull($lockedHero->user_id)) {
                        throw HeroRuleException::because('inventory_full');
                    }

                    $this->takeOff($operation, $target);
                },
                $actorUserId,
            );
        });

        return $item->refresh();
    }

    /**
     * Takes off everything a hero wears, ignoring the bag cap: before the
     * hero is deleted or replaced, or its role or archetype changes (note U3).
     * Must run inside a HeroEffectsChanged::apply() when the production
     * matters (KingdomPurgeService and the demo seed do not need it).
     */
    public function unequipAll(Hero $hero, string $idempotencyKey, int|null $actorUserId = null): void
    {
        $worn = KingdomItem::where('holder_hero_id', $hero->id)->orderBy('id')->pluck('uid')->all();
        if ($worn === []) {
            return;
        }

        $this->ledger->run(
            $idempotencyKey,
            ItemOperationType::UNEQUIP,
            $hero->user_id,
            ['hero' => $hero->id, 'items' => $worn],
            function (ItemOperation $operation) use ($hero): void {
                $locked = KingdomItem::where('holder_hero_id', $hero->id)->orderBy('id')->lockForUpdate()->get();
                foreach ($locked as $item) {
                    $this->takeOff($operation, $item);
                }
            },
            $actorUserId,
        );
    }

    /**
     * Positions the hero can use: all twelve for the main hero, a
     * configurable subset for advisors ("lighter inventory").
     *
     * @param Hero $hero
     * @return list<string>
     */
    public function allowedSlots(Hero $hero): array
    {
        if ($hero->role === HeroRole::ADVISOR) {
            /** @var list<string> $slots */
            $slots = config('heroes.advisors.slots');

            return $slots;
        }

        return array_map(fn (EquipmentSlot $slot) => $slot->value, EquipmentSlot::cases());
    }

    /**
     * Equipment classes an archetype may wear (config/heroes.php); an
     * archetype missing from the configuration wears "any" items only.
     *
     * @return list<string>
     */
    public function allowedClasses(string $archetype): array
    {
        /** @var list<string> $classes */
        $classes = config('heroes.archetypes.' . $archetype . '.equip_classes', ['any']);

        return $classes;
    }

    /**
     * Checks every rule of "equip" and returns the position to occupy.
     */
    private function checkEquippable(Hero $hero, KingdomItem $item, EquipmentSlot|null $requested): EquipmentSlot
    {
        if ($item->owner_user_id !== $hero->user_id) {
            throw HeroRuleException::because('item_not_owned');
        }
        if ($item->state !== KingdomItemState::AVAILABLE) {
            throw HeroRuleException::because('item_not_available');
        }

        $definition = $item->definition;
        if ($definition->kind !== ItemKind::EQUIPMENT || $definition->slot_type === null) {
            throw HeroRuleException::because('slot_not_allowed');
        }

        $allowed = $this->allowedSlots($hero);
        $positions = array_values(array_filter(
            $definition->slot_type->positions(),
            fn (EquipmentSlot $slot) => in_array($slot->value, $allowed, true)
        ));
        if ($positions === [] || ($requested !== null && !in_array($requested, $positions, true))) {
            throw HeroRuleException::because('slot_not_allowed');
        }

        if ($hero->level < $definition->required_level) {
            throw HeroRuleException::because('level_too_low');
        }

        if (!in_array($definition->equip_class->value, $this->allowedClasses($hero->archetype), true)) {
            throw HeroRuleException::because('class_not_allowed');
        }

        if ($requested !== null) {
            return $requested;
        }

        $taken = KingdomItem::where('holder_hero_id', $hero->id)->pluck('equipped_slot')
            ->map(fn ($slot) => $slot instanceof EquipmentSlot ? $slot->value : $slot)
            ->all();
        foreach ($positions as $slot) {
            if (!in_array($slot->value, $taken, true)) {
                return $slot;
            }
        }

        return $positions[0];
    }

    /**
     * Locks, in id order, the target item and everything the hero wears.
     *
     * @return Collection<int, KingdomItem>
     */
    private function lockItems(Hero $hero, KingdomItem $item): Collection
    {
        return KingdomItem::where(fn ($query) => $query->whereKey($item->id)->orWhere('holder_hero_id', $hero->id))
            ->where('owner_user_id', $hero->user_id)
            ->orderBy('id')
            ->lockForUpdate()
            ->with('definition')
            ->get();
    }

    /**
     * equipped -> available, conditional on the current location.
     */
    private function takeOff(ItemOperation $operation, KingdomItem $item): void
    {
        $from = $this->ledger->locationOf($item);
        $affected = KingdomItem::whereKey($item->id)
            ->where('state', KingdomItemState::EQUIPPED->value)
            ->where('holder_hero_id', $item->holder_hero_id)
            ->update([
                'state' => KingdomItemState::AVAILABLE->value,
                'holder_hero_id' => null,
                'equipped_slot' => null,
            ]);
        if ($affected !== 1) {
            throw HeroRuleException::because('item_not_equipped');
        }

        $item->refresh();
        $this->ledger->move($operation, $item, $from);
    }
}
