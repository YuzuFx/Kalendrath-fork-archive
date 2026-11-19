<?php

namespace OGame\Enums;

/**
 * Equipment class of an item definition: which archetypes may wear it is
 * decided once in config/heroes.php (archetypes.<code>.equip_classes), so a
 * new archetype is a configuration line, not a catalog review.
 * Values are provisional (to be validated by Gregory).
 */
enum EquipClass: string
{
    // Plate and mail: the Warrior's gear.
    case HEAVY = 'heavy';
    // Enchanted cloth: the Sorcerer's gear.
    case ARCANE = 'arcane';
    // Jewels, cloaks: anyone.
    case ANY = 'any';

    /**
     * Translated display name of the class.
     *
     * @return string
     */
    public function getName(): string
    {
        return __('t_items.equip_class.' . $this->value);
    }
}
