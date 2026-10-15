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

#[Name('listar-inscripciones')]
#[Description(
    'Muestra quién está inscripto en un curso, de la inscripción más reciente a la más antigua. '.
    'De cada persona ves cuándo entró, la nota, el comentario del diploma, si empezó, el avance y si terminó. '.
    'El avance es solo para mirar. Si hay_mas es verdadero, pedí la página siguiente.'
)]
class ListarInscripciones extends Tool
{
    use Paginates;

    public function handle(Request $request): Response|ResponseFactory
    {
        return LmsApi::get(
            'courses/'.(int) $request->get('curso').'/enrollments',
            LmsApi::pageQuery($request),
            fn (array $json): array => LmsPresent::pagina($json, [LmsPresent::class, 'inscripcion']),
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'curso' => $schema->integer()
                ->description('Número del curso.')
                ->required(),
            ...$this->pageArguments($schema),
        ];
    }
}
