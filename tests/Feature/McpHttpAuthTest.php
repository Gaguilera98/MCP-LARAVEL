<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! file_exists(storage_path('oauth-private.key'))) {
        Artisan::call('passport:keys', ['--force' => true]);
    }
});

it('rejects mcp http without bearer', function () {
    $this->postJson('/mcp/zalo-tenants', [])
        ->assertUnauthorized();
});

it('rejects mcp http without bearer even without Accept json', function () {
    $this->call('POST', '/mcp/zalo-tenants', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], '{}')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('allows zalo key on zalo and forbids on santox', function () {
    $user = User::factory()->create();
    $token = $user->createSanctumToken('zalo-only', ['zalo-tenants'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/mcp/santox-tenants', [])
        ->assertForbidden();

    $response = $this->withToken($token)->postJson('/mcp/zalo-tenants', []);
    expect($response->status())->not->toBeIn([401, 403]);
});

it('allows santox key on santox and forbids on zalo', function () {
    $user = User::factory()->create();
    $token = $user->createSanctumToken('santox-only', ['santox-tenants'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/mcp/zalo-tenants', [])
        ->assertForbidden();

    $response = $this->withToken($token)->postJson('/mcp/santox-tenants', []);
    expect($response->status())->not->toBeIn([401, 403]);
});

it('allows mailing key on godai-mailing and forbids on zalo', function () {
    $user = User::factory()->create();
    $token = $user->createSanctumToken('mailing-only', ['godai-mailing'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/mcp/zalo-tenants', [])
        ->assertForbidden();

    $this->withToken($token)
        ->postJson('/mcp/santox-tenants', [])
        ->assertForbidden();

    $response = $this->withToken($token)->postJson('/mcp/godai-mailing', []);
    expect($response->status())->not->toBeIn([401, 403]);
});

it('allows passport oauth user on godai-mailing without sanctum ability', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'api')
        ->postJson('/mcp/godai-mailing', []);

    expect($response->status())->not->toBeIn([401, 403]);
});

it('allows passport oauth user on all mcp servers', function () {
    $user = User::factory()->create();

    foreach (['/mcp/zalo-tenants', '/mcp/santox-tenants', '/mcp/godai-mailing'] as $path) {
        $response = $this->actingAs($user, 'api')->postJson($path, []);
        expect($response->status())->not->toBeIn([401, 403]);
    }
});

it('exposes oauth authorization server metadata', function () {
    $this->getJson('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonStructure(['issuer', 'authorization_endpoint', 'token_endpoint']);
});
