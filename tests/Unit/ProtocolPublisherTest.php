<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Pdf\TournamentProtocolPdf;
use App\Helpers\Tournaments\ProtocolPublisher;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Smoke tests dla ProtocolPublisher.
 *
 * Pelny test (publish do DB + zapis pliku) wymaga MySQL + ROOT_PATH writable
 * + mPDF — to bedzie pokryte przez Integration suite. Tutaj testujemy:
 *   - HTML PDF generuje sie bez bledow (smoke generacji)
 *   - Slug walidacja (regex z controllera) jest poprawna
 *   - buildRelPath ma poprawny format
 *   - klasa istnieje i ma publiczne metody publish/republish/setSharing
 */
class ProtocolPublisherTest extends TestCase
{
    public function testClassHasRequiredPublicApi(): void
    {
        $rc = new ReflectionClass(ProtocolPublisher::class);
        $this->assertTrue($rc->hasMethod('publish'));
        $this->assertTrue($rc->hasMethod('republish'));
        $this->assertTrue($rc->hasMethod('setSharing'));
        $this->assertTrue($rc->getMethod('publish')->isPublic());
        $this->assertTrue($rc->getMethod('republish')->isPublic());
        $this->assertTrue($rc->getMethod('setSharing')->isPublic());
    }

    public function testTournamentProtocolPdfGeneratesSmoke(): void
    {
        // ProtocolPublisher pod spodem zawsze wola TournamentProtocolPdf::generate
        // — sprawdzmy ze generator nie wybucha na minimalnym payloadzie i ze
        // produkuje walid HTML (z table + headera).
        $html = TournamentProtocolPdf::generate([
            'tournament' => [
                'id'         => 42,
                'name'       => 'Mistrzostwa Warszawy BJJ 2026',
                'sport_key'  => 'bjj',
                'date_start' => '2026-05-17',
                'status'     => 'finished',
            ],
            'participants' => [
                ['member_id' => 1, 'first_name' => 'Jan',  'last_name' => 'Kowalski', 'place' => 1],
                ['member_id' => 2, 'first_name' => 'Anna', 'last_name' => 'Nowak',    'place' => 2],
            ],
            'matches' => [
                [
                    'round' => 1, 'match_number' => 1,
                    'player1_id' => 1, 'player2_id' => 2,
                    'player1_name' => 'Kowalski Jan',
                    'player2_name' => 'Nowak Anna',
                    'winner_id' => 1, 'score1' => '10', 'score2' => '0',
                ],
            ],
            'sport'         => ['key' => 'bjj', 'name' => 'BJJ'],
            'club_header'   => '<div>Klub</div>',
            'system_footer' => '<div>Footer</div>',
        ]);
        $this->assertIsString($html);
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('Protokół turnieju', $html);
        $this->assertStringContainsString('Mistrzostwa Warszawy', $html);
        $this->assertStringContainsString('Kowalski Jan', $html);
    }

    /**
     * Slug regex zgodny ze spec: ^[a-z0-9-]{1,80}$
     * Ta sama walidacja co w kontrolerze publicznym.
     */
    public function testSlugRegexAcceptsValidSlugs(): void
    {
        $valid = [
            'puchar-warszawy-abc123',
            'liga-2026-2027-deadbe',
            'a',
            str_repeat('a', 80),
        ];
        foreach ($valid as $s) {
            $this->assertMatchesRegularExpression('/^[a-z0-9-]{1,80}$/', $s, "Powinien byc walid: {$s}");
        }
    }

    public function testSlugRegexRejectsInvalidSlugs(): void
    {
        $invalid = [
            '',                                  // empty
            str_repeat('a', 81),                 // >80
            'UPPERCASE',                         // wielkie litery
            'with_underscore',                   // underscore
            'with space',                        // spacja
            'polskie-żółć',                      // unicode
            '../../etc/passwd',                  // path traversal
        ];
        foreach ($invalid as $s) {
            $this->assertDoesNotMatchRegularExpression('/^[a-z0-9-]{1,80}$/', $s, "Powinien byc invalid: {$s}");
        }
    }

    public function testRelPathFormatMatchesSpec(): void
    {
        // Wzor: storage/tournament_protocols/{club_id}/{tournament_id}_v{version}.pdf
        // Wywolanie prywatne — reflexja, zeby uniknac DB.
        $publisher = $this->getMockBuilder(ProtocolPublisher::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $rc = new ReflectionClass(ProtocolPublisher::class);
        $method = $rc->getMethod('buildRelPath');
        $method->setAccessible(true);

        $path = $method->invoke($publisher, 7, 42, 3);
        $this->assertSame('storage/tournament_protocols/7/42_v3.pdf', $path);

        $this->assertStringStartsWith('storage/tournament_protocols/', $path);
        $this->assertStringNotContainsString('public', $path, 'PDF NIE moze byc w /public/');
    }

    public function testBuildShareUrlContainsProtocolsPath(): void
    {
        $publisher = $this->getMockBuilder(ProtocolPublisher::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $rc = new ReflectionClass(ProtocolPublisher::class);
        $method = $rc->getMethod('buildShareUrl');
        $method->setAccessible(true);

        $url = $method->invoke($publisher, 'puchar-warszawy-abc123');
        $this->assertStringContainsString('/protocols/puchar-warszawy-abc123', $url);
    }
}
