<?php

namespace OGame\Services\Hero;

use OGame\GameMissions\BattleEngine\BattleEngine;
use OGame\Models\BattleReport;

/**
 * Snapshot of the hero modifiers applied in a battle, stored with the battle
 * report (key "kalendrath_hero_modifiers" of its "general" JSON, no schema
 * change), so the report keeps the bonuses in force at resolution even if
 * the heroes change later. Displayed by the React report (not in Blade).
 */
class HeroBattleReport
{
    public const string KEY = 'kalendrath_hero_modifiers';

    public function record(int $reportId, BattleEngine $engine): void
    {
        $modifiers = $engine->heroModifiers();
        if ($modifiers === []) {
            return;
        }

        $report = BattleReport::find($reportId);
        if ($report === null) {
            return;
        }

        $report->general = ($report->general ?? []) + [self::KEY => $modifiers];
        $report->save();
    }
}
