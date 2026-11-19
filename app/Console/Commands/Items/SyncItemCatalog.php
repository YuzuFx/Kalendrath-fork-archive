<?php

namespace OGame\Console\Commands\Items;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use OGame\Services\Items\ItemCatalogService;

/**
 * Synchronises database/catalog/items.php into item_definitions. To buff or
 * nerf ONE item: edit its entry in the catalog, then run this command with
 * --code=<code>; the kingdoms wearing it get their production recalculated.
 * Run without option at every deployment.
 */
#[Description('Synchronise the item catalog (database/catalog/items.php); --code limits it to a few items (rebalance).')]
#[Signature('kalendrath:items:sync {--code=* : Only these item codes (no retirement)}')]
class SyncItemCatalog extends Command
{
    public function handle(ItemCatalogService $catalog): int
    {
        /** @var list<string> $codes */
        $codes = (array) $this->option('code');
        $report = $catalog->sync($codes === [] ? null : $codes);

        foreach ($report as $status => $itemCodes) {
            $this->info(sprintf('%s: %d%s', $status, count($itemCodes), $itemCodes === [] || $status === 'unchanged' ? '' : ' (' . implode(', ', $itemCodes) . ')'));
        }

        return Command::SUCCESS;
    }
}
