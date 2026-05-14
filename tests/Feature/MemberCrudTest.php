<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\Encryption;
use App\Models\MemberModel;

/**
 * Feature: CRUD na członkach klubu + multi-tenant isolation.
 *
 * Pokrywa:
 *  - insert członka przez MemberModel z auto-encryption pól wrażliwych (PESEL, email, phone)
 *  - decrypt-on-read przez findById
 *  - hash dla wyszukiwania (deterministyczny SHA-256)
 *  - club_id scoping: member z klubu B niewidoczny w kontekście klubu A
 */
class MemberCrudTest extends FeatureTestCase
{
    public function testInsertMemberThroughModelPersistsRow(): void
    {
        $clubId = $this->createClub('CRUD Test Club');
        $this->asClub($clubId);

        $model = new MemberModel();
        $id = $model->insert([
            'member_number' => 'CRUD-001',
            'first_name'    => 'Anna',
            'last_name'     => 'Testowa',
            'join_date'     => date('Y-m-d'),
            'status'        => 'aktywny',
        ]);

        $this->assertGreaterThan(0, $id);

        $stmt = $this->pdo->prepare('SELECT * FROM members WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row, 'Row musi być zapisany w DB');
        $this->assertSame($clubId, (int)$row['club_id'], 'club_id musi być automatycznie ustawiony przez ClubScopedModel');
        $this->assertSame('Anna', $row['first_name']);
    }

    public function testEncryptedFieldsArePersistedEncryptedAndReadDecrypted(): void
    {
        if (!Encryption::isConfigured()) {
            $this->markTestSkipped('Encryption key not configured');
        }

        $clubId = $this->createClub('Encrypt Member Club');
        $this->asClub($clubId);

        $model = new MemberModel();
        $id = $model->insert([
            'member_number' => 'ENC-001',
            'first_name'    => 'Piotr',
            'last_name'     => 'Szyfrowany',
            'email'         => 'piotr.szyfrowany@example.test',
            'phone'         => '+48555111222',
            'join_date'     => date('Y-m-d'),
        ]);

        // Bezpośredni odczyt z DB — pole `email` musi być ENC (≠ plaintext).
        $stmt = $this->pdo->prepare('SELECT email, phone, email_hash, phone_hash FROM members WHERE id = ?');
        $stmt->execute([$id]);
        $raw = $stmt->fetch();
        $this->assertNotFalse($raw);
        $this->assertNotSame('piotr.szyfrowany@example.test', $raw['email'], 'email w DB musi być zaszyfrowany');
        $this->assertNotEmpty($raw['email_hash'], 'email_hash musi być wypełniony (do search)');
        $this->assertSame(Encryption::hash('piotr.szyfrowany@example.test'), $raw['email_hash']);

        // findById deszyfruje.
        $row = $model->findById($id);
        $this->assertNotNull($row);
        $this->assertSame('piotr.szyfrowany@example.test', $row['email']);
        $this->assertSame('+48555111222', $row['phone']);
    }

    public function testClubScopedFindByIdReturnsNullForForeignClub(): void
    {
        $clubA = $this->createClub('Tenant A');
        $clubB = $this->createClub('Tenant B');

        // Member tworzony w klubie B
        $this->asClub($clubB);
        $model  = new MemberModel();
        $idB = $model->insert([
            'member_number' => 'B-' . bin2hex(random_bytes(3)),
            'first_name'    => 'Bob',
            'last_name'     => 'B',
            'join_date'     => date('Y-m-d'),
        ]);

        // Przełącz kontekst na klub A → nie powinien zobaczyć member-a klubu B
        $this->asClub($clubA);
        $modelA = new MemberModel();
        $this->assertNull(
            $modelA->findById($idB),
            'Klub A nie może odczytać member-a klubu B (cross-tenant isolation)'
        );

        // findAll w kontekście A nie zawiera idB
        $idsA = array_column($modelA->findAll(), 'id');
        $this->assertNotContains($idB, $idsA, 'findAll() w klubie A nie może zawierać member-a klubu B');
    }

    public function testFindAllScopedToActiveClub(): void
    {
        $clubA = $this->createClub('Scope A');
        $clubB = $this->createClub('Scope B');

        $this->createMember($clubA, 'A1');
        $this->createMember($clubA, 'A2');
        $this->createMember($clubB, 'B1');

        $this->asClub($clubA);
        $rows = (new MemberModel())->findAll();
        $names = array_column($rows, 'first_name');

        $this->assertContains('A1', $names);
        $this->assertContains('A2', $names);
        $this->assertNotContains('B1', $names, 'Lista members klubu A nie może zawierać members klubu B');
    }

    public function testUpdateMemberKeepsClubIsolation(): void
    {
        $clubA = $this->createClub('Update Scope A');
        $clubB = $this->createClub('Update Scope B');

        $idA = $this->createMember($clubA, 'OrigA', 'Last');

        // W kontekście klubu B próbuj zaktualizować member klubu A — nie powinno się udać.
        $this->asClub($clubB);
        $modelB = new MemberModel();
        $modelB->update($idA, ['last_name' => 'HACKED']);

        // Sprawdź czy klub A widzi nadal "Last" (nie HACKED)
        $stmt = $this->pdo->prepare('SELECT last_name FROM members WHERE id = ?');
        $stmt->execute([$idA]);
        $name = $stmt->fetchColumn();
        $this->assertSame('Last', $name, 'Update z cudzego klubu nie może zmienić danych');
    }
}
