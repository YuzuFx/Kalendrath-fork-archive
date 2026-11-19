<?php

namespace Tests\Feature\Items;

use OGame\Enums\HeroArchetype;
use OGame\Enums\ItemOperationType;
use OGame\Enums\ItemSourceType;
use OGame\Enums\KingdomItemState;
use OGame\Exceptions\HeroRuleException;
use OGame\Exceptions\ItemIdempotencyConflictException;
use OGame\Exceptions\ItemRuleException;
use OGame\Factories\PlayerServiceFactory;
use OGame\Models\Hero;
use OGame\Models\ItemMovement;
use OGame\Models\ItemOperation;
use OGame\Models\KingdomItem;
use OGame\Models\User;
use OGame\Services\Hero\HeroEquipmentService;
use OGame\Services\Hero\HeroRosterService;
use OGame\Services\Items\KingdomItemService;
use Tests\Feature\Hero\HeroTestCase;

class KingdomItemServiceTest extends HeroTestCase
{
    use ItemFixtures;

    private function service(): KingdomItemService
    {
        return resolve(KingdomItemService::class);
    }

    public function testGrantCreatesAnAvailableItemWithItsProvenanceAndJournal(): void
    {
        config(['server.key' => 'alpha-1']);
        $definition = $this->definition('test_helm');

        $item = $this->give($this->user->id, $definition, 'test:grant');

        $this->assertSame(KingdomItemState::AVAILABLE, $item->state);
        $this->assertSame(26, strlen($item->uid));
        $this->assertSame(ItemSourceType::ADMIN, $item->source_type);
        $this->assertSame('alpha-1', $item->origin_server_key);
        $this->assertSame($definition->version, $item->definition_version);

        $operation = ItemOperation::where('idempotency_key', 'test:grant')->firstOrFail();
        $this->assertSame(ItemOperationType::ADMIN_GRANT, $operation->type);
        $this->assertSame($operation->id, $item->created_operation_id);
        $this->assertSame('alpha-1', $operation->server_key);
        $this->assertSame(1, ItemMovement::where('kingdom_item_id', $item->id)->count());
    }

    public function testGrantIsIdempotent(): void
    {
        $definition = $this->definition('test_helm');

        $first = $this->give($this->user->id, $definition, 'test:grant');
        $second = $this->give($this->user->id, $definition, 'test:grant');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, KingdomItem::where('owner_user_id', $this->user->id)->count());
    }

    public function testSameKeyWithAnotherPayloadIsAConflict(): void
    {
        $this->give($this->user->id, $this->definition('test_helm'), 'test:grant');

        $this->expectException(ItemIdempotencyConflictException::class);
        $this->give($this->user->id, $this->definition('test_boots', 'boots'), 'test:grant');
    }

    public function testDiscardDestroysLogicallyAndJournals(): void
    {
        $item = $this->give($this->user->id, $this->definition('test_helm'));

        $item = $this->service()->discard($this->user->id, $item, 'test:discard');

        $this->assertSame(KingdomItemState::DESTROYED, $item->state);
        $this->assertNotNull(KingdomItem::find($item->id));
        $movement = ItemMovement::where('kingdom_item_id', $item->id)->orderByDesc('id')->firstOrFail();
        $this->assertSame('available', $movement->from_state);
        $this->assertSame('destroyed', $movement->to_state);
        $this->assertSame(0, $this->service()->bagCount($this->user->id));
    }

    public function testDiscardIsIdempotent(): void
    {
        $item = $this->give($this->user->id, $this->definition('test_helm'));

        $this->service()->discard($this->user->id, $item, 'test:discard');
        $this->service()->discard($this->user->id, $item, 'test:discard');

        $this->assertSame(1, ItemOperation::where('idempotency_key', 'test:discard')->count());
        $this->assertSame(1, ItemMovement::where('kingdom_item_id', $item->id)->where('to_state', 'destroyed')->count());
    }

    public function testDestroyedIsTerminalAndTheRowIsKept(): void
    {
        $item = $this->give($this->user->id, $this->definition('test_helm'));
        $this->service()->discard($this->user->id, $item, 'test:discard');

        // A second discard with another key, or an equip, is refused: no way out of destroyed.
        try {
            $this->service()->discard($this->user->id, $item, 'test:discard:again');
            $this->fail('A destroyed item was discarded again.');
        } catch (ItemRuleException) {
            $this->addToAssertionCount(1);
        }
        try {
            resolve(HeroEquipmentService::class)->equip($this->hero, $item->refresh());
            $this->fail('A destroyed item was equipped.');
        } catch (HeroRuleException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(KingdomItemState::DESTROYED, $item->refresh()->state);
        $this->assertSame(0, ItemOperation::where('idempotency_key', 'test:discard:again')->count());
    }

    public function testTheUlidLivesInUidAndTheKeyStaysAnAutoIncrementedId(): void
    {
        $item = $this->give($this->user->id, $this->definition('test_helm'));

        $this->assertTrue($item->getIncrementing());
        $this->assertSame('int', $item->getKeyType());
        $this->assertMatchesRegularExpression('/^[0-9a-hjkmnp-tv-z]{26}$/', $item->uid);
        $operation = ItemOperation::findOrFail($item->created_operation_id);
        $this->assertTrue($operation->getIncrementing());
        $this->assertSame(26, strlen($operation->uid));
    }

    public function testAWornItemCannotBeDiscarded(): void
    {
        $item = resolve(HeroEquipmentService::class)->equip($this->hero, $this->give($this->user->id, $this->definition('test_helm')));

        $this->expectException(ItemRuleException::class);
        $this->service()->discard($this->user->id, $item, 'test:discard');
    }

    public function testAReservedItemCannotBeDiscarded(): void
    {
        $item = $this->give($this->user->id, $this->definition('test_helm'));
        KingdomItem::whereKey($item->id)->update(['state' => 'reserved', 'reservation_type' => 'market', 'reservation_id' => 1]);

        try {
            $this->service()->discard($this->user->id, $item, 'test:discard');
            $this->fail('A reserved item was discarded.');
        } catch (ItemRuleException) {
            $this->assertSame(KingdomItemState::RESERVED, $item->refresh()->state);
            // The refused request left no operation behind.
            $this->assertSame(0, ItemOperation::where('idempotency_key', 'test:discard')->count());
        }
    }

    public function testAnItemOfAnotherKingdomCannotBeDiscarded(): void
    {
        $other = User::factory()->create();
        $item = $this->give($other->id, $this->definition('test_helm'));

        $this->expectException(ItemRuleException::class);
        $this->service()->discard($this->user->id, $item, 'test:discard');
    }

    public function testBagCountsAvailableItemsButNotWornOnes(): void
    {
        config(['items.inventory.capacity' => 2]);
        $definition = $this->definition('test_helm');
        resolve(HeroEquipmentService::class)->equip($this->hero, $this->give($this->user->id, $definition));
        $this->give($this->user->id, $definition);

        $this->assertSame(1, $this->service()->bagCount($this->user->id));
        $this->assertFalse($this->service()->isBagFull($this->user->id));

        $this->give($this->user->id, $definition);
        $this->assertTrue($this->service()->isBagFull($this->user->id));
        $this->assertCount(3, $this->service()->inventory($this->user->id));
    }

    public function testDefaultBagCapacityIs32(): void
    {
        $this->assertSame(32, $this->service()->bagCapacity());
    }

    public function testDeletingAPlayerPurgesItsHeroesAndItems(): void
    {
        $definition = $this->definition('test_helm');
        $advisor = resolve(HeroRosterService::class)->recruitAdvisor($this->user, HeroArchetype::WARRIOR, 'Squire');
        resolve(HeroEquipmentService::class)->equip($advisor, $this->give($this->user->id, $definition));
        $this->give($this->user->id, $definition);

        resolve(PlayerServiceFactory::class)->make($this->user->id, true)->delete();

        $this->assertNull(User::find($this->user->id));
        $this->assertSame(0, Hero::where('user_id', $this->user->id)->count());
        $this->assertSame(0, KingdomItem::where('owner_user_id', $this->user->id)->count());
        // The journal survives the purge.
        $this->assertGreaterThan(0, ItemOperation::where('subject_user_id', $this->user->id)->count());
    }
}
