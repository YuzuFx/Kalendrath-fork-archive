<?php

namespace OGame\Enums;

/**
 * Who/what a talent node's effect applies to (GDD section 5.5).
 * A "royaume" talent targets the whole kingdom (no governor per city since
 * 2026-11-19), a support talent may target the hero's party/group, and the
 * main hero's "royaume" path (plus capped advisor bonuses) applies empire-wide
 * (revision of 2026-10-11).
 */
enum TalentEffectScope: string
{
    case HERO = 'hero';
    case CITY = 'city';
    case GROUP = 'group';
    case EMPIRE = 'empire';

    /**
     * Display name (French — see project convention: game content is
     * authored in French for now, international locales come later).
     */
    public function getName(): string
    {
        return match ($this) {
            self::HERO => 'Héros',
            self::CITY => 'Ville',
            self::GROUP => 'Groupe',
            self::EMPIRE => 'Empire',
        };
    }
}
