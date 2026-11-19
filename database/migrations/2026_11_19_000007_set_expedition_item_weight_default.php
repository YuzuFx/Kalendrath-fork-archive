<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kalendrath: the expedition outcome "item" was seeded at weight 0 upstream
 * (unreachable). Provisional cautious default: 5, about 1 expedition in 20
 * with the other default weights (sum about 100). Still editable in the
 * administration (server settings); a value already changed there is kept.
 */
return new class () extends Migration {
    private const string KEY = 'expedition_weight_items';

    private const string DEFAULT = '5';

    public function up(): void
    {
        $current = DB::table('settings')->where('key', self::KEY)->value('value');
        if ($current === null) {
            DB::table('settings')->insert(['key' => self::KEY, 'value' => self::DEFAULT, 'created_at' => now(), 'updated_at' => now()]);
        } elseif ((float) $current === 0.0) {
            DB::table('settings')->where('key', self::KEY)->update(['value' => self::DEFAULT, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', self::KEY)->where('value', self::DEFAULT)->update(['value' => '0', 'updated_at' => now()]);
    }
};
