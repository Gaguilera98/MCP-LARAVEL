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

#[Name('asignar-tutores')]
#[Description(
    'Asigna tutores que ya existen a un capítulo. No quita a los que ya estaban. '.
    'Si el número no existe o ese tutor ya está en el capítulo, se salta y te dice por qué. '.
    'No crea la ficha del tutor.'
)]
class AsignarTutores extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $ids = LmsPayload::ids($request->get('tutores'));

        if ($ids === []) {
            return Response::error('Indicá al menos un tutor.');
        }

        return LmsApi::post(
            'courses/'.(int) $request->get('curso').'/chapters/'.(int) $request->get('capitulo').'/tutors',
            ['tutor_ids' => $ids],
            fn (array $json): array => LmsPresent::lote($json, 'assigned', 'asignados'),
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
                ->description('Números de tutor separados por coma. Los ves con listar-tutores.')
                ->required(),
        ];
    }
}
