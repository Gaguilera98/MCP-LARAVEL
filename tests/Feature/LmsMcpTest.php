<?php

use App\Mcp\Servers\LmsZalo;
use App\Mcp\Tools\Lms\CargarCalificaciones;
use App\Mcp\Tools\Lms\CrearCurso;
use App\Mcp\Tools\Lms\CrearEstudiante;
use App\Mcp\Tools\Lms\SubirArchivo;
use App\Mcp\Tools\Lms\SubirVideo;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Instructions;

beforeEach(function () {
    config([
        'services.lms_api.base_url' => 'http://lms.test',
        'services.lms_api.token' => 'clave-aula',
    ]);
});

it('creates a student with plain words and hides the remote call', function () {
    Http::fake([
        'http://lms.test/api/v1/students' => Http::response([
            'id' => 3,
            'name' => 'Ana',
            'last_name' => 'Paz',
            'email' => 'ana@ejemplo.com',
            'username' => 'anap',
            'is_active' => true,
        ], 201),
    ]);

    LmsZalo::tool(CrearEstudiante::class, [
        'nombre' => 'Ana',
        'apellidos' => 'Paz',
        'correo' => 'ana@ejemplo.com',
        'contrasena' => 'secreta123',
    ])->assertOk()->assertStructuredContent([
        'id' => 3,
        'nombre' => 'Ana',
        'apellidos' => 'Paz',
        'correo' => 'ana@ejemplo.com',
        'usuario' => 'anap',
        'activo' => true,
    ]);

    Http::assertSent(function ($request): bool {
        return $request->url() === 'http://lms.test/api/v1/students'
            && $request['name'] === 'Ana'
            && $request['last_name'] === 'Paz'
            && $request['email'] === 'ana@ejemplo.com'
            && $request['password'] === 'secreta123';
    });
});

it('creates a course using the numbers of files already uploaded', function () {
    Http::fake([
        'http://lms.test/api/v1/courses' => Http::response([
            'id' => 4,
            'name' => 'Marca',
            'description' => 'Curso corto',
            'is_private' => true,
            'expires_at' => '2026-12-01T18:00:00+00:00',
            'cover_library_item_id' => 8,
            'categories' => [],
            'chapters' => [[
                'id' => 10,
                'name' => 'Semana 1',
                'is_published' => true,
                'order' => 1,
                'tutors' => [],
                'steps' => [[
                    'id' => 20,
                    'name' => 'Clase',
                    'order' => 1,
                    'is_optional' => false,
                    'type' => 'video',
                    'library_item_id' => 9,
                ]],
            ]],
        ], 201),
    ]);

    LmsZalo::tool(CrearCurso::class, [
        'nombre' => 'Marca',
        'descripcion' => 'Curso corto',
        'privado' => true,
        'cierra_el' => '2026-12-01 18:00',
        'portada' => 8,
        'capitulos' => '[{"nombre":"Semana 1","pasos":[{"nombre":"Clase","tipo":"video","archivo":9}]}]',
    ])->assertOk()->assertSee(['Marca', 'Semana 1']);

    Http::assertSent(function ($request): bool {
        return $request->url() === 'http://lms.test/api/v1/courses'
            && $request['name'] === 'Marca'
            && $request['is_private'] === true
            && $request['cover_library_item_id'] === 8
            && $request['chapters'][0]['steps'][0]['type'] === 'video'
            && $request['chapters'][0]['steps'][0]['library_item_id'] === 9;
    });
});

it('explains a skipped grade in plain words', function () {
    Http::fake([
        'http://lms.test/api/v1/courses/4/grades' => Http::response([
            'saved' => [],
            'skipped' => [[
                'email' => 'ana@ejemplo.com',
                'reason' => 'No está inscripto en el curso.',
            ]],
        ]),
    ]);

    LmsZalo::tool(CargarCalificaciones::class, [
        'curso' => 4,
        'notas' => '[{"correo":"ana@ejemplo.com","calificacion":"A","comentario":"Muy bien"}]',
    ])->assertOk()->assertStructuredContent([
        'guardadas' => [],
        'omitidos' => [[
            'motivo' => 'No está inscripto en el curso.',
            'correo' => 'ana@ejemplo.com',
        ]],
    ]);
});

it('says when the file is not on this machine', function () {
    LmsZalo::tool(SubirArchivo::class, [
        'ruta' => 'C:\\no-existe\\guia.pdf',
        'uso' => 'documento',
    ])->assertHasErrors(['No encuentro ese archivo']);
});

it('uploads a class video and only returns its number', function () {
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'clase-mcp-test.mp4';
    file_put_contents($path, 'video');

    Http::fake(function ($request) {
        if ($request->method() === 'PUT') {
            return Http::response('', 200);
        }

        if (str_ends_with($request->url(), '/library/uploads')) {
            return Http::response([
                'uuid' => 'abc-secreto',
                'url' => 'http://bucket.test/put',
                'headers' => ['Content-Type' => 'video/mp4'],
            ]);
        }

        return Http::response([
            'id' => 9,
            'name' => 'clase-mcp-test.mp4',
        ], 201);
    });

    LmsZalo::tool(SubirVideo::class, [
        'ruta' => $path,
    ])->assertOk()
        ->assertStructuredContent([
            'id' => 9,
            'nombre' => 'clase-mcp-test.mp4',
        ])
        ->assertDontSee(['abc-secreto', 'bucket.test']);

    @unlink($path);
});

it('describes every tool for the agent without remote jargon', function () {
    $server = new ReflectionClass(LmsZalo::class);
    $tools = $server->getDefaultProperties()['tools'];
    $instructions = $server->getAttributes(Instructions::class)[0]->newInstance()->value;

    expect($tools)->toHaveCount(23);
    expect(mb_strtolower($instructions))->not->toContain('endpoint', '/api/', 'sanctum', 'uuid');

    $banned = ['endpoint', 'sanctum', 'uuid', 'presigned', 'library_item', '/api/', 'bearer', 'multipart'];

    foreach ($tools as $class) {
        $tool = new ReflectionClass($class);
        $description = mb_strtolower($tool->getAttributes(Description::class)[0]->newInstance()->value);

        foreach ($banned as $word) {
            expect($description)->not->toContain($word);
        }
    }
});
