<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per kingdom, used only as a stable row lock (SELECT ... FOR UPDATE)
 * that serialises the inventory paths of a kingdom: PvE launch, loot grant,
 * discard, equip, unequip, purge (OGame\Services\Items\KingdomLock). A
 * dedicated row rather than the users row, which upstream locks in other
 * orders (PlayerService::update, HalvingService, DarkMatterService).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('kingdom_locks', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->primary();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kingdom_locks');
    }
};
