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

#[Name('listar-estudiantes')]
#[Description(
    'Muestra los estudiantes, del más nuevo al más viejo: nombre, apellidos, correo, usuario con el que entra y si la cuenta está activa. '.
    'Si hay_mas es verdadero, pedí la página siguiente.'
)]
class ListarEstudiantes extends Tool
{
    use Paginates;

    public function handle(Request $request): Response|ResponseFactory
    {
        return LmsApi::get('students', LmsApi::pageQuery($request), fn (array $json): array => LmsPresent::pagina($json, [LmsPresent::class, 'estudiante']));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->pageArguments($schema);
    }
}
