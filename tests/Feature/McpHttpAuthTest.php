<?php

use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\ClientRepository;
use Livewire\Livewire;

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

it('allows lms key on lms and forbids on the other servers', function () {
    $user = User::factory()->create();
    $token = $user->createSanctumToken('lms-only', ['lms-zalo'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/mcp/zalo-tenants', [])
        ->assertForbidden();

    $this->withToken($token)
        ->postJson('/mcp/godai-mailing', [])
        ->assertForbidden();

    $response = $this->withToken($token)->postJson('/mcp/lms-zalo', []);
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

    foreach (['/mcp/zalo-tenants', '/mcp/santox-tenants', '/mcp/godai-mailing', '/mcp/lms-zalo'] as $path) {
        $response = $this->actingAs($user, 'api')->postJson($path, []);
        expect($response->status())->not->toBeIn([401, 403]);
    }
});

it('sends a signed-out oauth authorize visit to the panel login', function () {
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'Claude',
        ['https://claude.ai/api/mcp/auth_callback'],
        confidential: false,
    );

    $this->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
        'response_type' => 'code',
        'code_challenge' => str_repeat('a', 43),
        'code_challenge_method' => 'S256',
    ]))->assertRedirect(route('filament.admin.auth.login'));
});

it('returns to the approval screen after signing in from claude', function () {
    $user = User::factory()->create();
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'Claude',
        ['https://claude.ai/api/mcp/auth_callback'],
        confidential: false,
    );
    $authorize = '/oauth/authorize?'.http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
        'response_type' => 'code',
        'code_challenge' => str_repeat('a', 43),
        'code_challenge_method' => 'S256',
    ]);

    $this->get($authorize)->assertRedirect(route('filament.admin.auth.login'));

    expect((string) session('url.intended'))->toContain('/oauth/authorize');

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertRedirect(session('url.intended'));

    $this->get($authorize)
        ->assertOk()
        ->assertSee('Authorize Claude')
        ->assertSee($user->email);
});

it('exposes oauth authorization server metadata', function () {
    $this->getJson('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonStructure(['issuer', 'authorization_endpoint', 'token_endpoint']);
});
