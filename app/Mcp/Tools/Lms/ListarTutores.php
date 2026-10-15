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

#[Name('listar-tutores')]
#[Description(
    'Muestra los tutores que ya existen, del más nuevo al más viejo: nombre, apellidos, correo y si están activos. '.
    'No crea tutores. La ficha se da de alta en el panel del aula. '.
    'Si hay_mas es verdadero, pedí la página siguiente.'
)]
class ListarTutores extends Tool
{
    use Paginates;

    public function handle(Request $request): Response|ResponseFactory
    {
        return LmsApi::get('tutors', LmsApi::pageQuery($request), fn (array $json): array => LmsPresent::pagina($json, [LmsPresent::class, 'tutor']));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->pageArguments($schema);
    }
}
