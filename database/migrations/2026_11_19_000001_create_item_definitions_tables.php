<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Item catalog (docs/archives/retours-agents-2026-11-19-schema-donnees-objets.md
 * §2.1 corrected by U2): definitions identified by a stable machine code,
 * effects stored as rows. Additive only: no upstream OGameX table is touched.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('item_definitions', function (Blueprint $table) {
            $table->id();
            // Stable machine name, identical on every server (merge-safe).
            $table->string('code', 64)->charset('ascii')->collation('ascii_bin')->unique();
            // ItemKind: only "equipment" in the alpha.
            $table->string('kind', 32)->charset('ascii')->collation('ascii_bin');
            // EquipmentSlotType (11 types); NULL outside equipment.
            $table->string('slot_type', 32)->charset('ascii')->collation('ascii_bin')->nullable();
            // EquipClass; archetype -> classes mapping lives in config/heroes.php.
            $table->string('equip_class', 32)->charset('ascii')->collation('ascii_bin')->default('any');
            $table->unsignedTinyInteger('rarity')->default(1);
            $table->unsignedTinyInteger('required_level')->default(1);
            // Bumped by the catalog sync whenever content_hash changes.
            $table->unsignedInteger('version')->default(1);
            $table->char('content_hash', 64);
            // Retired definitions leave the loot tables but owned items stay valid.
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();

            $table->index(['kind', 'retired_at']);
            $table->index(['slot_type', 'rarity', 'required_level']);
        });

        DB::statement("ALTER TABLE item_definitions ADD CONSTRAINT chk_item_definitions_equipment_slot CHECK (kind <> 'equipment' OR slot_type IS NOT NULL)");
        DB::statement('ALTER TABLE item_definitions ADD CONSTRAINT chk_item_definitions_rarity CHECK (rarity BETWEEN 1 AND 5)');

        Schema::create('item_definition_effects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_definition_id')->constrained('item_definitions')->cascadeOnDelete();
            // Same vocabulary as talents and config/heroes.php axis_caps; the
            // unit is in the name (..._percent), hence no value_mode column.
            $table->string('effect_type', 64)->charset('ascii')->collation('ascii_bin');
            // TalentEffectScope: hero, city, group, empire.
            $table->string('effect_scope', 16)->charset('ascii')->collation('ascii_bin');
            // Optional target (metal, crystal, deuterium, unit machine name...);
            // '*' means the whole axis. Never NULL, so the unique index works.
            $table->string('target', 64)->charset('ascii')->collation('ascii_bin')->default('*');
            $table->decimal('value', 8, 2);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['item_definition_id', 'effect_type', 'effect_scope', 'target'], 'item_definition_effects_unique');
            $table->index('effect_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_definition_effects');
        Schema::dropIfExists('item_definitions');
    }
};
