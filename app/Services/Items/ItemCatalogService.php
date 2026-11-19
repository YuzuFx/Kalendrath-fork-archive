<?php

namespace OGame\Services\Items;

use Illuminate\Support\Facades\Date;
use OGame\Enums\EquipClass;
use OGame\Enums\EquipmentSlotType;
use OGame\Enums\ItemKind;
use OGame\Enums\ItemOperationType;
use OGame\Enums\KingdomItemState;
use OGame\Enums\TalentEffectScope;
use OGame\Models\ItemDefinition;
use OGame\Models\ItemDefinitionEffect;
use OGame\Models\ItemOperation;
use OGame\Models\KingdomItem;
use OGame\Services\Hero\HeroEffectsChanged;
use RuntimeException;

/**
 * Keeps the item catalog in database in line with database/catalog/items.php.
 *
 * A rebalance targets ONE definition (buff or nerf of one item, decision of
 * 2026-11-19): its effects are replaced, its version bumped, the change is
 * journaled (definition_rebalance), and every kingdom whose heroes wear the
 * item gets its production settled at the old rate, then recalculated. Every
 * copy of the item follows, since copies read their definition's effects.
 */
class ItemCatalogService
{
    public function __construct(
        private readonly ItemLedger $ledger,
        private readonly HeroEffectsChanged $effectsChanged,
    ) {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function catalog(): array
    {
        /** @var array<string, array<string, mixed>> $catalog */
        $catalog = require database_path('catalog/items.php');

        return $catalog;
    }

    /**
     * Synchronises the catalog. With $onlyCodes, only those entries are
     * synchronised and nothing is retired.
     *
     * @param list<string>|null $onlyCodes
     * @return array{created: list<string>, rebalanced: list<string>, retired: list<string>, unchanged: list<string>}
     */
    public function sync(array|null $onlyCodes = null): array
    {
        $report = ['created' => [], 'rebalanced' => [], 'retired' => [], 'unchanged' => []];
        $catalog = $this->catalog();

        foreach ($catalog as $code => $entry) {
            if ($onlyCodes !== null && !in_array($code, $onlyCodes, true)) {
                continue;
            }
            $report[$this->upsert($code, $entry)][] = $code;
        }

        if ($onlyCodes === null) {
            $missing = ItemDefinition::whereNotIn('code', array_keys($catalog))->whereNull('retired_at')->get();
            foreach ($missing as $definition) {
                $definition->retired_at = Date::now();
                $definition->save();
                $report['retired'][] = $definition->code;
            }
        }

        return $report;
    }

    /**
     * Creates or updates one definition from its catalog entry.
     *
     * @param array<string, mixed> $entry
     * @return 'created'|'rebalanced'|'unchanged'
     */
    public function upsert(string $code, array $entry): string
    {
        $normalized = $this->normalize($code, $entry);
        $hash = $this->hash($normalized);
        $definition = ItemDefinition::where('code', $code)->first();

        if ($definition === null) {
            $definition = ItemDefinition::create($this->attributes($normalized) + [
                'code' => $code,
                'version' => 1,
                'content_hash' => $hash,
            ]);
            $this->replaceEffects($definition, $normalized['effects']);

            return 'created';
        }

        if ($definition->retired_at !== null) {
            $definition->retired_at = null;
            $definition->save();
        }

        if ($definition->content_hash === $hash) {
            return 'unchanged';
        }

        $this->rebalance($definition, $normalized, $hash);

        return 'rebalanced';
    }

    /**
     * @param array{kind: string, slot_type: string|null, equip_class: string, rarity: int, required_level: int, effects: list<array{type: string, scope: string, target: string, value: float}>} $normalized
     */
    private function rebalance(ItemDefinition $definition, array $normalized, string $hash): void
    {
        /** @var list<int> $wearers */
        $wearers = KingdomItem::where('item_definition_id', $definition->id)
            ->where('state', KingdomItemState::EQUIPPED->value)
            ->distinct()
            ->pluck('owner_user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->effectsChanged->apply($wearers, function () use ($definition, $normalized, $hash): void {
            $newVersion = $definition->version + 1;
            $before = $definition->effects()->get(['effect_type', 'effect_scope', 'target', 'value'])->toArray();

            $this->ledger->run(
                'rebalance:' . $definition->code . ':v' . $newVersion,
                ItemOperationType::DEFINITION_REBALANCE,
                0,
                ['code' => $definition->code, 'hash' => $hash],
                function (ItemOperation $operation) use ($definition, $normalized, $hash, $newVersion): void {
                    $definition->fill($this->attributes($normalized));
                    $definition->version = $newVersion;
                    $definition->content_hash = $hash;
                    $definition->save();
                    $this->replaceEffects($definition, $normalized['effects']);
                },
                null,
                null,
                $definition->code,
                ['from_version' => $definition->version, 'to_version' => $newVersion, 'effects_before' => $before, 'effects_after' => $normalized['effects']],
            );
        });
    }

    /**
     * @param list<array{type: string, scope: string, target: string, value: float}> $effects
     */
    private function replaceEffects(ItemDefinition $definition, array $effects): void
    {
        ItemDefinitionEffect::where('item_definition_id', $definition->id)->delete();
        foreach ($effects as $order => $effect) {
            ItemDefinitionEffect::create([
                'item_definition_id' => $definition->id,
                'effect_type' => $effect['type'],
                'effect_scope' => $effect['scope'],
                'target' => $effect['target'],
                'value' => $effect['value'],
                'sort_order' => $order,
            ]);
        }
    }

    /**
     * Validates an entry against the enums and returns it in canonical form.
     *
     * @param array<string, mixed> $entry
     * @return array{kind: string, slot_type: string|null, equip_class: string, rarity: int, required_level: int, effects: list<array{type: string, scope: string, target: string, value: float}>}
     */
    private function normalize(string $code, array $entry): array
    {
        if (preg_match('/^[a-z0-9_]{1,64}$/', $code) !== 1) {
            throw new RuntimeException("Invalid item code: {$code}");
        }

        $kind = ItemKind::from((string) ($entry['kind'] ?? ItemKind::EQUIPMENT->value));
        $slotType = isset($entry['slot_type']) ? EquipmentSlotType::from((string) $entry['slot_type'])->value : null;
        if ($kind === ItemKind::EQUIPMENT && $slotType === null) {
            throw new RuntimeException("Equipment item without slot type: {$code}");
        }

        $effects = [];
        foreach ((array) ($entry['effects'] ?? []) as $effect) {
            $effects[] = [
                'type' => (string) $effect['type'],
                'scope' => TalentEffectScope::from((string) $effect['scope'])->value,
                'target' => (string) ($effect['target'] ?? '*'),
                'value' => round((float) $effect['value'], 2),
            ];
        }

        return [
            'kind' => $kind->value,
            'slot_type' => $slotType,
            'equip_class' => EquipClass::from((string) ($entry['equip_class'] ?? EquipClass::ANY->value))->value,
            'rarity' => (int) ($entry['rarity'] ?? 1),
            'required_level' => (int) ($entry['required_level'] ?? 1),
            'effects' => $effects,
        ];
    }

    /**
     * @param array{kind: string, slot_type: string|null, equip_class: string, rarity: int, required_level: int, effects: list<array<string, mixed>>} $normalized
     * @return array<string, mixed>
     */
    private function attributes(array $normalized): array
    {
        return [
            'kind' => $normalized['kind'],
            'slot_type' => $normalized['slot_type'],
            'equip_class' => $normalized['equip_class'],
            'rarity' => $normalized['rarity'],
            'required_level' => $normalized['required_level'],
        ];
    }

    /**
     * @param array<string, mixed> $normalized
     */
    private function hash(array $normalized): string
    {
        return hash('sha256', (string) json_encode($normalized));
    }
}
