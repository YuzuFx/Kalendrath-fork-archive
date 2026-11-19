<?php

namespace Tests\Feature\Items;

use OGame\Enums\ItemSourceType;
use OGame\Models\Hero;
use OGame\Models\ItemOperation;
use OGame\Models\KingdomItem;
use OGame\Services\Hero\HeroEquipmentService;
use OGame\Services\Items\ItemCatalogService;
use OGame\Services\Items\LootContext;
use OGame\Services\Items\LootService;
use OGame\Services\Items\LootTable;
use OGame\Services\SettingsService;
use Tests\Feature\Hero\HeroTestCase;

class LootServiceTest extends HeroTestCase
{
    use ItemFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        resolve(ItemCatalogService::class)->sync();
        resolve(SettingsService::class)->set('loot_item_rate_multiplier', '1.0');
    }

    private function context(string $key = 'test:loot', int|null $seed = 1234, string $table = 'expedition.t1', int|null $userId = null): LootContext
    {
        return new LootContext(ItemSourceType::EXPEDITION, '42', $table, $userId ?? $this->user->id, $key, $seed);
    }

    private function ringTable(): void
    {
        config(['loot.tables' => (array) config('loot.tables') + [
            'test.t1' => ['source' => 'expedition', 'drop_chance' => 1.0, 'level_margin' => null, 'slot_types' => ['ring'], 'rarity_multipliers' => [], 'weights' => []],
        ]]);
    }

    public function testLootGivesAnItemTheMainHeroCanWearAtOnce(): void
    {
        $item = resolve(LootService::class)->grant($this->context());

        $this->assertNotNull($item);
        $this->assertSame($this->user->id, $item->owner_user_id);
        $this->assertSame(ItemSourceType::EXPEDITION, $item->source_type);
        $this->assertContains($item->definition->equip_class->value, ['heavy', 'any']);

        $worn = resolve(HeroEquipmentService::class)->equip($this->hero, $item);
        $this->assertSame($this->hero->id, $worn->holder_hero_id);
    }

    public function testASorcererOnlyReceivesItemsASorcererCanWear(): void
    {
        Hero::whereKey($this->hero->id)->update(['archetype' => 'sorcerer']);

        for ($seed = 1; $seed <= 15; $seed++) {
            $item = resolve(LootService::class)->grant($this->context('test:loot:' . $seed, $seed));
            $this->assertNotNull($item);
            $this->assertContains($item->definition->equip_class->value, ['arcane', 'any']);
        }
    }

    public function testTheLevelMarginIsRespected(): void
    {
        for ($seed = 1; $seed <= 15; $seed++) {
            $item = resolve(LootService::class)->grant($this->context('test:loot:' . $seed, $seed));
            $this->assertNotNull($item);
            // Hero level 1, margin 2: nothing above level 3.
            $this->assertLessThanOrEqual(3, $item->definition->required_level);
        }
    }

    public function testALevelMarginOfZeroOnlyGivesItemsWearableAtOnce(): void
    {
        config(['loot.level_margin' => 0]);

        for ($seed = 1; $seed <= 20; $seed++) {
            $item = resolve(LootService::class)->grant($this->context('test:loot:' . $seed, $seed));
            $this->assertNotNull($item);
            $this->assertLessThanOrEqual($this->hero->level, $item->definition->required_level);
            resolve(HeroEquipmentService::class)->equip($this->hero, $item);
        }
    }

    public function testAnEmptyPoolIsJournaledAndNeverRerolled(): void
    {
        config(['loot.tables' => (array) config('loot.tables') + [
            'test.empty' => ['source' => 'expedition', 'drop_chance' => 1.0, 'level_margin' => null, 'slot_types' => ['ring'], 'rarity_multipliers' => [], 'weights' => ['ring_copper_band' => 0, 'ring_mana_signet' => 0, 'ring_iron_seal' => 0]],
        ]]);

        $this->assertNull(resolve(LootService::class)->grant($this->context('test:empty', 1, 'test.empty')));
        $this->assertNull(resolve(LootService::class)->grant($this->context('test:empty', 2, 'test.empty')));

        $operations = ItemOperation::where('idempotency_key', 'test:empty')->get();
        $this->assertCount(1, $operations);
        $meta = $operations->firstOrFail()->meta ?? [];
        $this->assertSame(LootTable::RESULT_EMPTY_POOL, $meta['result']);
        $this->assertSame(0, $meta['pool_size']);
        $this->assertSame(LootTable::fromConfig('test.empty')->revision(), $meta['table_revision']);
    }

    public function testTheSameLootIsNeverGrantedTwice(): void
    {
        $service = resolve(LootService::class);
        $first = $service->grant($this->context('test:loot', 1));
        $second = $service->grant($this->context('test:loot', 999));

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second?->id);
        $this->assertSame(1, KingdomItem::where('owner_user_id', $this->user->id)->count());
        $this->assertSame(1, ItemOperation::where('idempotency_key', 'test:loot')->count());
    }

    public function testTheRateMultiplierCanStopTheDropAndTheMissIsNotRerolled(): void
    {
        resolve(SettingsService::class)->set('loot_item_rate_multiplier', '0');
        $this->assertNull(resolve(LootService::class)->grant($this->context('test:loot')));

        resolve(SettingsService::class)->set('loot_item_rate_multiplier', '1.0');
        $this->assertNull(resolve(LootService::class)->grant($this->context('test:loot')));

        $operation = ItemOperation::where('idempotency_key', 'test:loot')->firstOrFail();
        $this->assertFalse($operation->meta['dropped'] ?? true);
        $this->assertSame(LootTable::RESULT_NO_DROP, $operation->meta['result'] ?? null);
    }

    public function testTheDrawIsJournaledForAudit(): void
    {
        resolve(SettingsService::class)->set('loot_item_rate_multiplier', '0.5');
        $this->ringTable();

        resolve(LootService::class)->grant($this->context('test:loot', 7, 'test.t1'));

        $meta = ItemOperation::where('idempotency_key', 'test:loot')->firstOrFail()->meta ?? [];
        $this->assertSame('test.t1', $meta['loot_table']);
        $this->assertSame(config('loot.version'), $meta['loot_version']);
        $this->assertSame(0.5, $meta['multiplier']);
        $this->assertSame(7, $meta['seed']);
        $this->assertSame(LootTable::fromConfig('test.t1')->revision(), $meta['table_revision']);
        $this->assertContains($meta['result'], [LootTable::RESULT_GRANTED, LootTable::RESULT_NO_DROP]);
        $this->assertArrayHasKey('dropped', $meta);
    }

    public function testSlotTypesOfTheTableAreRespected(): void
    {
        $this->ringTable();

        $item = resolve(LootService::class)->grant($this->context('test:loot', 3, 'test.t1'));

        $this->assertSame('ring', $item?->definition->slot_type?->value);
    }

    public function testAKingdomWithoutMainHeroDrawsFromTheAlphaArchetypes(): void
    {
        $this->hero->delete();

        $item = resolve(LootService::class)->grant($this->context());

        $this->assertNotNull($item);
        $this->assertContains($item->definition->equip_class->value, ['heavy', 'arcane', 'any']);
    }

    public function testNpcsNeverLoot(): void
    {
        $this->assertNull(resolve(LootService::class)->grant($this->context('test:npc', 1, 'expedition.t1', -5)));
        $this->assertSame(0, ItemOperation::where('idempotency_key', 'test:npc')->count());
    }

    public function testTheBagCapIsSoftForLoot(): void
    {
        config(['items.inventory.capacity' => 0]);

        $this->assertNotNull(resolve(LootService::class)->grant($this->context()));
    }
}
