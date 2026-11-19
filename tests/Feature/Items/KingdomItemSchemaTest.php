<?php

namespace Tests\Feature\Items;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use OGame\Enums\HeroArchetype;
use OGame\Models\User;
use OGame\Services\Hero\HeroEquipmentService;
use OGame\Services\Hero\HeroRosterService;
use Tests\Feature\Hero\HeroTestCase;

/**
 * The database itself enforces the item invariants (CHECK constraints,
 * composite foreign key, unique position), not only the services.
 */
class KingdomItemSchemaTest extends HeroTestCase
{
    use ItemFixtures;

    private function assertRejected(callable $statement): void
    {
        try {
            DB::transaction(fn () => $statement());
        } catch (QueryException) {
            $this->addToAssertionCount(1);

            return;
        }
        $this->fail('The database accepted an inconsistent item row.');
    }

    public function testEquippedStateRequiresAHolderAndAPosition(): void
    {
        $item = $this->give($this->user->id, $this->definition('test_helm'));

        $this->assertRejected(fn () => DB::table('kingdom_items')->where('id', $item->id)->update(['state' => 'equipped']));
    }

    public function testAHolderWithoutPositionIsRejected(): void
    {
        $item = $this->give($this->user->id, $this->definition('test_helm'));

        $this->assertRejected(fn () => DB::table('kingdom_items')->where('id', $item->id)->update(['holder_hero_id' => $this->hero->id]));
    }

    public function testUnknownStateIsRejectedCaseSensitively(): void
    {
        $item = $this->give($this->user->id, $this->definition('test_helm'));

        $this->assertRejected(fn () => DB::table('kingdom_items')->where('id', $item->id)->update(['state' => 'AVAILABLE']));
    }

    public function testReservedStateRequiresBothReservationColumns(): void
    {
        $item = $this->give($this->user->id, $this->definition('test_helm'));

        $this->assertRejected(fn () => DB::table('kingdom_items')->where('id', $item->id)->update(['state' => 'reserved', 'reservation_type' => 'market']));
        $this->assertRejected(fn () => DB::table('kingdom_items')->where('id', $item->id)->update(['reservation_type' => 'market', 'reservation_id' => 1]));

        DB::table('kingdom_items')->where('id', $item->id)->update(['state' => 'reserved', 'reservation_type' => 'market', 'reservation_id' => 1]);
        $this->assertSame('reserved', DB::table('kingdom_items')->where('id', $item->id)->value('state'));
    }

    public function testAHeroCannotWearAnItemOfAnotherKingdom(): void
    {
        $other = User::factory()->create();
        $item = $this->give($other->id, $this->definition('test_helm'));

        $this->assertRejected(fn () => DB::table('kingdom_items')->where('id', $item->id)->update([
            'state' => 'equipped', 'holder_hero_id' => $this->hero->id, 'equipped_slot' => 'head',
        ]));
    }

    public function testOnePositionHoldsOneItem(): void
    {
        $first = $this->give($this->user->id, $this->definition('test_helm'));
        $second = $this->give($this->user->id, $this->definition('test_helm'));
        resolve(HeroEquipmentService::class)->equip($this->hero, $first);

        $this->assertRejected(fn () => DB::table('kingdom_items')->where('id', $second->id)->update([
            'state' => 'equipped', 'holder_hero_id' => $this->hero->id, 'equipped_slot' => 'head',
        ]));
    }

    public function testAHeroWearingAnItemCannotBeDeleted(): void
    {
        $advisor = resolve(HeroRosterService::class)->recruitAdvisor($this->user, HeroArchetype::WARRIOR, 'Squire');
        resolve(HeroEquipmentService::class)->equip($advisor, $this->give($this->user->id, $this->definition('test_helm')));

        $this->assertRejected(fn () => $advisor->delete());
    }

    public function testEquipmentDefinitionRequiresASlotType(): void
    {
        $this->assertRejected(fn () => DB::table('item_definitions')->insert([
            'code' => 'test_no_slot', 'kind' => 'equipment', 'slot_type' => null, 'content_hash' => str_repeat('0', 64),
        ]));
    }

    public function testEffectsAreUniquePerTypeScopeAndTarget(): void
    {
        $definition = $this->definition('test_helm', 'head', 'any', [
            ['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 1],
        ]);

        $this->assertRejected(fn () => DB::table('item_definition_effects')->insert([
            'item_definition_id' => $definition->id, 'effect_type' => 'production_bonus_percent',
            'effect_scope' => 'empire', 'target' => 'metal', 'value' => 2,
        ]));
    }
}
