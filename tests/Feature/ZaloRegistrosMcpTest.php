<?php

use App\Mcp\Servers\ZaloTenants;
use App\Mcp\Tools\CambiosTenant;
use App\Mcp\Tools\ChatUsuario;
use App\Mcp\Tools\ConsultasApiZalo;
use App\Mcp\Tools\GetAccountChat;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.zalo_api.base_url' => 'http://zalo.test',
        'services.zalo_api.token' => 'token-equipo',
    ]);
});

it('lists the api queries with the date filters renamed for the api', function () {
    Http::fake([
        'http://zalo.test/api/v1/api-requests*' => Http::response([
            'data' => [['id' => 7, 'route' => 'api/v1/tenants/{tenantId}/models', 'status' => 403]],
            'meta' => ['page' => 1, 'per_page' => 50, 'total' => 1, 'last_page' => 1],
        ]),
    ]);

    ZaloTenants::tool(ConsultasApiZalo::class, [
        'date_from' => '2026-11-01',
        'date_to' => '2026-11-17',
        'status' => 403,
        'per_page' => 500,
    ])
        ->assertOk()
        ->assertStructuredContent([
            'consultas' => [
                'data' => [['id' => 7, 'route' => 'api/v1/tenants/{tenantId}/models', 'status' => 403]],
                'meta' => ['page' => 1, 'per_page' => 50, 'total' => 1, 'last_page' => 1],
            ],
        ]);

    Http::assertSent(function ($request): bool {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $request->method() === 'GET'
            && str_starts_with($request->url(), 'http://zalo.test/api/v1/api-requests')
            && $request->hasHeader('Authorization', 'Bearer token-equipo')
            && $query === ['from' => '2026-11-01', 'to' => '2026-11-17', 'status' => '403', 'per_page' => '200'];
    });
});

it('lists the changes of a tenant', function () {
    $tenant = 'd1256644-6f60-41d8-8105-92be40868cfe';
    Http::fake([
        "http://zalo.test/api/v1/tenants/{$tenant}/audit-events*" => Http::response([
            'data' => [['event' => 'updated', 'subject_type' => 'setting', 'changes' => ['value' => ['from' => '0', 'to' => '1']]]],
            'meta' => ['page' => 1, 'per_page' => 50, 'total' => 1, 'last_page' => 1],
        ]),
    ]);

    ZaloTenants::tool(CambiosTenant::class, ['tenant_id' => $tenant, 'subject_type' => 'setting'])
        ->assertOk()
        ->assertStructuredContent([
            'cambios' => [
                'data' => [['event' => 'updated', 'subject_type' => 'setting', 'changes' => ['value' => ['from' => '0', 'to' => '1']]]],
                'meta' => ['page' => 1, 'per_page' => 50, 'total' => 1, 'last_page' => 1],
            ],
        ]);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), "/api/v1/tenants/{$tenant}/audit-events")
        && str_contains($request->url(), 'subject_type=setting'));
});

it('explains a 403 from the api', function () {
    Http::fake(['http://zalo.test/api/v1/api-requests*' => Http::response(['message' => 'Este token no tiene acceso a esta consulta.'], 403)]);

    ZaloTenants::tool(ConsultasApiZalo::class)
        ->assertHasErrors(['token del equipo']);
});

it('rejects a tenant id that is not a uuid without calling the api', function () {
    Http::fake();

    ZaloTenants::tool(CambiosTenant::class, ['tenant_id' => '../api-requests'])
        ->assertHasErrors(['tenant_id inválido']);

    Http::assertNothingSent();
});

it('asks for at most 10 conversations per page when messages are included', function (string $tool, array $arguments, string $path) {
    Http::fake(['http://zalo.test/*' => Http::response(['data' => [], 'meta' => []])]);

    ZaloTenants::tool($tool, $arguments + ['per_page' => 50, 'include_messages' => true])->assertOk();

    Http::assertSent(fn ($request): bool => str_contains($request->url(), $path)
        && str_contains($request->url(), 'per_page=10'));
})->with([
    'chat de una cuenta' => [GetAccountChat::class, ['tenantId' => 't-1', 'accountId' => '1'], '/accounts/1/chat'],
    'chat de un usuario' => [ChatUsuario::class, ['tenant_id' => 't-1', 'account_id' => '1', 'user_id' => '5'], '/users/5/chat'],
]);

it('registers the new tools in the zalo server', function () {
    $tools = (new ReflectionClass(ZaloTenants::class))->getDefaultProperties()['tools'];

    expect($tools)->toContain(ConsultasApiZalo::class)->toContain(CambiosTenant::class);
});
