<?php

namespace Tests\Integration;

use App\Helpers\ClubContext;
use App\Helpers\Database;
use App\Models\MemberModel;

/**
 * @group integration
 *
 * Faza J — security regression test: ClubScopedModel.update() egzekwuje
 * scope club_id, zapobiega IDOR (cross-club update).
 *
 * Klub A i klub B maja po jednym czlonku. Klub A ustawiony jako kontekst
 * (ClubContext::setCurrent) probuje update'owac czlonka klubu B przez
 * MemberModel::update(). Update musi NIE zmodyfikowac wiersza klubu B.
 */
class ClubScopedUpdateIsolationTest extends TestCase
{
    public function testCannotUpdateMemberFromOtherClub(): void
    {
        $db = $this->requireDatabase();

        $clubA = $this->createTestClub('Klub A — IDOR test');
        $clubB = $this->createTestClub('Klub B — IDOR test');

        // Wstaw 2 czlonkow — kazdy w swoim klubie
        $memberA = $this->createTestMember($clubA, ['first_name' => 'Anna', 'last_name' => 'Aklub']);
        $memberB = $this->createTestMember($clubB, ['first_name' => 'Beata', 'last_name' => 'Bklub']);

        // Ustaw kontekst na klub A i sprobuj zaktualizowac czlonka klubu B
        ClubContext::setCurrent($clubA);
        $model = new MemberModel();
        $result = $model->update($memberB['id'], ['first_name' => 'HACKED']);

        // PDO::execute() zwraca true nawet gdy 0 wierszy zmodyfikowanych
        // — sprawdzamy stan w DB
        $stmt = $db->prepare("SELECT first_name FROM members WHERE id = ?");
        $stmt->execute([$memberB['id']]);
        $row = $stmt->fetch();

        $this->assertSame(
            'Beata',
            $row['first_name'],
            'IDOR: czlonek klubu B zostal zmodyfikowany przez kontekst klubu A'
        );
    }

    public function testCanUpdateOwnClubMember(): void
    {
        $db = $this->requireDatabase();

        $clubA = $this->createTestClub('Klub A — happy path');
        $memberA = $this->createTestMember($clubA, ['first_name' => 'Anna']);

        ClubContext::setCurrent($clubA);
        $model = new MemberModel();
        $model->update($memberA['id'], ['first_name' => 'Aniela']);

        $stmt = $db->prepare("SELECT first_name FROM members WHERE id = ?");
        $stmt->execute([$memberA['id']]);
        $this->assertSame('Aniela', $stmt->fetchColumn());
    }

    public function testUpdateIgnoresClubIdInData(): void
    {
        $db = $this->requireDatabase();

        $clubA = $this->createTestClub('Klub A — club_id ignore');
        $clubB = $this->createTestClub('Klub B — club_id ignore');
        $memberA = $this->createTestMember($clubA, ['first_name' => 'Anna']);

        ClubContext::setCurrent($clubA);
        $model = new MemberModel();
        // Zlosliwa proba "przeniesienia" do klubu B
        $model->update($memberA['id'], [
            'first_name' => 'Aniela',
            'club_id'    => $clubB,
        ]);

        $stmt = $db->prepare("SELECT first_name, club_id FROM members WHERE id = ?");
        $stmt->execute([$memberA['id']]);
        $row = $stmt->fetch();
        $this->assertSame('Aniela', $row['first_name'], 'first_name powinien byc zaktualizowany');
        $this->assertSame($clubA, (int)$row['club_id'], 'club_id NIE powinien byc zmieniony');
    }
}
