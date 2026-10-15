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

#[Name('listar-cursos')]
#[Description(
    'Muestra los cursos, del más nuevo al más viejo. '.
    'Cada uno trae su número, si es privado, cuándo cierra, la portada, las categorías, los capítulos y los pasos. '.
    'Sirve para encontrar el número de un curso antes de cambiarlo, inscribir gente o cargar notas. '.
    'Si hay_mas es verdadero, pedí la página siguiente.'
)]
class ListarCursos extends Tool
{
    use Paginates;

    public function handle(Request $request): Response|ResponseFactory
    {
        return LmsApi::get('courses', LmsApi::pageQuery($request), fn (array $json): array => LmsPresent::pagina($json, [LmsPresent::class, 'curso']));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->pageArguments($schema);
    }
}
