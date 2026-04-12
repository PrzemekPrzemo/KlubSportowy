<?php

namespace Tests\Integration;

use App\Models\ApiKeyModel;

/**
 * @group integration
 *
 * Integration tests for API key generation, authentication, and scope checking.
 */
class ApiAuthTest extends TestCase
{
    private int $clubId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clubId = $this->createTestClub('ApiAuth Test Club');
    }

    // ------------------------------------------------------------------
    // generate
    // ------------------------------------------------------------------

    public function testApiKeyModelGenerate(): void
    {
        $model  = new ApiKeyModel();
        $result = $model->generate($this->clubId, 'Test Key', ['members:read']);

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('raw_key', $result);
        $this->assertGreaterThan(0, $result['id']);
        $this->createdApiKeyIds[] = $result['id'];

        // Raw key starts with prefix 'ks_'
        $this->assertStringStartsWith('ks_', $result['raw_key']);

        // Verify the stored prefix matches raw key prefix
        $db   = $this->requireDatabase();
        $stmt = $db->prepare("SELECT key_prefix, key_hash FROM api_keys WHERE id = ?");
        $stmt->execute([$result['id']]);
        $row = $stmt->fetch();

        $this->assertNotNull($row);
        $this->assertEquals(substr($result['raw_key'], 0, 10), $row['key_prefix']);
        // Hash should be a valid bcrypt hash
        $this->assertStringStartsWith('$2y$', $row['key_hash']);
    }

    // ------------------------------------------------------------------
    // authenticate
    // ------------------------------------------------------------------

    public function testApiKeyAuthenticate(): void
    {
        $model  = new ApiKeyModel();
        $result = $model->withoutScope()->generate($this->clubId, 'Auth Test Key', ['members:read', 'payments:write']);
        $this->createdApiKeyIds[] = $result['id'];

        // Authenticate with the raw key
        $auth = $model->withoutScope()->authenticate($result['raw_key']);

        $this->assertNotNull($auth, 'Valid key must authenticate');
        $this->assertEquals($result['id'], $auth['id']);
        $this->assertEquals($this->clubId, (int) $auth['club_id']);
        $this->assertArrayHasKey('scopes_array', $auth);
        $this->assertEquals(['members:read', 'payments:write'], $auth['scopes_array']);
    }

    public function testApiKeyWrongKeyFails(): void
    {
        $model  = new ApiKeyModel();
        $result = $model->withoutScope()->generate($this->clubId, 'Wrong Key Test');
        $this->createdApiKeyIds[] = $result['id'];

        // Try authenticating with a wrong key (same prefix, different body)
        $wrongKey = substr($result['raw_key'], 0, 10) . str_repeat('0', 41);
        $auth = $model->withoutScope()->authenticate($wrongKey);

        $this->assertNull($auth, 'Wrong key must return null');
    }

    public function testApiKeyCompletelyFakeKeyFails(): void
    {
        $model = new ApiKeyModel();
        $auth  = $model->withoutScope()->authenticate('ks_totally_fake_key_1234567890');

        $this->assertNull($auth, 'Completely fake key must return null');
    }

    // ------------------------------------------------------------------
    // hasScope
    // ------------------------------------------------------------------

    public function testApiKeyHasScope(): void
    {
        $model  = new ApiKeyModel();
        $result = $model->withoutScope()->generate(
            $this->clubId,
            'Scope Test Key',
            ['members:read', 'payments:write']
        );
        $this->createdApiKeyIds[] = $result['id'];

        $auth = $model->withoutScope()->authenticate($result['raw_key']);
        $this->assertNotNull($auth);

        $this->assertTrue($model->hasScope($auth, 'members:read'));
        $this->assertTrue($model->hasScope($auth, 'payments:write'));
        $this->assertFalse($model->hasScope($auth, 'admin:delete'));
    }

    public function testApiKeyEmptyScopesGrantsFullAccess(): void
    {
        $model  = new ApiKeyModel();
        $result = $model->withoutScope()->generate($this->clubId, 'Full Access Key', []);
        $this->createdApiKeyIds[] = $result['id'];

        $auth = $model->withoutScope()->authenticate($result['raw_key']);
        $this->assertNotNull($auth);

        // Empty scopes = full access
        $this->assertTrue($model->hasScope($auth, 'anything'));
        $this->assertTrue($model->hasScope($auth, 'admin:delete'));
    }

    public function testApiKeyWildcardScope(): void
    {
        $model  = new ApiKeyModel();
        $result = $model->withoutScope()->generate($this->clubId, 'Wildcard Key', ['*']);
        $this->createdApiKeyIds[] = $result['id'];

        $auth = $model->withoutScope()->authenticate($result['raw_key']);
        $this->assertNotNull($auth);

        $this->assertTrue($model->hasScope($auth, 'members:read'));
        $this->assertTrue($model->hasScope($auth, 'anything:else'));
    }
}
