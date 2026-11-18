<?php

use App\Mcp\Servers\ZaloTenants;
use App\Mcp\Tools\ResumenModerador;
use App\Mcp\Tools\RevisionesModerador;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.zalo_api.base_url' => 'http://zalo.test',
        'services.zalo_api.token' => 'token-equipo',
    ]);
});

it('lists the flagged reviews of a tenant', function () {
    $tenant = '5d37120a-8fef-4658-811f-4cf3983a35e9';
    Http::fake([
        "http://zalo.test/api/v1/tenants/{$tenant}/content-reviews*" => Http::response([
            'data' => [['id' => 3, 'verdict' => 'flag', 'categories' => ['copyright'], 'phase' => 'output']],
            'meta' => ['page' => 1, 'per_page' => 50, 'total' => 1, 'last_page' => 1],
        ]),
    ]);

    ZaloTenants::tool(RevisionesModerador::class, ['tenant_id' => $tenant, 'verdict' => 'flag', 'date_from' => '2026-11-01', 'per_page' => 900])
        ->assertOk()
        ->assertStructuredContent([
            'revisiones' => [
                'data' => [['id' => 3, 'verdict' => 'flag', 'categories' => ['copyright'], 'phase' => 'output']],
                'meta' => ['page' => 1, 'per_page' => 50, 'total' => 1, 'last_page' => 1],
            ],
        ]);

    Http::assertSent(function ($request) use ($tenant): bool {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return str_contains($request->url(), "/api/v1/tenants/{$tenant}/content-reviews")
            && $query === ['date_from' => '2026-11-01', 'verdict' => 'flag', 'per_page' => '200'];
    });
});

it('asks the summary without paging', function () {
    Http::fake(['http://zalo.test/*' => Http::response(['data' => ['total' => 0]])]);

    ZaloTenants::tool(ResumenModerador::class, ['tenant_id' => 't-1', 'date_from' => '2026-11-01', 'page' => 3])->assertOk();

    Http::assertSent(fn ($request): bool => str_contains($request->url(), '/api/v1/tenants/t-1/content-reviews/summary?date_from=2026-11-01')
        && ! str_contains($request->url(), 'page='));
});

it('rejects a tenant id that is not a uuid without calling the api', function () {
    Http::fake();

    ZaloTenants::tool(RevisionesModerador::class, ['tenant_id' => '../api-requests'])
        ->assertHasErrors(['tenant_id inválido']);

    Http::assertNothingSent();
});

it('registers the moderator tools in the zalo server', function () {
    $tools = (new ReflectionClass(ZaloTenants::class))->getDefaultProperties()['tools'];

    expect($tools)->toContain(RevisionesModerador::class)->toContain(ResumenModerador::class);
});
