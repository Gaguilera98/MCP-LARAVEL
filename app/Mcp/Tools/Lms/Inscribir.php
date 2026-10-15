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

#[Name('inscribir')]
#[Description(
    'Inscribe estudiantes en un curso usando su correo. También sirve si el curso es privado o ya cerró. '.
    'Si un correo no es de un estudiante o esa persona ya está inscripta, se salta y te dice por qué. El resto sí entra. '.
    'No vuelvas a intentar los que quedaron en omitidos.'
)]
class Inscribir extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $emails = LmsPayload::emails($request->get('correos'));

        if ($emails === []) {
            return Response::error('Indicá al menos un correo.');
        }

        return LmsApi::post(
            'courses/'.(int) $request->get('curso').'/enrollments',
            ['emails' => $emails],
            fn (array $json): array => LmsPresent::lote($json, 'enrolled', 'inscriptos'),
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
                ->description('Correos separados por coma, por ejemplo ana@ejemplo.com, luis@ejemplo.com.')
                ->required(),
        ];
    }
}
