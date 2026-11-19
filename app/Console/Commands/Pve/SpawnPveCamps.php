<?php

namespace OGame\Console\Commands\Pve;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use OGame\Models\Planet\Coordinate;
use OGame\Services\Pve\PveCampService;

/**
 * Creates PvE bot camps (config/pve.php). Run once the administrator account
 * exists. Without --at, camps go to free positions outside the players' range.
 */
#[Description('Create PvE bot camps of a type (config/pve.php), at --at=G:S:P or at random free positions.')]
#[Signature('kalendrath:pve:spawn {--type=hostile_camp : Camp type} {--count=1 : Number of camps} {--at= : Coordinate G:S:P (one camp)}')]
class SpawnPveCamps extends Command
{
    public function handle(PveCampService $camps): int
    {
        $type = (string) $this->option('type');
        $at = $this->option('at');
        $coordinate = null;
        if (is_string($at) && $at !== '') {
            $parts = array_map(intval(...), explode(':', $at));
            if (count($parts) !== 3) {
                $this->error('--at must be G:S:P.');

                return Command::FAILURE;
            }
            $coordinate = new Coordinate($parts[0], $parts[1], $parts[2]);
        }

        $count = $coordinate !== null ? 1 : max(1, (int) $this->option('count'));
        for ($i = 0; $i < $count; $i++) {
            $camp = $camps->spawn($type, $coordinate);
            $this->info('Camp ' . $camp->id . ' (' . $type . ') on planet ' . $camp->planet_id . '.');
        }

        return Command::SUCCESS;
    }
}
