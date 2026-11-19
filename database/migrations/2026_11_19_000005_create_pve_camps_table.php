<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PvE bot camps (GDD 5.8A, alpha lot 2): a camp is an ordinary planet owned by
 * the PvE bot account, so the upstream attack mission and battle engines work
 * unchanged. This Kalendrath table only says which planets are camps, of which
 * type, and when their garrison comes back. No upstream table is touched.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('pve_camps', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('planet_id')->unique();
            $table->foreign('planet_id')->references('id')->on('planets')->cascadeOnDelete();
            $table->string('camp_type', 32)->charset('ascii')->collation('ascii_bin');
            $table->unsignedTinyInteger('tier')->default(1);
            $table->string('loot_table_code', 64)->charset('ascii')->collation('ascii_bin');
            // Set after a battle against the camp; the garrison is restocked once due.
            $table->timestamp('respawn_at')->nullable()->index();
            $table->unsignedInteger('raids_won')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pve_camps');
    }
};
