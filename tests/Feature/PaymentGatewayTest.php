<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\Encryption;
use App\Models\ClubPaymentGatewayModel;

/**
 * Feature: per-klub konfiguracja bramki płatności (Przelewy24/PayU/Stripe/tpay).
 *
 *  - upsert szyfruje api_key/api_secret/crc_key/webhook_secret przed zapisem
 *  - findByProvider deszyfruje z powrotem do plain text
 *  - listForClub zwraca pole `api_key_masked` jako '••••' (nie plain)
 *  - ClubScopedModel filter: klub A nie widzi gateway klubu B
 */
class PaymentGatewayTest extends FeatureTestCase
{
    public function testUpsertEncryptsCredentialsAndFindDecrypts(): void
    {
        if (!Encryption::isConfigured()) {
            $this->markTestSkipped('Encryption not configured');
        }

        $clubId = $this->createClub('PG Encrypt Club');
        $this->asClub($clubId);

        $model = new ClubPaymentGatewayModel();
        $id = $model->upsert('przelewy24', [
            'is_active'   => 1,
            'is_sandbox'  => 1,
            'merchant_id' => '123456',
            'api_key'     => 'super_secret_api_key_value',
            'api_secret'  => 'extra_secret_value',
            'crc_key'     => 'crc_key_secret',
            'currency'    => 'PLN',
        ]);
        $this->assertGreaterThan(0, $id);

        // Raw DB — api_key musi być ENC.
        $stmt = $this->pdo->prepare('SELECT api_key, api_secret, crc_key FROM club_payment_gateways WHERE id = ?');
        $stmt->execute([$id]);
        $raw = $stmt->fetch();
        $this->assertNotFalse($raw);
        $this->assertNotSame('super_secret_api_key_value', $raw['api_key'], 'api_key w DB musi być zaszyfrowany');
        $this->assertNotSame('extra_secret_value', $raw['api_secret']);
        $this->assertNotSame('crc_key_secret', $raw['crc_key']);

        // Odczyt przez findByProvider → decrypted.
        $row = $model->findByProvider('przelewy24');
        $this->assertNotNull($row);
        $this->assertSame('super_secret_api_key_value', $row['api_key']);
        $this->assertSame('extra_secret_value', $row['api_secret']);
        $this->assertSame('crc_key_secret', $row['crc_key']);
    }

    public function testListForClubMasksApiKey(): void
    {
        if (!Encryption::isConfigured()) {
            $this->markTestSkipped('Encryption not configured');
        }

        $clubId = $this->createClub('PG Masking Club');
        $this->asClub($clubId);

        $model = new ClubPaymentGatewayModel();
        $model->upsert('payu', [
            'is_active'   => 1,
            'merchant_id' => '777',
            'api_key'     => 'should_be_masked',
            'api_secret'  => 'should_also_be_secret',
            'currency'    => 'PLN',
        ]);

        $list = $model->listForClub();
        $this->assertNotEmpty($list);
        $first = $list[0];

        $this->assertArrayHasKey('api_key_masked', $first);
        $this->assertSame('••••', $first['api_key_masked'], 'Lista musi maskować api_key — NIGDY plain text');

        // I nie powinno być pól plain api_key/api_secret w liście.
        $this->assertArrayNotHasKey('api_key', $first, 'Pole api_key NIE może być w listForClub');
        $this->assertArrayNotHasKey('api_secret', $first, 'Pole api_secret NIE może być w listForClub');
    }

    public function testGatewayIsolatedAcrossClubs(): void
    {
        if (!Encryption::isConfigured()) {
            $this->markTestSkipped('Encryption not configured');
        }

        $clubA = $this->createClub('PG Tenant A');
        $clubB = $this->createClub('PG Tenant B');

        // Klub A konfiguruje stripe
        $this->asClub($clubA);
        $modelA = new ClubPaymentGatewayModel();
        $modelA->upsert('stripe', [
            'is_active'   => 1,
            'merchant_id' => 'acct_A',
            'api_key'     => 'sk_test_clubA',
            'currency'    => 'PLN',
        ]);

        // Klub B nie powinien widzieć stripe-config klubu A.
        $this->asClub($clubB);
        $modelB = new ClubPaymentGatewayModel();
        $this->assertNull(
            $modelB->findByProvider('stripe'),
            'Klub B nie może widzieć stripe config klubu A (multi-tenant)'
        );

        $listB = $modelB->listForClub();
        $this->assertEmpty($listB, 'Lista bramek klubu B musi być pusta');
    }

    public function testUpsertDoesNotOverwriteSecretsWhenLeftEmpty(): void
    {
        if (!Encryption::isConfigured()) {
            $this->markTestSkipped('Encryption not configured');
        }

        $clubId = $this->createClub('PG Preserve Secrets');
        $this->asClub($clubId);
        $model = new ClubPaymentGatewayModel();

        $model->upsert('tpay', [
            'is_active'   => 1,
            'merchant_id' => 'M1',
            'api_key'     => 'original_key_v1',
            'currency'    => 'PLN',
        ]);

        // Druga zmiana: tylko merchant_id, api_key puste — stare ma zostać.
        $model->upsert('tpay', [
            'is_active'   => 1,
            'merchant_id' => 'M1_updated',
            'api_key'     => '',
            'currency'    => 'PLN',
        ]);

        $row = $model->findByProvider('tpay');
        $this->assertNotNull($row);
        $this->assertSame('M1_updated', $row['merchant_id']);
        $this->assertSame('original_key_v1', $row['api_key'], 'Pusty api_key NIE może wyzerować poprzedniego secretu');
    }
}
