<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use OGame\Enums\EquipClass;
use OGame\Enums\EquipmentRarity;
use OGame\Enums\EquipmentSlotType;
use OGame\Enums\ItemKind;

/**
 * Catalog entry of an item (what an item IS), synchronised from
 * database/catalog/items.php by ItemCatalogService. Never deleted: retired.
 *
 * @property int $id
 * @property string $code
 * @property ItemKind $kind
 * @property EquipmentSlotType|null $slot_type
 * @property EquipClass $equip_class
 * @property int $rarity
 * @property int $required_level
 * @property int $version
 * @property string $content_hash
 * @property Carbon|null $retired_at
 * @property-read Collection<int, ItemDefinitionEffect> $effects
 */
#[Fillable([
    'code',
    'kind',
    'slot_type',
    'equip_class',
    'rarity',
    'required_level',
    'version',
    'content_hash',
    'retired_at',
])]
class ItemDefinition extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ItemKind::class,
            'slot_type' => EquipmentSlotType::class,
            'equip_class' => EquipClass::class,
            'rarity' => 'integer',
            'required_level' => 'integer',
            'version' => 'integer',
            'retired_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ItemDefinitionEffect, $this>
     */
    public function effects(): HasMany
    {
        return $this->hasMany(ItemDefinitionEffect::class)->orderBy('sort_order');
    }

    public function rarityEnum(): EquipmentRarity
    {
        return EquipmentRarity::from($this->rarity);
    }

    /**
     * Translated display name (t_items.item.<code>.name).
     */
    public function getName(): string
    {
        return __('t_items.item.' . $this->code . '.name');
    }

    /**
     * Translated description (t_items.item.<code>.description).
     */
    public function getDescription(): string
    {
        return __('t_items.item.' . $this->code . '.description');
    }
}
