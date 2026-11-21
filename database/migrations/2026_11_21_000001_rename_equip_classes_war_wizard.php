<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Equipment classes are named after the archetypes (decided 2026-11-21):
 * heavy -> war, arcane -> wizard. Data only; the column is a free string.
 */
return new class () extends Migration {
    public function up(): void
    {
        DB::table('item_definitions')->where('equip_class', 'heavy')->update(['equip_class' => 'war']);
        DB::table('item_definitions')->where('equip_class', 'arcane')->update(['equip_class' => 'wizard']);
    }

    public function down(): void
    {
        DB::table('item_definitions')->where('equip_class', 'war')->update(['equip_class' => 'heavy']);
        DB::table('item_definitions')->where('equip_class', 'wizard')->update(['equip_class' => 'arcane']);
    }
};
