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

#[Name('quitar-tutores')]
#[Description(
    'Saca tutores de un capítulo. No borra la ficha del tutor. '.
    'Si no estaba en ese capítulo, se avisa y se sigue con el resto.'
)]
class QuitarTutores extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $ids = LmsPayload::ids($request->get('tutores'));

        if ($ids === []) {
            return Response::error('Indicá al menos un tutor.');
        }

        return LmsApi::delete(
            'courses/'.(int) $request->get('curso').'/chapters/'.(int) $request->get('capitulo').'/tutors',
            ['tutor_ids' => $ids],
            fn (array $json): array => LmsPresent::lote($json, 'removed', 'quitados'),
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
            'tutores' => $schema->string()
                ->description('Números de tutor separados por coma.')
                ->required(),
        ];
    }
}
