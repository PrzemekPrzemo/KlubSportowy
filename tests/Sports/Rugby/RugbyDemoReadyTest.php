<?php

namespace Tests\Sports\Rugby;

use App\Sports\Rugby\RugbyArchetype;

class RugbyDemoReadyTest extends \PHPUnit\Framework\TestCase
{
    public function testArchetypeContract(): void
    {
        $a = new RugbyArchetype();
        $this->assertSame('rugby', $a->key());
        $this->assertContains('rugby_teams',   $a->tables());
        $this->assertContains('rugby_matches', $a->tables());
        $this->assertContains('rugby_events',  $a->tables());
        $this->assertTrue($a->isDemoReady());
    }
}
