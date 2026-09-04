<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
    $token = $user->createToken('zalo-only', ['zalo-tenants'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/mcp/santox-tenants', [])
        ->assertForbidden();

    // Auth + ability OK: puede pasar del middleware (respuesta MCP, no 401/403).
    $response = $this->withToken($token)->postJson('/mcp/zalo-tenants', []);
    expect($response->status())->not->toBeIn([401, 403]);
});

it('allows santox key on santox and forbids on zalo', function () {
    $user = User::factory()->create();
    $token = $user->createToken('santox-only', ['santox-tenants'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/mcp/zalo-tenants', [])
        ->assertForbidden();

    $response = $this->withToken($token)->postJson('/mcp/santox-tenants', []);
    expect($response->status())->not->toBeIn([401, 403]);
});
