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

#[Name('quitar-paso')]
#[Description(
    'Quita un paso solo si ningún estudiante lo abrió. '.
    'Si alguien ya lo empezó, no se borra. En ese caso ocultá el capítulo para que dejen de verlo.'
)]
class QuitarPaso extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        return LmsApi::delete(
            'courses/'.(int) $request->get('curso').'/chapters/'.(int) $request->get('capitulo').'/steps/'.(int) $request->get('paso'),
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
                ->description('Número del capítulo.')
                ->required(),
            'paso' => $schema->integer()
                ->description('Número del paso que querés quitar.')
                ->required(),
        ];
    }
}
