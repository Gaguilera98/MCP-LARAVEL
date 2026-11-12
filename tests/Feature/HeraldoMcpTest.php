<?php

use App\Mcp\Servers\Heraldo;
use App\Mcp\Tools\Heraldo\AsignarReceta;
use App\Mcp\Tools\Heraldo\ConfigurarCampana;
use App\Mcp\Tools\Heraldo\ConsolaCrearPedido;
use App\Mcp\Tools\Heraldo\ConsolaMoverPaso;
use App\Mcp\Tools\Heraldo\ConsolaSubirArchivo;
use App\Mcp\Tools\Heraldo\ConsolaVerArchivo;
use App\Mcp\Tools\Heraldo\ConsolaVincularCampanas;
use App\Mcp\Tools\Heraldo\CrearCampana;
use App\Mcp\Tools\Heraldo\ElegirDireccion;
use App\Mcp\Tools\Heraldo\ExcelTitulares;
use App\Mcp\Tools\Heraldo\GenerarCopys;
use App\Mcp\Tools\Heraldo\GenerarFotosBase;
use App\Mcp\Tools\Heraldo\GenerarTitulares;
use App\Mcp\Tools\Heraldo\GenerarVideos;
use App\Mcp\Tools\Heraldo\HojaFotos;
use App\Mcp\Tools\Heraldo\HojaPiezas;
use App\Mcp\Tools\Heraldo\ListarCampanas;
use App\Mcp\Tools\Heraldo\ListarPiezas;
use App\Mcp\Tools\Heraldo\ListarPresets;
use App\Mcp\Tools\Heraldo\ListarRecetas;
use App\Mcp\Tools\Heraldo\ListarTenants;
use App\Mcp\Tools\Heraldo\PedirAdaptaciones;
use App\Mcp\Tools\Heraldo\PedirExportacion;
use App\Mcp\Tools\Heraldo\PlanProduccion;
use App\Mcp\Tools\Heraldo\RegenerarCasilla;
use App\Mcp\Tools\Heraldo\RevisarPiezas;
use App\Mcp\Tools\Heraldo\SubirFoto;
use App\Mcp\Tools\Heraldo\SubirReferenciaSanto;
use App\Mcp\Tools\Heraldo\TraerFotos;
use App\Mcp\Tools\Heraldo\VerImagen;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Testing\TestResponse;

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

    Heraldo::tool(SubirFoto::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'strategy_id' => 9,
        'image_index' => 1,
        'ratio' => '1:1',
        'foto_url' => 'https://files.test/a.png',
        'console_file_id' => 77,
    ])->assertHasErrors(['una sola']);

    Http::assertNothingSent();
});

it('uploads a photo from a console file id as json and keeps ratio_warning', function () {
    Http::fake([
        'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/photos' => Http::response([
            'data' => [
                'saved' => true,
                'ratio_warning' => 'La foto mide 1600 × 900 px y no tiene la proporción de su columna (1:1).',
            ],
        ], 201),
    ]);

    Heraldo::tool(SubirFoto::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'strategy_id' => 9,
        'image_index' => 1,
        'ratio' => '1:1',
        'console_file_id' => 77,
    ])->assertOk()->assertStructuredContent([
        'data' => [
            'saved' => true,
            'ratio_warning' => 'La foto mide 1600 × 900 px y no tiene la proporción de su columna (1:1).',
        ],
    ]);

    Http::assertSent(fn ($request): bool => $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/photos'
        && $request->method() === 'POST'
        && $request['strategy_id'] === 9
        && $request['image_index'] === 1
        && $request['ratio'] === '1:1'
        && $request['console_file_id'] === 77
        && ! str_contains($request->body(), 'name="photo"'));
});

it('describes console_file_id and ratio_warning for subir-foto', function () {
    $source = file_get_contents(base_path('app/Mcp/Tools/Heraldo/SubirFoto.php'));

    expect($source)
        ->toContain('usa console_file_id: es lo más simple')
        ->toContain('Si la respuesta trae ratio_warning')
        ->toContain('de files en consola-ver-pedido')
        ->toContain('->min(1)');
});

it('registers the heraldo tools', function () {
    $server = new ReflectionClass(Heraldo::class);
    $tools = $server->getDefaultProperties()['tools'];
    $instructions = $server->getAttributes(Instructions::class)[0]->newInstance()->value;

    expect($tools)->toHaveCount(68)
        ->and($server->getAttributes(Name::class)[0]->newInstance()->value)->toBe('Heraldo')
        ->and($instructions)->toContain('La llave ya está configurada: no la pidas ni la cambies.')
        ->and($instructions)->toContain('dilo y no reintentes.')
        ->and($instructions)->toContain('ver-campana')
        ->and($instructions)->toContain('generar-fotos-base')
        ->and($instructions)->toContain('estado-copys-ia')
        ->and($instructions)->toContain('duplicar-campana')
        ->and($instructions)->toContain('listar-recetas')
        ->and($instructions)->toContain('piece_ids_json')
        ->and($instructions)->toContain('consola-pendientes')
        ->and($instructions)->toContain('regenerar-casilla')
        ->and($instructions)->toContain('manda agente')
        ->and($instructions)->toContain('bitácora de Heraldo');

    $planDescription = (new ReflectionClass(PlanProduccion::class))->getAttributes(Description::class)[0]->newInstance()->value;

    expect($planDescription)->toContain('regenerar-casilla')
        ->and($planDescription)->toContain('quitar-foto')
        ->and($planDescription)->toContain('ver-direcciones');

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

it('rounds noisy floats in error details', function () {
    Http::fake([
        '*' => Http::response([
            'message' => 'Saldo insuficiente.',
            'estimate_usd' => 0.056000000000000001165734175856414367444813251495361328125,
            'santo_balance_usd' => 45.98700000000000187583282240666449069976806640625,
        ], 422),
    ]);

    Heraldo::tool(RegenerarCasilla::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'strategy_id' => 1,
        'image_index' => 1,
        'ratio' => '1:1',
    ])->assertHasErrors([
        '422',
        'Saldo insuficiente.',
        'Detalle: {"estimate_usd":0.056,"santo_balance_usd":45.987}',
    ]);
});

it('sends buzz_urls when linking console campaigns', function () {
    Http::fake([
        'http://heraldo.test/api/console/v1/orders/12/campaigns' => Http::response([
            'data' => ['linked' => [44], 'order' => ['id' => 12]],
        ], 201),
    ]);

    Heraldo::tool(ConsolaVincularCampanas::class, [
        'order_id' => 12,
        'campaign_ids_json' => '[44,45]',
        'buzz_urls_json' => '{"44":"https://www.figma.com/file/abc","45":null}',
        'agente' => 'Agente de diseño',
    ])->assertOk()->assertStructuredContent([
        'data' => ['linked' => [44], 'order' => ['id' => 12]],
    ]);

    Http::assertSent(fn ($request): bool => $request->url() === 'http://heraldo.test/api/console/v1/orders/12/campaigns'
        && $request->hasHeader('X-Heraldo-Agent', 'Agente de diseño')
        && $request['campaign_ids'] === [44, 45]
        && $request['buzz_urls'] === ['44' => 'https://www.figma.com/file/abc', '45' => null]);
});

it('describes campaign_ids_json and buzz_urls_json for linking', function () {
    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/ConsolaVincularCampanas.php')))
        ->toContain('Los ids de las campañas que se vinculan, como lista JSON')
        ->toContain('buzz_urls_json')
        ->toContain('la persona lo abre desde el paso 6');
});

it('copies photos and keeps every field heraldo returns', function () {
    Http::fake([
        'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/photos/copy-from' => Http::response([
            'data' => [
                'copied' => 0,
                'dry_run' => true,
                'rows' => 2,
                'production_id' => 9,
                'warning' => null,
            ],
        ]),
    ]);

    Heraldo::tool(TraerFotos::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'source_campaign_id' => 3,
        'source_ratio' => '9:16',
        'target_ratio' => '1:1',
        'dry_run' => true,
    ])->assertOk()->assertStructuredContent([
        'data' => [
            'copied' => 0,
            'dry_run' => true,
            'rows' => 2,
            'production_id' => 9,
            'warning' => null,
        ],
    ]);

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/photos/copy-from'
        && $request['source_campaign_id'] === 3
        && $request['source_ratio'] === '9:16'
        && $request['target_ratio'] === '1:1'
        && $request['dry_run'] === true
        && ! array_key_exists('replace', $request->data()));
});

it('sends a null intention and leaves description untouched', function () {
    Http::fake(['*' => Http::response(['data' => ['name' => 'Marca', 'intention' => null]])]);

    Heraldo::tool(ConfigurarCampana::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'intention' => null,
        'copy_slots_json' => '[{"key":"c1","generate_with_ai":true}]',
    ])->assertOk();

    Http::assertSent(function ($request): bool {
        return $request->method() === 'PUT'
            && $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/setup'
            && array_key_exists('intention', $request->data())
            && $request['intention'] === null
            && ! array_key_exists('description', $request->data())
            && $request['copy_slots'] === [['key' => 'c1', 'generate_with_ai' => true]];
    });
});

it('sends single_sentence when generating copys', function () {
    Http::fake([
        'http://heraldo.test/api/v1/tenants/acme/campaigns/4/copies/generate' => Http::response([
            'data' => ['queued' => true, 'single_sentence' => true],
        ], 202),
    ]);

    Heraldo::tool(GenerarCopys::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'max_usd' => 1,
        'concept' => 'Para vos que mueves tu platita',
        'single_sentence' => true,
    ])->assertOk()->assertStructuredContent([
        'data' => ['queued' => true, 'single_sentence' => true],
    ]);

    Http::assertSent(fn ($request): bool => $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/copies/generate'
        && $request['max_usd'] === 1.0
        && $request['concept'] === 'Para vos que mueves tu platita'
        && $request['single_sentence'] === true);
});

it('sends file_names when configuring a campaign', function () {
    Http::fake([
        'http://heraldo.test/api/v1/tenants/acme/campaigns/4/setup' => Http::response([
            'data' => [
                'file_names' => [
                    'template' => 'BPD_{codigo}_{medida}',
                    'source' => 'campaign',
                ],
            ],
        ]),
    ]);

    Heraldo::tool(ConfigurarCampana::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'file_names_json' => '{"template":"BPD_{codigo}_{medida}","text":"Segmento A","lowercase":false}',
    ])->assertOk()->assertStructuredContent([
        'data' => [
            'file_names' => [
                'template' => 'BPD_{codigo}_{medida}',
                'source' => 'campaign',
            ],
        ],
    ]);

    Http::assertSent(fn ($request): bool => $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/setup'
        && $request['file_names'] === [
            'template' => 'BPD_{codigo}_{medida}',
            'text' => 'Segmento A',
            'lowercase' => false,
        ]);
});

it('lists gpt-image-2.6 models in configurar-santo', function () {
    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/ConfigurarSanto.php')))
        ->toContain('openai/gpt-image-2.6-sunburst')
        ->toContain('openai/gpt-image-2.6-flare')
        ->toContain('2.5: solo si la campaña ya lo usa')
        ->toContain('4:5 no');
});

it('describes rejected as pedir ajuste like the Heraldo screen', function () {
    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/RevisarPiezas.php')))
        ->toContain('Aprueba artes o pide ajustes')
        ->toContain('status rejected (en Heraldo se ve como «Ajuste pedido»)');

    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/ListarPiezas.php')))
        ->toContain('rejected (en Heraldo, «Ajuste pedido»)');
});

it('describes single_sentence and file_names for packages G and H', function () {
    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/GenerarCopys.php')))
        ->toContain('single_sentence')
        ->toContain('forman UNA sola frase');

    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/EstadoCopysIa.php')))
        ->toContain('single_sentence dice si los cuadros');

    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/ConfigurarCampana.php')))
        ->toContain('file_names_json')
        ->toContain('template null vuelve a la plantilla del cliente');

    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/VerCampana.php')))
        ->toContain('file_names dice cómo se llaman los archivos');
});

it('asks for the base photo estimate without spending when max usd is missing', function () {
    Http::fake([
        '*' => Http::response([
            'message' => 'max_usd es obligatorio: es el tope de gasto de esta operación.',
            'estimate_usd' => 1.2,
            'santo_balance_usd' => 4,
        ], 422),
    ]);

    Heraldo::tool(GenerarFotosBase::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
    ])->assertHasErrors(['422', 'max_usd es obligatorio', 'santo_balance_usd']);

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/santo/generate'
        && $request->data() === []);
});

it('returns the contact sheet json and the jpeg', function () {
    $jpeg = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGf/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9k=');
    $sheet = 'https://god.heraldo-x.co/campaign-productions/9/contact-sheet/plan?signature=abc';

    Http::fake(function ($request) use ($jpeg, $sheet) {
        if ($request->url() === $sheet) {
            return Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg']);
        }

        return Http::response([
            'data' => [
                'download_url' => $sheet,
                'total' => 2,
                'tiles' => [['n' => 1, 'strategy_id' => 8]],
                'extra' => 'se queda',
            ],
        ]);
    });

    $result = heraldoResult(Heraldo::tool(HojaFotos::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'ratio' => '9:16',
        'page' => 1,
    ])->assertOk()->assertStructuredContent([
        'data' => [
            'download_url' => $sheet,
            'total' => 2,
            'tiles' => [['n' => 1, 'strategy_id' => 8]],
            'extra' => 'se queda',
        ],
    ]));

    $image = collect($result['result']['content'])->firstWhere('type', 'image');

    expect($image['mimeType'])->toBe('image/jpeg')
        ->and($image['data'])->toBe(base64_encode($jpeg));

    Http::assertSent(fn ($request): bool => $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/plan/contact-sheet?ratio=9%3A16&page=1'
        && $request->hasHeader('Authorization', 'Bearer clave-heraldo'));

    Http::assertSent(fn ($request): bool => $request->url() === $sheet
        && ! $request->hasHeader('Authorization'));
});

it('filters the piece sheet the same way as listar-piezas', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $sheet = 'https://god.heraldo-x.co/campaign-productions/9/contact-sheet/pieces?signature=abc';

    Http::fake(function ($request) use ($png, $sheet) {
        if ($request->url() === $sheet) {
            return Http::response($png, 200, ['Content-Type' => 'image/png']);
        }

        return Http::response(['data' => ['download_url' => $sheet, 'tiles' => []]]);
    });

    $result = heraldoResult(Heraldo::tool(HojaPiezas::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'review' => 'approved',
        'platform' => 'Instagram',
    ])->assertOk());

    expect(collect($result['result']['content'])->firstWhere('type', 'image')['mimeType'])->toBe('image/png');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'pieces/contact-sheet')
        && $request['filter'] === [
            'review' => 'approved',
            'platform' => 'name:Instagram',
        ]);
});

it('shows a signed preview in the type heraldo sent', function (string $mime, string $bytes) {
    $url = 'https://cliente.heraldo-x.co/campaign-productions/plan-assets/3/signed-preview?signature=abc';

    Http::fake([
        $url => Http::response($bytes, 200, ['Content-Type' => $mime.'; charset=binary']),
    ]);

    $result = heraldoResult(Heraldo::tool(VerImagen::class, [
        'preview_url' => $url,
    ])->assertOk());

    $image = collect($result['result']['content'])->firstWhere('type', 'image');

    expect($image['mimeType'])->toBe($mime)
        ->and(base64_decode($image['data']))->toBe($bytes);

    Http::assertSent(fn ($request): bool => $request->url() === $url && ! $request->hasHeader('Authorization'));
})->with([
    'jpeg' => ['image/jpeg', 'jpeg-bytes'],
    'png' => ['image/png', 'png-bytes'],
    'webp' => ['image/webp', 'webp-bytes'],
]);

it('refuses a link that is not a heraldo image and an expired one', function () {
    Http::fake([
        'https://god.heraldo-x.co/*' => Http::response('no', 403),
    ]);

    Heraldo::tool(VerImagen::class, [
        'preview_url' => 'https://evil.com/signed-preview',
    ])->assertHasErrors(['heraldo-x.co']);

    Heraldo::tool(VerImagen::class, [
        'preview_url' => 'http://god.heraldo-x.co/campaign-productions/plan-assets/1/signed-preview',
    ])->assertHasErrors(['https']);

    Heraldo::tool(VerImagen::class, [
        'preview_url' => 'https://god.heraldo-x.co/campaign-productions/plan-assets/1/preview',
    ])->assertHasErrors(['signed-preview']);

    Heraldo::tool(VerImagen::class, [
        'preview_url' => 'https://god.heraldo-x.co/campaign-productions/plan-assets/1/signed-preview?signature=vieja',
    ])->assertHasErrors(['403', 'plan-produccion']);

    Http::assertSentCount(1);
});

it('keeps the sheet json when the image link fails', function () {
    $sheet = 'https://god.heraldo-x.co/campaign-productions/9/contact-sheet/plan?signature=vieja';

    Http::fake([
        'http://heraldo.test/*' => Http::response(['data' => ['download_url' => $sheet, 'tiles' => []]]),
        $sheet => Http::response('no', 404),
    ]);

    Heraldo::tool(HojaFotos::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
    ])->assertOk()
        ->assertStructuredContent(['data' => ['download_url' => $sheet, 'tiles' => []]])
        ->assertSee(['404', 'plan-produccion']);
});

it('uploads a santo reference as webp', function () {
    $webp = base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA');

    Http::fake(['*' => Http::response(['data' => ['path' => 'campaigns/4/source/foto.webp']], 201)]);

    Heraldo::tool(SubirReferenciaSanto::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'foto_base64' => base64_encode($webp),
    ])->assertOk()->assertStructuredContent([
        'data' => ['path' => 'campaigns/4/source/foto.webp'],
    ]);

    Http::assertSent(fn ($request): bool => $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/santo/reference'
        && str_contains($request->body(), 'foto.webp'));
});

it('lists animation recipes exactly, including an extra field', function () {
    Http::fake([
        'http://heraldo.test/api/v1/tenants/acme/animation-recipes' => Http::response([
            'data' => [
                'recipes' => [
                    ['key' => 'revelado-sereno', 'name' => 'Revelado sereno', 'extra' => 'nuevo'],
                ],
                'by_objective' => [
                    'conversion' => ['sobrio' => 'cta-claro'],
                ],
            ],
        ]),
    ]);

    Heraldo::tool(ListarRecetas::class, ['tenant_id' => 'acme'])
        ->assertOk()
        ->assertStructuredContent([
            'data' => [
                'recipes' => [
                    ['key' => 'revelado-sereno', 'name' => 'Revelado sereno', 'extra' => 'nuevo'],
                ],
                'by_objective' => [
                    'conversion' => ['sobrio' => 'cta-claro'],
                ],
            ],
        ]);

    Http::assertSent(fn ($request): bool => $request->method() === 'GET'
        && $request->url() === 'http://heraldo.test/api/v1/tenants/acme/animation-recipes'
        && $request->hasHeader('Authorization', 'Bearer clave-heraldo'));
});

it('sends piece ids when assigning a recipe or generating videos', function () {
    Http::fake([
        'http://heraldo.test/api/v1/tenants/acme/campaigns/4/animations/recipe' => Http::response([
            'data' => ['updated' => 2, 'without_layers' => []],
        ]),
        'http://heraldo.test/api/v1/tenants/acme/campaigns/4/animations/videos' => Http::response([
            'data' => ['queued' => true, 'videos' => 2, 'skipped' => [], 'estimate_usd' => 1.4],
        ], 202),
    ]);

    Heraldo::tool(AsignarReceta::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'recipe' => 'cta-claro',
        'piece_ids_json' => '[10,11]',
    ])->assertOk()->assertStructuredContent([
        'data' => ['updated' => 2, 'without_layers' => []],
    ]);

    Heraldo::tool(GenerarVideos::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'max_usd' => 3,
        'piece_ids_json' => '[10,11]',
    ])->assertOk()->assertStructuredContent([
        'data' => ['queued' => true, 'videos' => 2, 'skipped' => [], 'estimate_usd' => 1.4],
    ]);

    Http::assertSent(fn ($request): bool => $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/animations/recipe'
        && $request['recipe'] === 'cta-claro'
        && $request['piece_ids'] === [10, 11]);

    Http::assertSent(fn ($request): bool => $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/animations/videos'
        && $request['max_usd'] === 3.0
        && $request['piece_ids'] === [10, 11]);
});

it('shows a recipe example image from heraldo', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $url = 'https://god.heraldo-x.co/vendor/heraldo/recetas-ejemplos/cta-claro.png';

    Http::fake([
        $url => Http::response($png, 200, ['Content-Type' => 'image/png']),
    ]);

    $result = heraldoResult(Heraldo::tool(VerImagen::class, [
        'preview_url' => $url,
    ])->assertOk());

    $image = collect($result['result']['content'])->firstWhere('type', 'image');

    expect($image['mimeType'])->toBe('image/png')
        ->and(base64_decode($image['data']))->toBe($png);

    Heraldo::tool(VerImagen::class, [
        'preview_url' => 'https://evil.com/vendor/heraldo/recetas-ejemplos/cta-claro.png',
    ])->assertHasErrors(['heraldo-x.co']);
});

it('regenerates one cell and sets an adaptation direction', function () {
    Http::fake([
        'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/cells/regenerate' => Http::response([
            'data' => ['kind' => 'adaptation', 'action' => 'regenerate', 'estimate_usd' => 0.4],
        ], 202),
        'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/adaptation-directions' => Http::response([
            'data' => ['columns' => [['ratio' => '9:16', 'direction' => 'top']]],
        ]),
    ]);

    Heraldo::tool(RegenerarCasilla::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'strategy_id' => 9,
        'image_index' => 1,
        'ratio' => '9:16',
        'max_usd' => 1,
    ])->assertOk()->assertStructuredContent([
        'data' => ['kind' => 'adaptation', 'action' => 'regenerate', 'estimate_usd' => 0.4],
    ]);

    Heraldo::tool(ElegirDireccion::class, [
        'tenant_id' => 'acme',
        'campaign_id' => 4,
        'ratio' => '9:16',
        'direction' => 'top',
    ])->assertOk()->assertStructuredContent([
        'data' => ['columns' => [['ratio' => '9:16', 'direction' => 'top']]],
    ]);

    Http::assertSent(fn ($request): bool => $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/cells/regenerate'
        && $request['strategy_id'] === 9
        && $request['image_index'] === 1
        && $request['ratio'] === '9:16'
        && $request['max_usd'] === 1.0);

    Http::assertSent(fn ($request): bool => $request->method() === 'PUT'
        && $request->url() === 'http://heraldo.test/api/v1/tenants/acme/campaigns/4/production/adaptation-directions'
        && $request['ratio'] === '9:16'
        && $request['direction'] === 'top');
});

it('sends the console agent header and keeps arrays out of the query string', function () {
    Http::fake([
        'http://heraldo.test/api/console/v1/orders' => Http::response([
            'data' => ['id' => 12, 'name' => 'Prueba'],
        ], 201),
    ]);

    Heraldo::tool(ConsolaCrearPedido::class, [
        'tenant_id' => 'acme',
        'name' => 'Prueba',
        'campaign_ids_json' => '[4,5]',
        'agente' => 'Agente de diseño',
    ])->assertOk()->assertStructuredContent([
        'data' => ['id' => 12, 'name' => 'Prueba'],
    ]);

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return $request->url() === 'http://heraldo.test/api/console/v1/orders'
            && $request->hasHeader('Authorization', 'Bearer clave-heraldo')
            && $request->hasHeader('X-Heraldo-Agent', 'Agente de diseño')
            && $body['tenant_id'] === 'acme'
            && $body['name'] === 'Prueba'
            && $body['campaign_ids'] === [4, 5]
            && ! array_key_exists('agente', $body);
    });
});

it('uploads a console file from a url in the file field', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    Http::fake([
        'https://files.test/captura.png' => Http::response($png, 200, ['Content-Type' => 'image/png']),
        'http://heraldo.test/api/console/v1/orders/12/files' => Http::response([
            'data' => ['id' => 77, 'name' => 'captura.png'],
        ], 201),
    ]);

    Heraldo::tool(ConsolaSubirArchivo::class, [
        'order_id' => 12,
        'archivo_url' => 'https://files.test/captura.png',
        'name' => 'captura.png',
        'kind' => 'capture',
        'agente' => 'Agente de diseño',
    ])->assertOk()->assertStructuredContent([
        'data' => ['id' => 77, 'name' => 'captura.png'],
    ]);

    Http::assertSent(fn ($request): bool => $request->url() === 'http://heraldo.test/api/console/v1/orders/12/files'
        && $request->hasHeader('X-Heraldo-Agent', 'Agente de diseño')
        && str_contains($request->body(), 'filename="captura.png"')
        && str_contains($request->body(), 'name="file"')
        && str_contains($request->body(), 'name="kind"'));
});

it('keeps console enum and json shapes in the schema text', function () {
    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/ConsolaPreguntar.php')))
        ->toContain('Lista en JSON, hasta 8')
        ->toContain('[{"text":"…","category":"legal"}]')
        ->toContain('marca, legal, texto, foto, diseno o proceso');

    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/ConsolaListarPedidos.php')))
        ->toContain('open, active, paused, delivered o cancelled');

    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/ConsolaListarPreguntas.php')))
        ->toContain('open, answered o cancelled');

    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/ConsolaAnotar.php')))
        ->toContain('note, progress, decision o problem');

    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/ConsolaRegistrarCorrida.php')))
        ->toContain('launcher, agent o person');

    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/ConsolaCrearMarca.php')))
        ->toContain('tu, vos, usted o mixto');

    expect(file_get_contents(base_path('app/Mcp/Tools/Heraldo/ConsolaEditarMarca.php')))
        ->toContain('tu, vos, usted o mixto');
});

it('exposes fixed enums and bounds in tool schemas', function () {
    $schema = new JsonSchemaTypeFactory;

    expect((new ListarPiezas)->schema($schema)['review']->toArray()['enum'])
        ->toBe(['all', 'in_review', 'approved', 'rejected', 'reviewed']);

    expect((new RevisarPiezas)->schema($schema)['status']->toArray()['enum'])
        ->toBe(['approved', 'rejected', 'in_review']);

    expect((new PedirExportacion)->schema($schema)['type']->toArray()['enum'])
        ->toBe(['pieces', 'videos']);

    expect((new AsignarReceta)->schema($schema)['style']->toArray()['enum'])
        ->toBe(['sobrio', 'dinamico', 'impacto']);

    $options = (new GenerarTitulares)->schema($schema)['options']->toArray();
    expect($options['minimum'])->toBe(1)->and($options['maximum'])->toBe(5);

    $listar = (new ListarCampanas)->schema($schema);
    expect($listar['page']->toArray()['minimum'])->toBe(1)
        ->and($listar['per_page']->toArray()['minimum'])->toBe(1)
        ->and($listar['per_page']->toArray()['maximum'])->toBe(100);

    $mover = (new ConsolaMoverPaso)->schema($schema);
    expect($mover['step']->toArray()['enum'])
        ->toBe(['order', 'campaigns', 'design', 'content', 'templates', 'review', 'delivery'])
        ->and($mover['state']->toArray()['enum'])
        ->toBe(['pending', 'agent', 'done', 'skipped']);

    expect((new ElegirDireccion)->schema($schema)['direction']->toArray()['enum'])
        ->toBe(['top', 'bottom', 'left', 'right', 'center']);

    expect((new PedirAdaptaciones)->schema($schema)['max_usd']->toArray()['minimum'])->toBe(0);
});

it('uses tú in tool descriptions and keeps the two BP phrases', function () {
    $voseo = '/(?<!\p{L})(volvé|usá|usás|mandá|mandás|indicás|escribí|seguí|llamal[ao]|armá|corregí|mostrale|pasale|pasaselo|esperá|podés|respetá|dividilos|empezá|mirala|confirmá|preferí|generá|acá)(?!\p{L})/iu';

    $server = new ReflectionClass(Heraldo::class);
    $instructions = $server->getAttributes(Instructions::class)[0]->newInstance()->value;
    expect(preg_match($voseo, $instructions))->toBe(0);

    foreach ($server->getDefaultProperties()['tools'] as $class) {
        $description = (new ReflectionClass($class))->getAttributes(Description::class)[0]->newInstance()->value;
        expect(preg_match($voseo, $description))->toBe(0, "voseo en {$class}");
    }

    $mover = (new ReflectionClass(ConsolaMoverPaso::class))->getAttributes(Description::class)[0]->newInstance()->value;
    $adaptaciones = (new ReflectionClass(PedirAdaptaciones::class))->getAttributes(Description::class)[0]->newInstance()->value;

    expect($mover)->toContain('se marca skipped en cuanto se sabe')
        ->and($adaptaciones)->toContain('cuéntalas en plan-produccion de esta campaña');
});

it('shows a console image and rejects another host', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $url = 'https://heraldo-x.co/consola/archivos/abc?signature=1';

    Http::fake([
        'http://heraldo.test/api/console/v1/files/77' => Http::response([
            'data' => ['id' => 77, 'mime' => 'image/png', 'url' => $url],
        ]),
        $url => Http::response($png, 200, ['Content-Type' => 'image/png']),
    ]);

    $result = heraldoResult(Heraldo::tool(ConsolaVerArchivo::class, [
        'file_id' => 77,
    ])->assertOk());

    $image = collect($result['result']['content'])->firstWhere('type', 'image');

    expect($image['mimeType'])->toBe('image/png')
        ->and(base64_decode($image['data']))->toBe($png);

    Http::fake([
        'http://heraldo.test/api/console/v1/files/78' => Http::response([
            'data' => [
                'id' => 78,
                'mime' => 'image/png',
                'url' => 'https://evil.com/consola/archivos/abc',
            ],
        ]),
    ]);

    $bad = heraldoResult(Heraldo::tool(ConsolaVerArchivo::class, [
        'file_id' => 78,
    ])->assertOk());

    expect(collect($bad['result']['content'])->pluck('text')->implode(' '))
        ->toContain('heraldo-x.co/consola/archivos');
});

/**
 * @return array<string, mixed>
 */
function heraldoResult(TestResponse $response): array
{
    $property = new ReflectionProperty($response, 'response');

    return $property->getValue($response)->toArray();
}
