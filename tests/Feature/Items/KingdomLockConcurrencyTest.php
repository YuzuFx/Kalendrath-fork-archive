<?php

namespace Tests\Feature\Items;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use OGame\Enums\ItemOperationType;
use OGame\Models\ItemOperation;
use OGame\Models\User;
use OGame\Services\Items\ItemLedger;
use OGame\Services\Items\KingdomLock;
use OGame\Services\Items\PveLaunchGuard;
use PDOException;
use Tests\TestCase;

/**
 * Basic concurrency proof with two real database connections: while one
 * transaction holds a kingdom's lock, every inventory path of that kingdom
 * (journal operations: loot, discard, equip, unequip; PvE launch) waits, and
 * another kingdom is not blocked. Not wrapped in a test transaction on
 * purpose: the second connection must see committed rows only.
 */
class KingdomLockConcurrencyTest extends TestCase
{
    private Connection $other;

    private int $userId;

    private int $otherUserId;

    protected function setUp(): void
    {
        parent::setUp();

        $ids = User::query()->orderBy('id')->limit(2)->pluck('id')->all();
        if (count($ids) < 2) {
            $this->markTestSkipped('Needs two committed accounts in the database.');
        }
        [$this->userId, $this->otherUserId] = array_map(intval(...), $ids);

        $default = (string) config('database.default');
        config(['database.connections.kingdom_lock_other' => config('database.connections.' . $default)]);
        $this->other = DB::connection('kingdom_lock_other');

        // Fail fast instead of waiting 50 s on the lock.
        DB::statement('SET SESSION innodb_lock_wait_timeout = 1');
    }

    protected function tearDown(): void
    {
        if ($this->other->transactionLevel() > 0) {
            $this->other->rollBack();
        }
        $this->other->disconnect();
        DB::statement('SET SESSION innodb_lock_wait_timeout = 50');

        parent::tearDown();
    }

    /**
     * The other connection takes the kingdom lock and keeps its transaction open.
     */
    private function holdLockElsewhere(int $userId): void
    {
        $this->other->beginTransaction();
        $this->other->table('kingdom_locks')->insertOrIgnore(['user_id' => $userId]);
        $this->other->table('kingdom_locks')->where('user_id', $userId)->lockForUpdate()->value('user_id');
    }

    private function assertWaitsForTheLock(callable $path): void
    {
        try {
            DB::transaction(fn () => $path());
            $this->fail('The path did not wait for the kingdom lock.');
        } catch (PDOException $e) {
            // 1205: Lock wait timeout exceeded (Laravel may wrap it in a DeadlockException).
            $this->assertStringContainsString('1205', $e->getMessage());
        }
    }

    public function testInventoryPathsOfAKingdomWaitWhileAnotherTransactionHoldsItsLock(): void
    {
        $this->holdLockElsewhere($this->userId);

        // Kingdom lock itself, a journal operation (every loot, discard,
        // equip and unequip goes through it), and a PvE launch.
        $this->assertWaitsForTheLock(fn () => resolve(KingdomLock::class)->acquire($this->userId));
        $key = 'test:concurrency:' . Str::ulid();
        $this->assertWaitsForTheLock(fn () => resolve(ItemLedger::class)->run($key, ItemOperationType::ADMIN_GRANT, $this->userId, [], function (): void {
        }));
        $this->assertSame(0, ItemOperation::where('idempotency_key', $key)->count());
        $this->assertWaitsForTheLock(fn () => resolve(PveLaunchGuard::class)->launch($this->userId, fn () => null));

        // Another kingdom is not blocked.
        DB::transaction(fn () => resolve(KingdomLock::class)->acquire($this->otherUserId));

        // Once the first transaction ends, the kingdom is free again.
        $this->other->rollBack();
        DB::transaction(fn () => resolve(KingdomLock::class)->acquire($this->userId));
        $this->addToAssertionCount(1);
    }

    public function testTheLockMustBeTakenInsideATransaction(): void
    {
        $this->expectException(LogicException::class);

        resolve(KingdomLock::class)->acquire($this->userId);
    }
}
