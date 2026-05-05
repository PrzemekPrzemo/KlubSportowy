<?php

namespace Tests\Sports\FieldHockey;

use App\Sports\FieldHockey\FieldHockeyArchetype;

class FieldHockeyDemoReadyTest extends \PHPUnit\Framework\TestCase
{
    public function testArchetypeContract(): void
    {
        $a = new FieldHockeyArchetype();
        $this->assertSame('fieldhockey', $a->key());
        // Tabele uzywaja UNDERSCORE w 'field_hockey' a klucz pluginu jest bez
        $this->assertContains('field_hockey_teams',   $a->tables());
        $this->assertContains('field_hockey_matches', $a->tables());
        $this->assertContains('field_hockey_events',  $a->tables());
        $this->assertTrue($a->isDemoReady());
    }
}
