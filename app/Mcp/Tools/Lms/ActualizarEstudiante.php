<?php

namespace App\Mcp\Tools\Lms;

use App\Support\LmsApi;
use App\Support\LmsPayload;
use App\Support\LmsPresent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('actualizar-estudiante')]
#[Description(
    'Cambia el nombre, los apellidos, si la cuenta está activa o la contraseña de un estudiante. '.
    'El correo no se cambia. No le asigna un rol.'
)]
class ActualizarEstudiante extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [];

        if ($request->get('nombre') !== null) {
            $body['name'] = trim((string) $request->get('nombre'));
        }

        if ($request->get('apellidos') !== null) {
            $body['last_name'] = trim((string) $request->get('apellidos'));
        }

        if ($request->get('activo') !== null) {
            $body['is_active'] = LmsPayload::flag($request->get('activo'));
        }

        if ($request->get('contrasena') !== null && $request->get('contrasena') !== '') {
            $body['password'] = (string) $request->get('contrasena');
        }

        if ($body === []) {
            return Response::error('Decí qué querés cambiar del estudiante.');
        }

        return LmsApi::patch('students/'.(int) $request->get('estudiante'), $body, [LmsPresent::class, 'estudiante']);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'estudiante' => $schema->integer()
                ->description('Número del estudiante.')
                ->required(),
            'nombre' => $schema->string()
                ->description('Nombre nuevo.'),
            'apellidos' => $schema->string()
                ->description('Apellidos nuevos.'),
            'activo' => $schema->boolean()
                ->description('Falso si la cuenta no puede entrar. Verdadero si vuelve a poder entrar.'),
            'contrasena' => $schema->string()
                ->description('Contraseña nueva, de al menos 8 caracteres.'),
        ];
    }
}
