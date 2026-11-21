<?php

namespace OGame\Console\Commands\Pve;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use OGame\Services\Pve\PveCampService;

/**
 *  * has come (scheduled every minute in routes/console.php).
 */
#[Description('Let the PvE camps produce and recruit their missing garrison.')]
#[Signature('kalendrath:pve:respawn')]
class RespawnPveCamps extends Command
{
    public function handle(PveCampService $camps): int
    {
        $count = $camps->recruitDue();
        if ($count > 0) {
            $this->info($count . ' unit(s) recruited by the PvE camps.');
        }

        return Command::SUCCESS;
    }
}
