<?php

namespace OGame\Enums;

/**
 * The type of an equipment item (what kind of piece it is), as opposed to
 * EquipmentSlot, the position it occupies on the hero. Eleven types for
 * twelve positions: a ring fits either ring position (decision of
 * 2026-11-19, docs/archives/retours-agents-2026-11-19-schema-donnees-objets.md U1).
 */
enum EquipmentSlotType: string
{
    case HEAD = 'head';
    case NECK = 'neck';
    case SHOULDERS = 'shoulders';
    case CLOAK = 'cloak';
    case CHEST = 'chest';
    case WRISTS = 'wrists';
    case HANDS = 'hands';
    case BELT = 'belt';
    case LEGS = 'legs';
    case BOOTS = 'boots';
    case RING = 'ring';

    /**
     * Positions an item of this type may occupy, in preference order.
     *
     * @return list<EquipmentSlot>
     */
    public function positions(): array
    {
        if ($this === self::RING) {
            return [EquipmentSlot::RING_1, EquipmentSlot::RING_2];
        }

        return [EquipmentSlot::from($this->value)];
    }

    /**
     * Translated display name of the type.
     *
     * @return string
     */
    public function getName(): string
    {
        return __('t_items.slot_type.' . $this->value);
    }
}
