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

#[Name('quitar-inscripciones')]
#[Description(
    'Saca del curso a las personas que indiques por correo. No borra su ficha de estudiante. '.
    'Si alguien no estaba inscripto, se avisa y se sigue con el resto.'
)]
class QuitarInscripciones extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $emails = LmsPayload::emails($request->get('correos'));

        if ($emails === []) {
            return Response::error('Indicá al menos un correo.');
        }

        return LmsApi::delete(
            'courses/'.(int) $request->get('curso').'/enrollments',
            ['emails' => $emails],
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
            'correos' => $schema->string()
                ->description('Correos separados por coma.')
                ->required(),
        ];
    }
}
