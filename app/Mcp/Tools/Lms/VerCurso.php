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

#[Name('ver-curso')]
#[Description(
    'Muestra un curso completo: descripción, si es privado, fecha de cierre, portada, categorías, capítulos con sus tutores y pasos. '.
    'Usalo cuando necesités el número de un capítulo o de un paso.'
)]
class VerCurso extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        return LmsApi::get('courses/'.(int) $request->get('curso'), present: [LmsPresent::class, 'curso']);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'curso' => $schema->integer()
                ->description('Número del curso. Lo obtenés con listar-cursos.')
                ->required(),
        ];
    }
}
