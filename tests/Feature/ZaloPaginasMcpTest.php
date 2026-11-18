<?php

use App\Mcp\Servers\ZaloTenants;
use App\Mcp\Tools\ListarCuentasTenants;
use App\Mcp\Tools\ListarEnviosFormularioTenant;
use App\Mcp\Tools\ListarUsuariosFormularioTenant;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.zalo_api.base_url' => 'http://zalo.test']);
});

it('asks for a page of submissions and returns the meta', function () {
    Http::fake([
        'http://zalo.test/api/v1/tenants/t-1/forms/3/submissions*' => Http::response([
            'data' => ['form_id' => 3, 'form_title' => 'Evaluación', 'submissions' => [['submission_id' => 9]]],
            'meta' => ['page' => 2, 'per_page' => 200, 'total' => 201, 'last_page' => 2],
        ]),
    ]);

    ZaloTenants::tool(ListarEnviosFormularioTenant::class, ['tenant_id' => 't-1', 'form_id' => '3', 'page' => 2, 'per_page' => 500])
        ->assertOk()
        ->assertStructuredContent([
            'envios' => ['form_id' => 3, 'form_title' => 'Evaluación', 'submissions' => [['submission_id' => 9]]],
            'count' => 1,
            'meta' => ['page' => 2, 'per_page' => 200, 'total' => 201, 'last_page' => 2],
        ]);

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'page=2')
        && str_contains($request->url(), 'per_page=200'));
});

it('sends no page params when none are given', function (string $tool, array $arguments) {
    Http::fake(['http://zalo.test/*' => Http::response(['data' => [], 'meta' => ['page' => 1]])]);

    ZaloTenants::tool($tool, $arguments)->assertOk();

    Http::assertSent(fn ($request): bool => ! str_contains($request->url(), '?'));
})->with([
    'cuentas' => [ListarCuentasTenants::class, ['tenant_id' => 't-1']],
    'asignados' => [ListarUsuariosFormularioTenant::class, ['tenant_id' => 't-1', 'form_id' => '3']],
]);
