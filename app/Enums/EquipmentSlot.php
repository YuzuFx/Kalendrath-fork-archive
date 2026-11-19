<?php

namespace OGame\Enums;

/**
 * The twelve equipment positions of a hero (decision of 2026-11-19): head,
 * neck, shoulders, cloak, chest, wrists, hands, belt, legs, feet and two
 * rings. The item's type is EquipmentSlotType; a ring may sit in either ring
 * position. "boots" is displayed as "Pieds" / "Feet": only the label changed.
 */
enum EquipmentSlot: string
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
    case RING_1 = 'ring_1';
    case RING_2 = 'ring_2';

    /**
     * Translated display name of the position.
     *
     * @return string
     */
    public function getName(): string
    {
        return __('t_heroes.slot.' . $this->value);
    }
}
