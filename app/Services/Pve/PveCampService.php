<?php

namespace OGame\Services\Pve;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OGame\Factories\PlanetServiceFactory;
use OGame\Factories\PlayerServiceFactory;
use OGame\Models\Enums\PlanetType;
use OGame\Models\Planet;
use OGame\Models\Planet\Coordinate;
use OGame\Models\PveCamp;
use OGame\Models\User;
use OGame\Models\UserTech;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;
use RuntimeException;

/**
 * PvE bot camps (GDD 5.8A, alpha lot 2). A camp is an ordinary planet owned
 * by the single PvE bot account: players spy on it and attack it with the
 * upstream missions and battle engines, nothing in the combat knows it is a
 * bot. Its garrison and lootable stock come from config/pve.php and come back
 * some time after a battle (respawn, divided by the economy speed). Camps
 * depend neither on a war system nor on the War Mode.
 */
class PveCampService
{
    public function __construct(
        private readonly PlayerServiceFactory $players,
        private readonly PlanetServiceFactory $planets,
        private readonly SettingsService $settings,
    ) {
    }

    /**
     * The camp on this planet, if any.
     */
    public function campForPlanet(int $planetId): PveCamp|null
    {
        return PveCamp::where('planet_id', $planetId)->first();
    }

    /**
     * The camp at these coordinates, if any (attack target check).
     */
    public function campAt(Coordinate $coordinate, PlanetType $type): PveCamp|null
    {
        if ($type !== PlanetType::Planet) {
            return null;
        }

        $planetId = Planet::where('galaxy', $coordinate->galaxy)
            ->where('system', $coordinate->system)
            ->where('planet', $coordinate->position)
            ->where('planet_type', PlanetType::Planet->value)
            ->value('id');

        return $planetId !== null ? $this->campForPlanet((int) $planetId) : null;
    }

    /**
     * The PvE bot account, created on first use: no hero, no login (random
     * password, reserved .invalid address), combat research from the config.
     * Created without model events so it never takes the "first user is
     * admin" promotion of the upstream User model.
     */
    public function botAccount(): User
    {
        $email = (string) config('pve.bot_account.email');
        $existing = User::where('email', $email)->first();
        if ($existing !== null) {
            return $existing;
        }

        if (!User::where('email', '!=', $email)->where('username', '!=', 'Legor')->exists()) {
            // The upstream promotes the first non-Legor account to admin: the
            // bot would make the first real player a plain player.
            throw new RuntimeException('Create the administrator account before spawning PvE camps.');
        }

        $user = User::withoutEvents(function () use ($email): User {
            $user = new User();
            $user->username = (string) config('pve.bot_account.username');
            $user->email = $email;
            $user->password = Hash::make(Str::random(48));
            $user->lang = 'fr';
            $user->time = (string) Date::now()->timestamp;
            $user->save();

            return $user;
        });

        $tech = new UserTech();
        $tech->user_id = $user->id;
        foreach ((array) config('pve.bot_account.research') as $research => $level) {
            $tech->{$research} = (int) $level;
        }
        $tech->save();

        return $user;
    }

    /**
     * Creates a camp of the given type, at the given coordinate or at a free
     * random position outside the players' allocator range, fully stocked.
     */
    public function spawn(string $campType, Coordinate|null $coordinate = null): PveCamp
    {
        $type = $this->type($campType);
        $bot = $this->botAccount();
        $player = $this->players->make($bot->id, true);

        $coordinate ??= $this->freeCoordinate();
        $planet = $this->planets->createPlanetAtPosition($player, $coordinate, __('t_pve.camp.' . $campType . '.name'));

        $camp = PveCamp::create([
            'planet_id' => $planet->getPlanetId(),
            'camp_type' => $campType,
            'tier' => (int) $type['tier'],
            'loot_table_code' => (string) $type['loot_table'],
        ]);

        $this->restock($camp);

        return $camp->refresh();
    }

    /**
     * After a battle against a camp: count a won raid and plan the return
     * of the garrison (once, the first battle sets the timer).
     */
    public function recordBattle(PveCamp $camp, bool $raidWon): void
    {
        if ($raidWon) {
            $camp->raids_won++;
        }
        if ($camp->respawn_at === null) {
            $hours = (float) $this->type($camp->camp_type)['respawn_hours'];
            $seconds = (int) round($hours * 3600 / max(1, $this->settings->economySpeed()));
            $camp->respawn_at = Date::now()->addSeconds($seconds);
        }
        $camp->save();
    }

    /**
     * Restocks the camps whose respawn time has come (scheduled command).
     *
     * @return int Number of camps restocked.
     */
    public function respawnDue(): int
    {
        $count = 0;
        foreach (PveCamp::whereNotNull('respawn_at')->where('respawn_at', '<=', Date::now())->get() as $camp) {
            $this->restock($camp);
            $count++;
        }

        return $count;
    }

    /**
     * Sets the camp's garrison and lootable stock back to its type (absolute
     * values, so a replay is harmless) and clears the respawn timer.
     */
    public function restock(PveCamp $camp): void
    {
        $type = $this->type($camp->camp_type);

        DB::transaction(function () use ($camp, $type): void {
            $planet = Planet::whereKey($camp->planet_id)->lockForUpdate()->firstOrFail();

            // Every combat unit to zero, then the configured garrison.
            foreach (ObjectService::getUnitObjects() as $unit) {
                if (array_key_exists($unit->machine_name, $planet->getAttributes())) {
                    $planet->{$unit->machine_name} = 0;
                }
            }
            foreach ((array) $type['garrison'] as $machineName => $amount) {
                $planet->{$machineName} = (int) $amount;
            }
            foreach ((array) $type['resources'] as $resource => $amount) {
                $planet->{$resource} = (int) $amount;
            }
            $planet->time_last_update = (int) Date::now()->timestamp;
            $planet->save();

            $camp->respawn_at = null;
            $camp->save();
        });

        $this->planets->forgetPlanetCache($camp->planet_id);
    }

    /**
     * @return array{tier: int, loot_table: string, garrison: array<string, int>, resources: array<string, int>, respawn_hours: int|float}
     */
    private function type(string $campType): array
    {
        $type = config('pve.camp_types.' . $campType);
        if (!is_array($type)) {
            throw new RuntimeException('Unknown PvE camp type: ' . $campType);
        }

        /** @var array{tier: int, loot_table: string, garrison: array<string, int>, resources: array<string, int>, respawn_hours: int|float} $type */
        return $type;
    }

    private function freeCoordinate(): Coordinate
    {
        $positions = array_map(intval(...), (array) config('pve.spawn_positions'));
        for ($try = 0; $try < 200; $try++) {
            $coordinate = new Coordinate(
                random_int(1, $this->settings->numberOfGalaxies()),
                random_int(1, 499),
                $positions[array_rand($positions)],
            );
            if (!$this->planets->planetExistsAtCoordinate($coordinate)) {
                return $coordinate;
            }
        }

        throw new RuntimeException('No free position found for a PvE camp.');
    }
}
