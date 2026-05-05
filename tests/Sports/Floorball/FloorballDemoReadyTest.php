<?php

namespace Tests\Sports\Floorball;

use App\Sports\Floorball\FloorballArchetype;

class FloorballDemoReadyTest extends \PHPUnit\Framework\TestCase
{
    public function testArchetypeContract(): void
    {
        $a = new FloorballArchetype();
        $this->assertSame('floorball', $a->key());
        $this->assertContains('floorball_teams',   $a->tables());
        $this->assertContains('floorball_matches', $a->tables());
        $this->assertContains('floorball_events',  $a->tables());
        $this->assertTrue($a->isDemoReady());
    }
}
