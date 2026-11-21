<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        // Replace the previous default while retaining other server settings.
        DB::table('settings')->where('key', 'expedition_weight_items')->where('value', '5')
            ->update(['value' => '25', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'expedition_weight_items')->where('value', '25')
            ->update(['value' => '5', 'updated_at' => now()]);
    }
};
