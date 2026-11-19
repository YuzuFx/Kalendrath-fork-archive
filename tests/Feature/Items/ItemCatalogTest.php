<?php

namespace Tests\Feature\Items;

use Illuminate\Testing\PendingCommand;
use OGame\Enums\EquipmentSlot;
use OGame\Enums\ItemOperationType;
use OGame\Models\ItemDefinition;
use OGame\Models\ItemOperation;
use OGame\Models\KingdomItem;
use OGame\Services\Hero\HeroEquipmentService;
use OGame\Services\Items\ItemCatalogService;
use Tests\Feature\Hero\HeroTestCase;

class ItemCatalogTest extends HeroTestCase
{
    use ItemFixtures;

    private function catalog(): ItemCatalogService
    {
        return resolve(ItemCatalogService::class);
    }

    public function testSyncLoadsEveryCatalogEntryWithItsEffects(): void
    {
        $this->catalog()->sync();

        foreach ($this->catalog()->catalog() as $code => $entry) {
            $definition = ItemDefinition::where('code', $code)->firstOrFail();
            $this->assertNull($definition->retired_at);
            $this->assertCount(count($entry['effects']), $definition->effects);
        }
    }

    public function testSyncIsIdempotent(): void
    {
        $this->catalog()->sync();
        $report = $this->catalog()->sync();

        $this->assertSame([], $report['created']);
        $this->assertSame([], $report['rebalanced']);
        $this->assertCount(count($this->catalog()->catalog()), $report['unchanged']);
    }

    public function testADefinitionMissingFromTheCatalogIsRetiredNotDeleted(): void
    {
        $this->definition('test_obsolete');

        $report = $this->catalog()->sync();

        $this->assertContains('test_obsolete', $report['retired']);
        $this->assertNotNull(ItemDefinition::where('code', 'test_obsolete')->firstOrFail()->retired_at);
    }

    public function testRebalancingOneDefinitionBumpsItsVersionAndIsJournaled(): void
    {
        $definition = $this->definition('test_helm', 'head', 'any', [['type' => 'production_bonus_percent', 'scope' => 'empire', 'value' => 1]]);
        $other = $this->definition('test_boots', 'boots', 'any', [['type' => 'production_bonus_percent', 'scope' => 'empire', 'value' => 1]]);
        $item = $this->give($this->user->id, $definition);

        $this->definition('test_helm', 'head', 'any', [['type' => 'production_bonus_percent', 'scope' => 'empire', 'value' => 2]]);

        $definition->refresh();
        $this->assertSame(2, $definition->version);
        $this->assertEqualsWithDelta(2.0, $definition->effects->first()?->value, 0.001);
        // Only that definition changed; the copy keeps its acquisition version for audit.
        $this->assertSame(1, $other->refresh()->version);
        $this->assertSame(1, $item->refresh()->definition_version);

        $operation = ItemOperation::where('idempotency_key', 'rebalance:test_helm:v2')->firstOrFail();
        $this->assertSame(ItemOperationType::DEFINITION_REBALANCE, $operation->type);
        $this->assertSame(1, $operation->meta['from_version'] ?? null);
    }

    public function testEveryCatalogItemHasFrenchAndEnglishNames(): void
    {
        foreach (array_keys($this->catalog()->catalog()) as $code) {
            foreach (['fr', 'en'] as $locale) {
                $key = 't_items.item.' . $code . '.name';
                $this->assertTrue(trans()->has($key, $locale), "Missing {$locale} name for {$code}");
            }
        }
    }

    public function testEachAlphaArchetypeCanFillAllTwelvePositionsFromTheCatalog(): void
    {
        $this->catalog()->sync();
        $equipment = resolve(HeroEquipmentService::class);

        foreach (['warrior', 'sorcerer'] as $archetype) {
            $positions = [];
            $definitions = ItemDefinition::whereIn('equip_class', $equipment->allowedClasses($archetype))->whereNull('retired_at')->get();
            foreach ($definitions as $definition) {
                foreach ($definition->slot_type?->positions() ?? [] as $slot) {
                    $positions[$slot->value] = true;
                }
            }
            $this->assertCount(count(EquipmentSlot::cases()), $positions, "Archetype {$archetype} cannot fill every position");
        }
    }

    public function testEffectsAreReadable(): void
    {
        $definition = $this->definition('test_helm', 'head', 'any', [['type' => 'production_bonus_percent', 'scope' => 'empire', 'target' => 'metal', 'value' => 1.5]]);
        app()->setLocale('fr');

        $this->assertSame('+1,5 % de production (Bois)', $definition->effects->first()?->describe());
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function command(string $name, array $parameters = []): PendingCommand
    {
        $pending = $this->artisan($name, $parameters);
        $this->assertInstanceOf(PendingCommand::class, $pending);

        return $pending;
    }

    public function testSyncCommandCanTargetOneItem(): void
    {
        $this->command('kalendrath:items:sync', ['--code' => ['ring_copper_band']])->assertSuccessful();

        $this->assertNotNull(ItemDefinition::where('code', 'ring_copper_band')->first());
    }

    public function testResetCommandWipesItemsAndJournalButKeepsTheCatalog(): void
    {
        $definition = $this->definition('test_helm');
        $this->give($this->user->id, $definition);

        $this->command('kalendrath:items:reset')->assertFailed();
        $this->assertSame(1, KingdomItem::where('owner_user_id', $this->user->id)->count());

        $this->command('kalendrath:items:reset', ['--force' => true])->assertSuccessful();
        $this->assertSame(0, KingdomItem::count());
        $this->assertSame(0, ItemOperation::count());
        $this->assertNotNull(ItemDefinition::where('code', 'test_helm')->first());
    }
}
