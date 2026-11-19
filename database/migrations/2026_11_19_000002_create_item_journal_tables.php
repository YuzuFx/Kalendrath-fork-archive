<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Item journal: one operation per inventory change (unique idempotency key),
 * detailed by append-only movement lines. Deliberately no foreign key towards
 * users, heroes or kingdom_items: the journal must survive their deletion.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::create('item_operations', function (Blueprint $table) {
            $table->id();
            // ULID, correlation id towards other ledgers (Kaelite, trade currency).
            $table->char('uid', 26)->charset('ascii')->collation('ascii_bin')->unique();
            // Same key twice = a single effect.
            $table->string('idempotency_key', 191)->charset('ascii')->collation('ascii_bin')->unique();
            // Same key replayed with a different payload = conflict error.
            $table->char('payload_hash', 64)->charset('ascii')->collation('ascii_bin');
            // ItemOperationType.
            $table->string('type', 32)->charset('ascii')->collation('ascii_bin');
            // Kingdom concerned (users.id), no foreign key on purpose.
            $table->unsignedInteger('subject_user_id');
            // Player or administrator; NULL = system.
            $table->unsignedInteger('actor_user_id')->nullable();
            $table->string('server_key', 64);
            $table->string('source_type', 32)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('source_ref', 64)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_user_id', 'type', 'created_at']);
        });

        Schema::create('item_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_operation_id')->constrained('item_operations')->restrictOnDelete();
            // NULL for a future stack movement (components, consumables).
            $table->unsignedBigInteger('kingdom_item_id')->nullable();
            // Copy of the item ULID: readable after a merge or a purge.
            $table->char('item_uid', 26)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->unsignedBigInteger('item_definition_id');
            // Signed quantity (1 for a unique item).
            $table->integer('quantity');
            $table->unsignedInteger('from_owner_user_id')->nullable();
            $table->unsignedInteger('to_owner_user_id')->nullable();
            $table->string('from_state', 16)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('to_state', 16)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->unsignedBigInteger('from_hero_id')->nullable();
            $table->unsignedBigInteger('to_hero_id')->nullable();
            $table->string('from_slot', 32)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->string('to_slot', 32)->charset('ascii')->collation('ascii_bin')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['kingdom_item_id', 'id']);
            $table->index(['to_owner_user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_movements');
        Schema::dropIfExists('item_operations');
    }
};
