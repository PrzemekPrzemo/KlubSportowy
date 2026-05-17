<?php

namespace Tests\Integration;

use App\Models\ChatMessageModel;
use App\Models\MessageThreadModel;

/**
 * @group integration
 *
 * Smoke coverage for /api/v1/messages/* — exercises the new chat tables
 * (message_threads / chat_messages) via the model layer directly to avoid
 * dragging in the HTTP stack.
 */
class MessagesApiTest extends TestCase
{
    private int $clubId;
    private int $aliceId;
    private int $bobId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clubId = $this->createTestClub('Messages Test Club');
        $alice = $this->createTestMember($this->clubId, ['first_name' => 'Alice', 'last_name' => 'A']);
        $bob   = $this->createTestMember($this->clubId, ['first_name' => 'Bob',   'last_name' => 'B']);
        $this->aliceId = (int)$alice['id'];
        $this->bobId   = (int)$bob['id'];
    }

    public function testFindDirectBetweenReturnsNullBeforeAnyMessage(): void
    {
        $threads = new MessageThreadModel();
        $this->assertNull(
            $threads->findDirectBetween($this->aliceId, $this->bobId, $this->clubId)
        );
    }

    public function testCreateDirectAddsBothParticipants(): void
    {
        $threads = new MessageThreadModel();
        $tid = $threads->createDirect($this->aliceId, $this->bobId, $this->clubId);
        $this->assertGreaterThan(0, $tid);

        $this->assertTrue($threads->isParticipant($tid, $this->aliceId, $this->clubId));
        $this->assertTrue($threads->isParticipant($tid, $this->bobId, $this->clubId));

        $found = $threads->findDirectBetween($this->bobId, $this->aliceId, $this->clubId);
        $this->assertNotNull($found, 'Direct lookup must be symmetric (Bob→Alice == Alice→Bob)');
        $this->assertSame($tid, (int)$found['id']);
    }

    public function testSendMessageBumpsLastMessageAtAndUnreadForPeer(): void
    {
        $threads = new MessageThreadModel();
        $messages = new ChatMessageModel();

        $tid = $threads->createDirect($this->aliceId, $this->bobId, $this->clubId);
        $messages->send($tid, $this->aliceId, $this->clubId, 'Hej Bob!');

        // Forces the touch on last_message_at in createDirect path? It's done by
        // send(). Refresh and check.
        $list = $threads->forMember($this->bobId, $this->clubId);
        $this->assertNotEmpty($list);
        $row = $list[0];
        $this->assertSame('Hej Bob!', $row['last_body']);
        $this->assertSame((int)$row['unread_count'], 1, 'Bob has one unread message from Alice');

        // Alice (sender) has zero unread in the same thread.
        $aliceList = $threads->forMember($this->aliceId, $this->clubId);
        $this->assertSame(0, (int)$aliceList[0]['unread_count']);
    }

    public function testMarkReadResetsUnreadCount(): void
    {
        $threads = new MessageThreadModel();
        $messages = new ChatMessageModel();

        $tid = $threads->createDirect($this->aliceId, $this->bobId, $this->clubId);
        $messages->send($tid, $this->aliceId, $this->clubId, 'm1');
        $m2 = $messages->send($tid, $this->aliceId, $this->clubId, 'm2');

        $bobBefore = $threads->forMember($this->bobId, $this->clubId);
        $this->assertSame(2, (int)$bobBefore[0]['unread_count']);

        $threads->markRead($tid, $this->bobId, $m2);

        $bobAfter = $threads->forMember($this->bobId, $this->clubId);
        $this->assertSame(0, (int)$bobAfter[0]['unread_count']);
    }

    public function testTotalUnreadForMemberAggregatesAcrossThreads(): void
    {
        $threads = new MessageThreadModel();
        $messages = new ChatMessageModel();

        $charlie = $this->createTestMember($this->clubId, ['first_name' => 'Charlie', 'last_name' => 'C']);
        $charlieId = (int)$charlie['id'];

        $tidA = $threads->createDirect($this->aliceId, $this->bobId,    $this->clubId);
        $tidB = $threads->createDirect($this->aliceId, $charlieId,      $this->clubId);

        $messages->send($tidA, $this->bobId,  $this->clubId, 'from bob 1');
        $messages->send($tidA, $this->bobId,  $this->clubId, 'from bob 2');
        $messages->send($tidB, $charlieId,    $this->clubId, 'from charlie');

        // totalUnreadForMember returns SUM of unread messages across all threads.
        $this->assertSame(3, (int)$threads->totalUnreadForMember($this->aliceId, $this->clubId));
    }
}
