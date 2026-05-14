<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\Encryption;
use App\Models\ClubShippingProviderModel;
use App\Models\ShipmentModel;

/**
 * Feature: per-klub konfiguracja shipping providera (InPost).
 *
 *  - upsert szyfruje api_token + organization_id (kolumny `_enc`)
 *  - findByProvider deszyfruje do logical names
 *  - ClubScopedModel: config klubu A niewidoczny dla klubu B
 *  - ShipmentModel: insert + recentForClub, izolacja per-klub
 */
class ShippingTest extends FeatureTestCase
{
    public function testShippingProviderUpsertEncryptsAndFindDecrypts(): void
    {
        if (!Encryption::isConfigured()) {
            $this->markTestSkipped('Encryption not configured');
        }

        $clubId = $this->createClub('Ship Encrypt Club');
        $this->asClub($clubId);

        $model = new ClubShippingProviderModel();
        $id = $model->upsert('inpost', [
            'is_active'        => 1,
            'is_sandbox'       => 1,
            'api_token'        => 'inpost_token_secret_abc',
            'organization_id'  => 'org_xyz_123',
            'default_size'     => 'A',
            'default_service'  => 'inpost_locker_standard',
            'sender_name'      => 'Klub Sportowy XYZ',
            'sender_email'     => 'biuro@klubxyz.test',
            'sender_phone'     => '+48500111222',
            'sender_address_street'    => 'Sportowa',
            'sender_address_building'  => '10',
            'sender_address_city'      => 'Warszawa',
            'sender_address_post_code' => '00-001',
        ]);
        $this->assertGreaterThan(0, $id);

        // RAW DB: kolumny `_enc` muszą zawierać szyfrogram, nie plain text.
        $stmt = $this->pdo->prepare('SELECT api_token_enc, organization_id_enc FROM club_shipping_providers WHERE id = ?');
        $stmt->execute([$id]);
        $raw = $stmt->fetch();
        $this->assertNotFalse($raw);
        $this->assertNotSame('inpost_token_secret_abc', $raw['api_token_enc'], 'api_token musi być zaszyfrowany');
        $this->assertNotSame('org_xyz_123', $raw['organization_id_enc']);

        // Decrypt-on-read.
        $row = $model->findByProvider('inpost');
        $this->assertNotNull($row);
        $this->assertSame('inpost_token_secret_abc', $row['api_token']);
        $this->assertSame('org_xyz_123', $row['organization_id']);
        $this->assertSame('Klub Sportowy XYZ', $row['sender_name']);
    }

    public function testShippingProviderIsolatedAcrossClubs(): void
    {
        if (!Encryption::isConfigured()) {
            $this->markTestSkipped('Encryption not configured');
        }

        $clubA = $this->createClub('Ship Tenant A');
        $clubB = $this->createClub('Ship Tenant B');

        $this->asClub($clubA);
        (new ClubShippingProviderModel())->upsert('inpost', [
            'is_active' => 1,
            'api_token' => 'tokenA',
            'organization_id' => 'orgA',
        ]);

        $this->asClub($clubB);
        $this->assertNull(
            (new ClubShippingProviderModel())->findByProvider('inpost'),
            'Klub B nie może widzieć config klubu A'
        );
    }

    public function testShipmentInsertAndListForClub(): void
    {
        $clubA = $this->createClub('Shipment Club A');
        $clubB = $this->createClub('Shipment Club B');

        $this->asClub($clubA);
        $model = new ShipmentModel();
        $id1 = $model->insert([
            'provider'        => 'inpost',
            'external_id'     => 'EXT-A-1',
            'tracking_number' => '111122223333',
            'recipient_name'  => 'Adam Odbiorca',
            'recipient_email' => 'adam@example.test',
            'status'          => 'created',
        ]);
        $id2 = $model->insert([
            'provider'        => 'inpost',
            'external_id'     => 'EXT-A-2',
            'recipient_name'  => 'Beata',
            'status'          => 'sent',
        ]);
        $this->assertGreaterThan(0, $id1);
        $this->assertGreaterThan(0, $id2);

        // Shipment do klubu B
        $this->asClub($clubB);
        $idB = (new ShipmentModel())->insert([
            'provider' => 'inpost',
            'external_id' => 'EXT-B-1',
            'recipient_name' => 'Foreign',
            'status' => 'created',
        ]);

        // Lista w klubie A nie powinna zawierać shipmentów z B.
        $this->asClub($clubA);
        $recentA = (new ShipmentModel())->recentForClub(10);
        $idsA = array_column($recentA, 'id');
        $this->assertContains($id1, $idsA);
        $this->assertContains($id2, $idsA);
        $this->assertNotContains($idB, $idsA, 'Klub A nie może widzieć shipment-ów klubu B');
    }

    public function testFindByExternalIdScopedToClub(): void
    {
        $clubA = $this->createClub('XID Club A');
        $clubB = $this->createClub('XID Club B');

        $this->asClub($clubA);
        (new ShipmentModel())->insert([
            'provider' => 'inpost',
            'external_id' => 'XID-SHARED-001',
            'recipient_name' => 'A',
            'status' => 'created',
        ]);

        // Ten sam external_id w klubie B
        $this->asClub($clubB);
        (new ShipmentModel())->insert([
            'provider' => 'inpost',
            'external_id' => 'XID-SHARED-001',
            'recipient_name' => 'B',
            'status' => 'created',
        ]);

        // findByExternalId musi zwrócić wyłącznie shipment aktywnego klubu.
        $rowB = (new ShipmentModel())->findByExternalId('XID-SHARED-001');
        $this->assertNotNull($rowB);
        $this->assertSame('B', $rowB['recipient_name']);

        $this->asClub($clubA);
        $rowA = (new ShipmentModel())->findByExternalId('XID-SHARED-001');
        $this->assertNotNull($rowA);
        $this->assertSame('A', $rowA['recipient_name']);
    }
}
