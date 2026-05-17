<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Static audit dla EsportMemberProfileModel + portal/sport_esport view.
 *
 * Sprawdza:
 *   - EsportMemberProfileModel rozszerza ClubScopedModel (multi-tenant).
 *   - upsertProfile dla dedup: ten sam (member_id, game_code) => UPDATE, nie INSERT.
 *   - listForMember filtruje WHERE club_id = ? (isolation).
 *   - leaderboard ma LIMIT i clamp 1..200 (no DoS).
 *   - View portal/sport_esport.php zawiera form z csrf_field, leaderboard, modal.
 *   - MemberPortalController::sportDetail ma case 'esport'.
 */
class EsportMemberProfileTest extends TestCase
{
    private string $modelFile;
    private string $viewFile;
    private string $controllerFile;

    protected function setUp(): void
    {
        $this->modelFile      = __DIR__ . '/../../app/Sports/Esport/Models/EsportMemberProfileModel.php';
        $this->viewFile       = __DIR__ . '/../../app/Views/portal/sport_esport.php';
        $this->controllerFile = __DIR__ . '/../../app/Controllers/MemberPortalController.php';
    }

    public function testModelExists(): void
    {
        $this->assertFileExists($this->modelFile);
    }

    public function testModelExtendsClubScopedModel(): void
    {
        require_once $this->modelFile;
        $rc = new ReflectionClass(\App\Sports\Esport\Models\EsportMemberProfileModel::class);
        $this->assertSame(
            \App\Models\ClubScopedModel::class,
            $rc->getParentClass()->getName(),
            'EsportMemberProfileModel MUSI rozszerzac ClubScopedModel — wymagane do multi-tenant isolation.'
        );
    }

    public function testListForMemberFiltersByClubId(): void
    {
        $src = file_get_contents($this->modelFile);
        $this->assertNotFalse($src);
        $this->assertMatchesRegularExpression(
            '/listForMember.*?WHERE.*?member_id\s*=\s*\?\s+AND\s+club_id\s*=\s*\?/s',
            $src,
            'listForMember MUSI filtrowac WHERE club_id = ? aby zapobiec cross-tenant leakage.'
        );
    }

    public function testLeaderboardFiltersByClubAndGame(): void
    {
        $src = file_get_contents($this->modelFile);
        $this->assertNotFalse($src);
        $this->assertMatchesRegularExpression(
            '/leaderboard.*?WHERE.*?club_id\s*=\s*\?\s+AND\s+game_code\s*=\s*\?/s',
            $src,
            'leaderboard MUSI filtrowac WHERE club_id = ? AND game_code = ?.'
        );
        // Limit clamp musi byc 1..200
        $this->assertMatchesRegularExpression(
            '/\$limit\s*=\s*max\(\s*1\s*,\s*min\(\s*200\s*,\s*\$limit\s*\)\s*\)/',
            $src,
            'leaderboard MUSI clampowac limit do 1..200 (anty-DoS).'
        );
    }

    public function testUpsertProfileDedupes(): void
    {
        $src = file_get_contents($this->modelFile);
        $this->assertNotFalse($src);
        // upsertProfile wola findForMemberGame zanim INSERT — to dedup logic
        $this->assertStringContainsString('findForMemberGame', $src);
        $this->assertMatchesRegularExpression(
            '/upsertProfile.*?findForMemberGame.*?\$this->update/s',
            $src,
            'upsertProfile MUSI sprawdzic istniejacy profil i UPDATE zamiast INSERT (dedup per [member_id, game_code]).'
        );
    }

    public function testPortalViewExists(): void
    {
        $this->assertFileExists($this->viewFile);
    }

    public function testPortalViewHasRequiredElements(): void
    {
        $viewSrc = file_get_contents($this->viewFile);
        $this->assertNotFalse($viewSrc);
        $this->assertStringContainsString('csrf_field()', $viewSrc,
            'Modal form MUSI miec csrf_field() — POST do save profile.');
        $this->assertStringContainsStringIgnoringCase('leaderboard', $viewSrc,
            'View MUSI miec sekcje leaderboard.');
        $this->assertStringContainsStringIgnoringCase('Twitch', $viewSrc,
            'View MUSI miec przycisk Twitch live dla stream_url.');
        $this->assertStringContainsString('portal/esport/profiles/save', $viewSrc,
            'Form MUSI POST do /portal/esport/profiles/save.');
    }

    public function testSportDetailHasEsportCase(): void
    {
        $src = file_get_contents($this->controllerFile);
        $this->assertNotFalse($src);
        $this->assertStringContainsString("case 'esport':", $src,
            'MemberPortalController::sportDetail() MUSI miec case esport.');
        $this->assertStringContainsString("portal/sport_esport", $src,
            'sportDetail MUSI renderowac widok portal/sport_esport.');
    }

    public function testSportDetailSetsClubContextForEsport(): void
    {
        $src = file_get_contents($this->controllerFile);
        $this->assertNotFalse($src);
        // ClubContext::set jest wymagane do dzialania ClubScopedModel-i w esport case
        $this->assertMatchesRegularExpression(
            '/case\s+\'esport\'.*?ClubContext::set/s',
            $src,
            'case esport MUSI ustawic ClubContext::set(clubId) zanim wywola model.'
        );
    }
}
