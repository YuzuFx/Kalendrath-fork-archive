<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A camp no longer comes back on a timer (decision of 2026-11-21): it produces
 * like a player and recruits its missing garrison with its own resources.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('pve_camps', function (Blueprint $table) {
            $table->dropIndex(['respawn_at']);
            $table->dropColumn('respawn_at');
        });
    }

    public function down(): void
    {
        Schema::table('pve_camps', function (Blueprint $table) {
            $table->timestamp('respawn_at')->nullable()->index();
        });
    }
};
