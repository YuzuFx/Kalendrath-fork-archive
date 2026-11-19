<?php

namespace OGame\Console\Commands\Items;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Wipes every owned item and the item journal (between alpha and beta, the
 * accounts are reset: decision of 2026-11-19). The catalog is kept; run
 * kalendrath:items:sync afterwards if it changed.
 */
#[Description('Delete all kingdom items and the item journal (catalog kept). Requires --force.')]
#[Signature('kalendrath:items:reset {--force : Required, the deletion cannot be undone}')]
class ResetKingdomItems extends Command
{
    public function handle(): int
    {
        if (!$this->option('force')) {
            $this->error('Refused without --force: this deletes every kingdom item and the item journal.');

            return Command::FAILURE;
        }

        DB::transaction(function (): void {
            DB::table('kingdom_items')->delete();
            DB::table('item_movements')->delete();
            DB::table('item_operations')->delete();
        });

        $this->info('Kingdom items and item journal deleted.');

        return Command::SUCCESS;
    }
}
