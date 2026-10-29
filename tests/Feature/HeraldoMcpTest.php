<?php

use App\Mcp\Servers\Heraldo;
use App\Mcp\Tools\Heraldo\CrearCampana;
use App\Mcp\Tools\Heraldo\ExcelTitulares;
use App\Mcp\Tools\Heraldo\GenerarVideos;
use App\Mcp\Tools\Heraldo\ListarPiezas;
use App\Mcp\Tools\Heraldo\ListarPresets;
use App\Mcp\Tools\Heraldo\ListarTenants;
use App\Mcp\Tools\Heraldo\PedirAdaptaciones;
use App\Mcp\Tools\Heraldo\SubirFoto;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;

beforeEach(function () {
    config([
        'services.heraldo_api.base_url' => 'http://heraldo.test',
        'services.heraldo_api.token' => 'clave-heraldo',
    ]);
});

it('returns the tenant json exactly, including an extra field', function () {
    Http::fake([
        'http://heraldo.test/api/v1/tenants' => Http::response([
            'data' => [
                ['id' => 'acme', 'name' => 'Acme', 'extra' => 'nuevo'],
            ],
        ]),
    ]);

    Heraldo::tool(ListarTenants::class)
        ->assertOk()
        ->assertStructuredContent([
            'data' => [
                ['id' => 'acme', 'name' => 'Acme', 'extra' => 'nuevo'],
            ],
        ]);

    Http::assertSent(function ($request): bool {
        return $request->method() === 'GET'
            && $request->url() === 'http://heraldo.test/api/v1/tenants'
            && $request->hasHeader('Authorization', 'Bearer clave-heraldo');
    });
});

it('returns a queued response in full', function () {
    Http::fake([
        'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/adaptations' => Http::response([
            'data' => ['queued' => true, 'estimate_usd' => 1.5],
        ], 202),
    ]);

    Heraldo::tool(PedirAdaptaciones::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'max_usd' => 3,
    ])->assertOk()->assertStructuredContent([
        'data' => ['queued' => true, 'estimate_usd' => 1.5],
    ]);
});

it('passes heraldo 403 and 422 messages through', function () {
    Http::fake([
        'http://heraldo.test/api/v1/tenants/acme/presets' => Http::response([
            'message' => 'Esta llave es de solo lectura: no puede operar.',
        ], 403),
        'http://heraldo.test/api/v1/tenants/acme/campaigns' => Http::response([
            'message' => 'La hoja tiene una fila vacía.',
        ], 422),
    ]);

    Heraldo::tool(ListarPresets::class, ['tenant_id' => 'acme'])
        ->assertHasErrors(['403', 'Esta llave es de solo lectura: no puede operar.']);

    Heraldo::tool(CrearCampana::class, [
        'tenant_id' => 'acme',
        'preset_id' => 2,
        'name' => 'Marca',
        'sheet' => "producto\n",
        'messages_json' => '["Post"]',
        'format_ids_json' => '[8]',
        'dry_run' => true,
    ])->assertHasErrors(['422', 'La hoja tiene una fila vacía.']);

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'POST') {
            return false;
        }

        return $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns'
            && $request['preset_id'] === 2
            && $request['name'] === 'Marca'
            && $request['dry_run'] === true
            && $request['messages'] === ['Post']
            && $request['format_ids'] === [8];
    });
});

it('forwards the excel download json and the 409 when there are no texts', function () {
    Http::fake([
        'http://heraldo.test/api/v1/tenants/acme/campaigns/4/headlines/excel' => Http::sequence()
            ->push([
                'data' => [
                    'download_url' => 'https://cliente.test/excel',
                    'filename' => 'Campana_Textos.xlsx',
                    'expires_at' => '2026-10-30T18:00:00+00:00',
                ],
            ])
            ->push(['message' => 'Todavía no hay textos de publicación.'], 409)
            ->push('PK fake-xlsx', 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]),
    ]);

    Heraldo::tool(ExcelTitulares::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
    ])->assertOk()->assertStructuredContent([
        'data' => [
            'download_url' => 'https://cliente.test/excel',
            'filename' => 'Campana_Textos.xlsx',
            'expires_at' => '2026-10-30T18:00:00+00:00',
        ],
    ]);

    Heraldo::tool(ExcelTitulares::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
    ])->assertHasErrors(['409', 'Todavía no hay textos de publicación.']);

    Heraldo::tool(ExcelTitulares::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
    ])->assertHasErrors(['sin JSON']);
});

it('uploads a photo from base64 or a url as multipart', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    Http::fake(function ($request) use ($png) {
        if ($request->url() === 'https://files.test/foto.png') {
            return Http::response($png, 200, ['Content-Type' => 'image/png']);
        }

        return Http::response(['data' => ['saved' => true, 'extra' => 'foto']], 201);
    });

    Heraldo::tool(SubirFoto::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'strategy_id' => 9,
        'image_index' => 1,
        'ratio' => '1:1',
        'foto_base64' => base64_encode($png),
    ])->assertOk()->assertStructuredContent([
        'data' => ['saved' => true, 'extra' => 'foto'],
    ]);

    Heraldo::tool(SubirFoto::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'strategy_id' => 9,
        'image_index' => 2,
        'ratio' => '9:16',
        'foto_url' => 'https://files.test/foto.png',
    ])->assertOk();

    $uploads = Http::recorded(function ($request): bool {
        return $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/photos';
    })->values();

    expect($uploads)->toHaveCount(2);

    foreach ($uploads as [$request]) {
        expect($request->method())->toBe('POST')
            ->and($request->hasHeader('Authorization', 'Bearer clave-heraldo'))->toBeTrue()
            ->and($request->body())->toContain('name="photo"', 'foto.png', 'strategy_id', 'image_index', 'ratio');
    }

    expect($uploads[0][0]->body())->toContain('1:1')
        ->and($uploads[1][0]->body())->toContain('9:16');
});

it('rejects a photo that is not jpg or png or is over 10 mb without calling heraldo', function () {
    Http::fake();

    Heraldo::tool(SubirFoto::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'strategy_id' => 9,
        'image_index' => 1,
        'ratio' => '1:1',
        'foto_base64' => base64_encode('hola'),
    ])->assertHasErrors(['JPG o PNG']);

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    Heraldo::tool(SubirFoto::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'strategy_id' => 9,
        'image_index' => 1,
        'ratio' => '1:1',
        'foto_base64' => base64_encode($png.str_repeat("\0", 10 * 1024 * 1024)),
    ])->assertHasErrors(['10 MB']);

    Heraldo::tool(SubirFoto::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'strategy_id' => 9,
        'image_index' => 1,
        'ratio' => '1:1',
        'foto_url' => 'https://files.test/a.png',
        'foto_base64' => base64_encode($png),
    ])->assertHasErrors(['una sola']);

    Http::assertNothingSent();
});

it('registers the 25 heraldo tools', function () {
    $server = new ReflectionClass(Heraldo::class);
    $tools = $server->getDefaultProperties()['tools'];
    $instructions = $server->getAttributes(Instructions::class)[0]->newInstance()->value;

    expect($tools)->toHaveCount(25)
        ->and($server->getAttributes(Name::class)[0]->newInstance()->value)->toBe('Heraldo')
        ->and($instructions)->toContain('La llave ya está configurada: no la pidas ni la cambies.')
        ->and($instructions)->toContain('decilo y no reintentes.');

    foreach ($tools as $class) {
        $description = (new ReflectionClass($class))->getAttributes(Description::class)[0]->newInstance()->value;

        expect($description)->not->toContain('llave que pueda operar');

        foreach (['endpoint', 'multipart', 'Bearer', '/api/', 'La API'] as $jargon) {
            expect(str_contains($description, $jargon))->toBeFalse("{$class} menciona {$jargon}");
        }
    }
});

it('sends platform format and product as name filters and keeps id filters', function () {
    Http::fake(['*' => Http::response(['data' => ['pieces' => []]])]);

    Heraldo::tool(ListarPiezas::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'review' => 'approved',
        'platform' => 'Instagram',
        'format' => 'id:7',
        'product' => 'name:Café',
    ])->assertOk();

    Http::assertSent(fn ($request): bool => $request['filter'] === [
        'review' => 'approved',
        'platform' => 'name:Instagram',
        'format' => 'id:7',
        'product' => 'name:Café',
    ]);
});

it('includes the extra error fields so the agent can show the estimate', function () {
    Http::fake([
        '*' => Http::response([
            'message' => 'Indicá max_usd para confirmar el gasto.',
            'estimate_usd' => 2.4,
        ], 422),
    ]);

    Heraldo::tool(GenerarVideos::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
    ])->assertHasErrors(['422', 'Indicá max_usd para confirmar el gasto.', 'Detalle: {"estimate_usd":2.4}']);
});
