<?php

namespace Tests\Sports\IceHockey;

use App\Sports\IceHockey\IceHockeyArchetype;

class IceHockeyDemoReadyTest extends \PHPUnit\Framework\TestCase
{
    public function testArchetypeContract(): void
    {
        $a = new IceHockeyArchetype();
        $this->assertSame('icehockey', $a->key());
        $this->assertContains('icehockey_teams',   $a->tables());
        $this->assertContains('icehockey_matches', $a->tables());
        $this->assertContains('icehockey_events',  $a->tables());
        $this->assertTrue($a->isDemoReady());
    }
}
