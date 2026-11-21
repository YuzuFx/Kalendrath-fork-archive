<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('pve_retaliation_intents', function (Blueprint $table): void {
            $table->id();
            // Keep historical identities if a mission, camp or city is deleted.
            $table->unsignedBigInteger('source_mission_id')->unique();
            $table->unsignedBigInteger('source_battle_report_id');
            $table->unsignedBigInteger('camp_id')->index();
            $table->unsignedBigInteger('target_user_id')->index();
            $table->unsignedBigInteger('target_planet_id');
            $table->unsignedSmallInteger('target_galaxy');
            $table->unsignedSmallInteger('target_system');
            $table->unsignedSmallInteger('target_position');
            // A recorded raid is audit data. It is not an active reservation.
            $table->dateTime('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pve_retaliation_intents');
    }
};
