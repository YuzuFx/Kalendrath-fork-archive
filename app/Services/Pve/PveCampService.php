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
 * the way a player's would: the planet has mines and produces resources, and
 * the bot recruits its missing garrison with what it holds. Camps
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

        $this->initialize($camp);

        return $camp->refresh();
    }

    /**
     * After a battle against a camp: count a won raid. The camp does not
     * come back on a timer: it recruits again with what it produces.
     */
    public function recordBattle(PveCamp $camp, bool $raidWon): void
    {
        if ($raidWon) {
            $camp->raids_won++;
            $camp->save();
        }
    }

    /**
     * Lets every camp produce and recruit (scheduled command, every minute).
     *
     * @return int Number of units recruited.
     */
    public function recruitDue(): int
    {
        $count = 0;
        foreach (PveCamp::all() as $camp) {
            $count += $this->recruit($camp);
        }

        return $count;
    }

    /**
     * Brings the camp's planet up to date (the upstream production of its
     * mines, since the last update), then recruits the missing units of its
     * garrison, in the order of the configuration, as far as its resources
     * go. The cost is the ordinary unit price: a pillaged camp rebuilds
     * slowly, a rich one quickly.
     *
     * @return int Number of units recruited.
     */
    public function recruit(PveCamp $camp): int
    {
        $type = $this->type($camp->camp_type);
        $recruited = 0;

        DB::transaction(function () use ($camp, $type, &$recruited): void {
            Planet::whereKey($camp->planet_id)->lockForUpdate()->firstOrFail();
            $planet = $this->planets->make($camp->planet_id, true);
            if ($planet === null) {
                return;
            }
            $planet->update();

            foreach ((array) $type['garrison'] as $machineName => $target) {
                $missing = (int) $target - $planet->getObjectAmount((string) $machineName);
                if ($missing <= 0) {
                    continue;
                }

                $price = ObjectService::getObjectPrice((string) $machineName, $planet);
                $have = $planet->getResources();
                $affordable = $missing;
                foreach (['metal', 'crystal', 'deuterium'] as $resource) {
                    $unitCost = (int) $price->{$resource}->get();
                    if ($unitCost > 0) {
                        $affordable = min($affordable, intdiv((int) $have->{$resource}->get(), $unitCost));
                    }
                }
                if ($affordable <= 0) {
                    continue;
                }

                $cost = $price->multiply((float) $affordable);
                if ($planet->deductResourcesAtomic($cost)) {
                    $planet = $this->planets->make($camp->planet_id, true) ?? $planet;
                    $planet->addUnit((string) $machineName, $affordable);
                    $recruited += $affordable;
                }
            }
        });

        $this->planets->forgetPlanetCache($camp->planet_id);

        return $recruited;
    }

    /**
     * First setup of a camp: the buildings of its type, the starting stock and
     * the full garrison. After this the camp lives on its own production.
     */
    private function initialize(PveCamp $camp): void
    {
        $type = $this->type($camp->camp_type);

        DB::transaction(function () use ($camp, $type): void {
            $planet = Planet::whereKey($camp->planet_id)->lockForUpdate()->firstOrFail();

            foreach ((array) $type['buildings'] as $machineName => $level) {
                $planet->{$machineName} = (int) $level;
            }
            foreach ((array) $type['garrison'] as $machineName => $amount) {
                $planet->{$machineName} = (int) $amount;
            }
            foreach ((array) $type['start_resources'] as $resource => $amount) {
                $planet->{$resource} = (int) $amount;
            }
            $planet->time_last_update = (int) Date::now()->timestamp;
            $planet->save();
        });

        // The production and storage columns are derived from the buildings.
        $service = $this->planets->make($camp->planet_id, true);
        $service?->updateResourceStorageStats();
        $service?->updateResourceProductionStats();

        $this->planets->forgetPlanetCache($camp->planet_id);
    }

    /**
     * @return array{tier: int, loot_table: string, garrison: array<string, int>, buildings: array<string, int>, start_resources: array<string, int>}
     */
    private function type(string $campType): array
    {
        $type = config('pve.camp_types.' . $campType);
        if (!is_array($type)) {
            throw new RuntimeException('Unknown PvE camp type: ' . $campType);
        }

        /** @var array{tier: int, loot_table: string, garrison: array<string, int>, buildings: array<string, int>, start_resources: array<string, int>} $type */
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
