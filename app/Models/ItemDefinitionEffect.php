<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OGame\Enums\TalentEffectScope;

/**
 * One effect of an item definition (N effects per definition).
 *
 * @property int $id
 * @property int $item_definition_id
 * @property string $effect_type
 * @property TalentEffectScope $effect_scope
 * @property string $target
 * @property float $value
 * @property int $sort_order
 */
#[Fillable([
    'item_definition_id',
    'effect_type',
    'effect_scope',
    'target',
    'value',
    'sort_order',
])]
class ItemDefinitionEffect extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effect_scope' => TalentEffectScope::class,
            'value' => 'float',
        ];
    }

    /**
     * Readable effect for the inventory ("+1 % de production (Bois)").
     */
    public function describe(): string
    {
        $key = 't_items.effect.' . $this->effect_type;
        $params = [
            'value' => rtrim(rtrim(number_format($this->value, 2, ',', ''), '0'), ','),
            'target' => __('t_items.target.' . $this->target),
            'type' => $this->effect_type,
        ];

        return __(trans()->has($key) ? $key : 't_items.effect.generic', $params);
    }

    /**
     * @return BelongsTo<ItemDefinition, $this>
     */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(ItemDefinition::class, 'item_definition_id');
    }
}
