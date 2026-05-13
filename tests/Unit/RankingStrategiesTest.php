<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Ranking\BestTimeStrategy;
use App\Helpers\Ranking\EloStrategy;
use App\Helpers\Ranking\LeaguePointsStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Pure-function testy strategii rankingowych. Bez DB.
 */
class RankingStrategiesTest extends TestCase
{
    // ────────────────────────────── Elo ──────────────────────────────

    public function testEloEqualRatingsWinnerGainsSixteen(): void
    {
        $strategy = new EloStrategy();
        $participants = [
            ['member_id' => 1, 'finishPlace' => 1],
            ['member_id' => 2, 'finishPlace' => 2],
        ];
        $current = [
            ['member_id' => 1, 'points' => 1000, 'games_played' => 0, 'wins' => 0],
            ['member_id' => 2, 'points' => 1000, 'games_played' => 0, 'wins' => 0],
        ];
        $result = $strategy->recalculate($participants, $current, []);
        $byId = [];
        foreach ($result as $r) $byId[$r['member_id']] = $r;

        // K=32, expected=0.5, S_winner=1 → delta = 32*(1-0.5) = 16.
        $this->assertSame(1016, $byId[1]['new_points']);
        $this->assertSame(984,  $byId[2]['new_points']);
        $this->assertSame(16,   $byId[1]['delta']);
        $this->assertSame(-16,  $byId[2]['delta']);
        $this->assertTrue($byId[1]['is_win']);
        $this->assertFalse($byId[2]['is_win']);
    }

    public function testEloUnknownPlayerSeedsAtDefault(): void
    {
        $strategy = new EloStrategy();
        $participants = [
            ['member_id' => 10, 'finishPlace' => 1],
            ['member_id' => 11, 'finishPlace' => 2],
        ];
        $result = $strategy->recalculate($participants, [], []);
        $this->assertCount(2, $result);
        $byId = [];
        foreach ($result as $r) $byId[$r['member_id']] = $r;
        $this->assertSame(1016, $byId[10]['new_points']);
        $this->assertSame(984,  $byId[11]['new_points']);
    }

    // ───────────────────────── LeaguePoints ─────────────────────────

    public function testLeaguePointsThreeWinnersGetThreePointsEach(): void
    {
        $strategy = new LeaguePointsStrategy();
        $participants = [
            ['member_id' => 1, 'result' => 'win'],
            ['member_id' => 2, 'result' => 'win'],
            ['member_id' => 3, 'result' => 'win'],
        ];
        $result = $strategy->recalculate($participants, [], []);
        $this->assertCount(3, $result);
        foreach ($result as $r) {
            $this->assertSame(3, $r['delta']);
            $this->assertSame(3, $r['new_points']);
            $this->assertSame(1, $r['games_played']);
            $this->assertTrue($r['is_win']);
        }
    }

    public function testLeaguePointsDrawAndCustomContext(): void
    {
        $strategy = new LeaguePointsStrategy();
        $participants = [
            ['member_id' => 1, 'result' => 'draw'],
            ['member_id' => 2, 'result' => 'draw'],
        ];
        $result = $strategy->recalculate(
            $participants,
            [['member_id' => 1, 'points' => 10, 'games_played' => 4, 'wins' => 3]],
            ['points_per_draw' => 2]
        );
        $byId = [];
        foreach ($result as $r) $byId[$r['member_id']] = $r;
        $this->assertSame(12, $byId[1]['new_points']);
        $this->assertSame(2,  $byId[1]['delta']);
        $this->assertSame(5,  $byId[1]['games_played']);
        $this->assertFalse($byId[1]['is_win']);
    }

    // ─────────────────────────── BestTime ───────────────────────────

    public function testBestTimeTopThreeFromTimes(): void
    {
        $strategy = new BestTimeStrategy();
        $participants = [
            ['member_id' => 1, 'time' => 60.0],
            ['member_id' => 2, 'time' => 58.5], // najszybszy
            ['member_id' => 3, 'time' => 59.2],
        ];
        $result = $strategy->recalculate($participants, [], []);
        $byId = [];
        foreach ($result as $r) $byId[$r['member_id']] = $r;

        // 1st (member 2) = 10, 2nd (member 3) = 8, 3rd (member 1) = 6.
        $this->assertSame(10, $byId[2]['new_points']);
        $this->assertSame(8,  $byId[3]['new_points']);
        $this->assertSame(6,  $byId[1]['new_points']);
        $this->assertTrue($byId[2]['is_win']);
        $this->assertFalse($byId[3]['is_win']);
    }

    public function testBestTimeFromFinishPlaceFallback(): void
    {
        $strategy = new BestTimeStrategy();
        $participants = [
            ['member_id' => 1, 'finishPlace' => 1],
            ['member_id' => 2, 'finishPlace' => 2],
            ['member_id' => 3, 'finishPlace' => 3],
            ['member_id' => 4, 'finishPlace' => 11], // poza tabelą → 0 pkt
        ];
        $result = $strategy->recalculate($participants, [], []);
        $byId = [];
        foreach ($result as $r) $byId[$r['member_id']] = $r;
        $this->assertSame(10, $byId[1]['new_points']);
        $this->assertSame(8,  $byId[2]['new_points']);
        $this->assertSame(6,  $byId[3]['new_points']);
        $this->assertSame(0,  $byId[4]['new_points']);
    }

    public function testBestTimeAddsToExistingPoints(): void
    {
        $strategy = new BestTimeStrategy();
        $participants = [['member_id' => 1, 'finishPlace' => 1]];
        $current = [['member_id' => 1, 'points' => 20, 'games_played' => 3, 'wins' => 1]];
        $result = $strategy->recalculate($participants, $current, []);
        $this->assertSame(30, $result[0]['new_points']);
        $this->assertSame(10, $result[0]['delta']);
        $this->assertSame(4,  $result[0]['games_played']);
        $this->assertSame(2,  $result[0]['wins']);
    }

    // ─────────────────────── Strategy keys ──────────────────────────

    public function testStrategyKeys(): void
    {
        $this->assertSame('elo',           (new EloStrategy())->key());
        $this->assertSame('league_points', (new LeaguePointsStrategy())->key());
        $this->assertSame('best_time',     (new BestTimeStrategy())->key());
    }
}
