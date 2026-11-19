<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A governor loses a destroyed city, it does not retain it: without this the
 * purge of a destroyed planet governed by a hero fails (note U6).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->dropForeign(['planet_id']);
            $table->foreign('planet_id')->references('id')->on('planets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            $table->dropForeign(['planet_id']);
            $table->foreign('planet_id')->references('id')->on('planets');
        });
    }
};
