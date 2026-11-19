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
            'test.t1' => ['drop_chance' => 1.0, 'rarity_weights' => [1 => 1], 'level_band' => 2, 'slot_types' => ['ring']],
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

    public function testTheLevelBandIsRespected(): void
    {
        for ($seed = 1; $seed <= 15; $seed++) {
            $item = resolve(LootService::class)->grant($this->context('test:loot:' . $seed, $seed));
            $this->assertNotNull($item);
            // Hero level 1, band 2: nothing above level 3.
            $this->assertLessThanOrEqual(3, $item->definition->required_level);
        }
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
