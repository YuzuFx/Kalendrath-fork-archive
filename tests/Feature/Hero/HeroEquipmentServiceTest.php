<?php

namespace Tests\Feature\Hero;

use OGame\Enums\EquipmentSlot;
use OGame\Enums\HeroArchetype;
use OGame\Enums\ItemOperationType;
use OGame\Enums\KingdomItemState;
use OGame\Exceptions\HeroRuleException;
use OGame\Models\ItemMovement;
use OGame\Models\ItemOperation;
use OGame\Models\KingdomItem;
use OGame\Models\User;
use OGame\Services\Hero\HeroEquipmentService;
use OGame\Services\Hero\HeroRosterService;
use OGame\Services\Items\KingdomItemService;
use Tests\Feature\Items\ItemFixtures;

class HeroEquipmentServiceTest extends HeroTestCase
{
    use ItemFixtures;

    private function own(string $slotType, int $requiredLevel = 1, string $class = 'any', int|null $userId = null): KingdomItem
    {
        $definition = $this->definition('test_' . $slotType . '_' . $class . '_' . $requiredLevel, $slotType, $class, [], $requiredLevel);

        return $this->give($userId ?? $this->user->id, $definition);
    }

    private function service(): HeroEquipmentService
    {
        return resolve(HeroEquipmentService::class);
    }

    public function testEquipOccupiesTheSlot(): void
    {
        $helm = $this->service()->equip($this->hero, $this->own('head'));

        $this->assertSame(KingdomItemState::EQUIPPED, $helm->state);
        $this->assertSame(EquipmentSlot::HEAD, $helm->equipped_slot);
        $this->assertSame($this->hero->id, $helm->holder_hero_id);
        // The kingdom still owns it.
        $this->assertSame($this->user->id, $helm->owner_user_id);
    }

    public function testAllTwelvePositionsCanBeWornAtOnce(): void
    {
        foreach (['head', 'neck', 'shoulders', 'cloak', 'chest', 'wrists', 'hands', 'belt', 'legs', 'boots', 'ring', 'ring'] as $type) {
            $this->service()->equip($this->hero, $this->own($type));
        }

        $worn = KingdomItem::where('holder_hero_id', $this->hero->id)->pluck('equipped_slot')->map(fn ($slot) => $slot->value)->sort()->values()->all();
        $expected = array_map(fn (EquipmentSlot $slot) => $slot->value, EquipmentSlot::cases());
        sort($expected);
        $this->assertSame($expected, $worn);
    }

    public function testARingGoesToTheFirstFreeRingPosition(): void
    {
        $first = $this->service()->equip($this->hero, $this->own('ring'));
        $second = $this->service()->equip($this->hero, $this->own('ring'));

        $this->assertSame(EquipmentSlot::RING_1, $first->equipped_slot);
        $this->assertSame(EquipmentSlot::RING_2, $second->equipped_slot);
    }

    public function testRequestedPositionIsHonoured(): void
    {
        $ring = $this->service()->equip($this->hero, $this->own('ring'), EquipmentSlot::RING_2);

        $this->assertSame(EquipmentSlot::RING_2, $ring->equipped_slot);
    }

    public function testAPositionOutsideTheItemTypeIsRefused(): void
    {
        $this->expectException(HeroRuleException::class);

        $this->service()->equip($this->hero, $this->own('ring'), EquipmentSlot::HEAD);
    }

    public function testEquippingOverAnOccupiedPositionIsOneSwapWithTwoMovements(): void
    {
        $old = $this->service()->equip($this->hero, $this->own('head'));
        $new = $this->service()->equip($this->hero, $this->own('head'), null, 'test:swap');

        $this->assertSame(KingdomItemState::AVAILABLE, $old->refresh()->state);
        $this->assertNull($old->holder_hero_id);
        $this->assertSame(KingdomItemState::EQUIPPED, $new->state);

        $operation = ItemOperation::where('idempotency_key', 'test:swap')->firstOrFail();
        $this->assertSame(ItemOperationType::SWAP, $operation->type);
        $this->assertSame(2, $operation->movements()->count());
    }

    public function testEquipIsJournaled(): void
    {
        $helm = $this->own('head');
        $this->service()->equip($this->hero, $helm, null, 'test:equip');

        $movement = ItemMovement::whereHas('operation', fn ($q) => $q->where('idempotency_key', 'test:equip'))->firstOrFail();
        $this->assertSame('available', $movement->from_state);
        $this->assertSame('equipped', $movement->to_state);
        $this->assertSame($this->hero->id, $movement->to_hero_id);
        $this->assertSame('head', $movement->to_slot);
        $this->assertSame($helm->uid, $movement->item_uid);
    }

    public function testReplayingAnEquipRequestHasNoEffect(): void
    {
        $helm = $this->own('head');
        $this->service()->equip($this->hero, $helm, null, 'test:equip');
        $this->service()->equip($this->hero, $helm, null, 'test:equip');

        $this->assertSame(1, ItemOperation::where('idempotency_key', 'test:equip')->count());
        $this->assertSame(1, ItemMovement::where('kingdom_item_id', $helm->id)->where('to_state', 'equipped')->count());
    }

    public function testRequiredLevelIsEnforced(): void
    {
        $this->expectException(HeroRuleException::class);

        $this->service()->equip($this->hero, $this->own('chest', 5));
    }

    public function testItemOfAnotherKingdomIsRefused(): void
    {
        $other = User::factory()->create();

        $this->expectException(HeroRuleException::class);
        $this->service()->equip($this->hero, $this->own('head', 1, 'any', $other->id));
    }

    public function testAWarriorCannotWearArcaneGear(): void
    {
        $this->expectException(HeroRuleException::class);

        $this->service()->equip($this->hero, $this->own('chest', 1, 'arcane'));
    }

    public function testASorcererWearsArcaneButNotHeavyGear(): void
    {
        $other = User::factory()->create();
        $sorcerer = resolve(HeroRosterService::class)->recruitMain($other, HeroArchetype::SORCERER, 'Mage');

        $robe = $this->service()->equip($sorcerer, $this->own('chest', 1, 'arcane', $other->id));
        $this->assertSame(KingdomItemState::EQUIPPED, $robe->state);

        $this->expectException(HeroRuleException::class);
        $this->service()->equip($sorcerer, $this->own('legs', 1, 'heavy', $other->id));
    }

    public function testADestroyedItemCannotBeEquipped(): void
    {
        $helm = $this->own('head');
        resolve(KingdomItemService::class)->discard($this->user->id, $helm, 'test:discard');

        $this->expectException(HeroRuleException::class);
        $this->service()->equip($this->hero, $helm);
    }

    public function testAnItemWornByOneHeroCannotBeEquippedByAnother(): void
    {
        $advisor = resolve(HeroRosterService::class)->recruitAdvisor($this->user, HeroArchetype::WARRIOR, 'Squire');
        $helm = $this->service()->equip($this->hero, $this->own('head'));

        $this->expectException(HeroRuleException::class);
        $this->service()->equip($advisor, $helm);
    }

    public function testAdvisorSlotsFollowConfiguration(): void
    {
        config(['heroes.advisors.slots' => ['head']]);
        $advisor = resolve(HeroRosterService::class)->recruitAdvisor($this->user, HeroArchetype::WARRIOR, 'Squire');

        $this->service()->equip($advisor, $this->own('head'));

        $this->expectException(HeroRuleException::class);
        $this->service()->equip($advisor, $this->own('legs'));
    }

    public function testUnequipSendsTheItemBackToTheBag(): void
    {
        $helm = $this->service()->equip($this->hero, $this->own('head'));

        $helm = $this->service()->unequip($this->hero, $helm);

        $this->assertSame(KingdomItemState::AVAILABLE, $helm->state);
        $this->assertNull($helm->holder_hero_id);
        $this->assertNull($helm->equipped_slot);
    }

    public function testUnequipIsRefusedWhenTheBagIsFull(): void
    {
        config(['items.inventory.capacity' => 1]);
        $helm = $this->service()->equip($this->hero, $this->own('head'));
        $this->own('legs');

        $this->expectException(HeroRuleException::class);
        $this->service()->unequip($this->hero, $helm);
    }

    public function testUnequipOfAnItemNotWornIsRefused(): void
    {
        $this->expectException(HeroRuleException::class);

        $this->service()->unequip($this->hero, $this->own('head'));
    }

    public function testUnequipAllTakesEverythingOff(): void
    {
        $this->service()->equip($this->hero, $this->own('head'));
        $this->service()->equip($this->hero, $this->own('ring'));

        $this->service()->unequipAll($this->hero, 'test:unequip_all');

        $this->assertSame(0, KingdomItem::where('holder_hero_id', $this->hero->id)->count());
        $this->assertSame(2, KingdomItem::where('owner_user_id', $this->user->id)->where('state', 'available')->count());
    }
}
