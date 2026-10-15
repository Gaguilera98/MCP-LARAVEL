<?php

namespace App\Mcp\Tools\Lms;

use App\Support\LmsApi;
use App\Support\LmsPresent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('crear-estudiante')]
#[Description(
    'Crea un estudiante con nombre, apellidos, correo y contraseña. '.
    'El usuario para entrar se arma solo a partir del nombre. La cuenta queda activa y sin un rol especial: entra al aula y ve los cursos públicos o los que le inscribas. '.
    'Si el correo ya existe, no se crea nada y no se modifica esa cuenta. La contraseña tiene que tener al menos 8 caracteres.'
)]
class CrearEstudiante extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        return LmsApi::post('students', [
            'name' => trim((string) $request->get('nombre')),
            'last_name' => trim((string) $request->get('apellidos')),
            'email' => trim((string) $request->get('correo')),
            'password' => (string) $request->get('contrasena'),
        ], [LmsPresent::class, 'estudiante']);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'nombre' => $schema->string()
                ->description('Nombre.')
                ->required(),
            'apellidos' => $schema->string()
                ->description('Apellidos.')
                ->required(),
            'correo' => $schema->string()
                ->description('Correo. Si ya existe en el aula, no se crea el estudiante.')
                ->required(),
            'contrasena' => $schema->string()
                ->description('Contraseña de al menos 8 caracteres.')
                ->required(),
        ];
    }
}
