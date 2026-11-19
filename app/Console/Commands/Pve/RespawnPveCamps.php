<?php

namespace OGame\Console\Commands\Pve;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use OGame\Services\Pve\PveCampService;

/**
 * Brings back the garrison and lootable stock of the camps whose respawn time
 * has come (scheduled every minute in routes/console.php).
 */
#[Description('Restock the PvE camps whose respawn time has come.')]
#[Signature('kalendrath:pve:respawn')]
class RespawnPveCamps extends Command
{
    public function handle(PveCampService $camps): int
    {
        $count = $camps->respawnDue();
        if ($count > 0) {
            $this->info($count . ' PvE camp(s) restocked.');
        }

        return Command::SUCCESS;
    }
}
