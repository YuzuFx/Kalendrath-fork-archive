<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Items owned by a kingdom (users row), worn by a hero of the same kingdom.
 * Invariants are enforced by the database (CHECK, composite foreign key,
 * unique position per hero), not only by the services.
 */
return new class () extends Migration {
    public function up(): void
    {
        // Target of the composite key (holder_hero_id, owner_user_id): heroes is our table.
        Schema::table('heroes', function (Blueprint $table) {
            $table->unique(['id', 'user_id'], 'heroes_id_user_id_unique');
        });

        Schema::create('kingdom_items', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 26)->charset('ascii')->collation('ascii_bin')->unique();
            $table->foreignId('item_definition_id')->constrained('item_definitions')->restrictOnDelete();
            // Catalog version when the item was created (audit of rebalances).
            $table->unsignedInteger('definition_version');
            // The kingdom (users.id is an INT UNSIGNED increments).
            $table->unsignedInteger('owner_user_id');
            $table->foreign('owner_user_id')->references('id')->on('users')->restrictOnDelete();
            // KingdomItemState.
            $table->string('state', 16)->charset('ascii')->collation('ascii_bin')->default('available');
            // Hero or advisor wearing the item, and the position (EquipmentSlot).
            $table->unsignedBigInteger('holder_hero_id')->nullable();
            $table->string('equipped_slot', 32)->charset('ascii')->collation('ascii_bin')->nullable();
            // Created but empty in the alpha: market listing, crafting ingredient, convoy.
            $table->string('reservation_type', 32)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->unsignedBigInteger('reservation_id')->nullable();
            // Created but empty in the alpha: NULL = no wear. Filtered by the effects query.
            $table->unsignedInteger('durability')->nullable();
            // Provenance, captured at birth and never rebuilt.
            $table->string('source_type', 32)->charset('ascii')->collation('ascii_bin');
            $table->string('source_ref', 64)->nullable();
            $table->string('origin_server_key', 64);
            // Plain index, no foreign key: the journal may be purged or archived.
            $table->unsignedBigInteger('created_operation_id');
            $table->timestamp('acquired_at');
            $table->timestamps();

            $table->unique(['holder_hero_id', 'equipped_slot'], 'kingdom_items_holder_slot_unique');
            $table->index(['owner_user_id', 'state']);
            $table->index('item_definition_id');
            $table->index(['source_type', 'source_ref']);
            $table->index('created_operation_id');

            // A hero only wears items of its own kingdom; a hero still wearing
            // something cannot be deleted (unequip first). InnoDB skips the
            // check when holder_hero_id is NULL.
            $table->foreign(['holder_hero_id', 'owner_user_id'], 'kingdom_items_holder_same_kingdom_fk')
                ->references(['id', 'user_id'])->on('heroes')->restrictOnDelete();
        });

        $checks = [
            'chk_kingdom_items_state' => "state IN ('available','equipped','reserved','in_transit','consumed','destroyed')",
            'chk_kingdom_items_equipped' => "(state = 'equipped') = (holder_hero_id IS NOT NULL AND equipped_slot IS NOT NULL)",
            'chk_kingdom_items_holder_slot' => '(holder_hero_id IS NULL) = (equipped_slot IS NULL)',
            'chk_kingdom_items_reservation_pair' => '(reservation_type IS NULL) = (reservation_id IS NULL)',
            'chk_kingdom_items_reserved' => "(state = 'reserved') = (reservation_type IS NOT NULL)",
            'chk_kingdom_items_equipped_not_reserved' => "state <> 'equipped' OR reservation_type IS NULL",
        ];
        foreach ($checks as $name => $expression) {
            DB::statement("ALTER TABLE kingdom_items ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kingdom_items');

        Schema::table('heroes', function (Blueprint $table) {
            $table->dropUnique('heroes_id_user_id_unique');
        });
    }
};
