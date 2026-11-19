<?php

namespace Tests\Feature\Items;

use Illuminate\Support\Str;
use OGame\Enums\ItemSourceType;
use OGame\Models\ItemDefinition;
use OGame\Models\KingdomItem;
use OGame\Services\Items\ItemCatalogService;
use OGame\Services\Items\KingdomItemService;

/**
 * Helpers to create item definitions and give items in tests.
 */
trait ItemFixtures
{
    /**
     * Creates (or updates) a test definition through the catalog service.
     *
     * @param list<array{type: string, scope: string, target?: string, value: float}> $effects
     */
    protected function definition(string $code, string $slotType = 'head', string $equipClass = 'any', array $effects = [], int $requiredLevel = 1, int $rarity = 1): ItemDefinition
    {
        resolve(ItemCatalogService::class)->upsert($code, [
            'kind' => 'equipment',
            'slot_type' => $slotType,
            'equip_class' => $equipClass,
            'rarity' => $rarity,
            'required_level' => $requiredLevel,
            'effects' => $effects,
        ]);

        return ItemDefinition::where('code', $code)->firstOrFail();
    }

    protected function give(int $userId, ItemDefinition $definition, string|null $key = null): KingdomItem
    {
        return resolve(KingdomItemService::class)->grant(
            $userId,
            $definition,
            ItemSourceType::ADMIN,
            'test',
            $key ?? 'test:' . Str::ulid(),
        );
    }
}
