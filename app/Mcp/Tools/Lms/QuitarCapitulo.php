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

#[Name('quitar-capitulo')]
#[Description(
    'Quita un capítulo y sus pasos solo si ningún estudiante abrió ese material. '.
    'Si alguien ya lo empezó, no se borra: ocultalo con actualizar-capitulo marcándolo como no visible.'
)]
class QuitarCapitulo extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        return LmsApi::delete(
            'courses/'.(int) $request->get('curso').'/chapters/'.(int) $request->get('capitulo'),
            present: [LmsPresent::class, 'quitado'],
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
            'capitulo' => $schema->integer()
                ->description('Número del capítulo que querés quitar.')
                ->required(),
        ];
    }
}
