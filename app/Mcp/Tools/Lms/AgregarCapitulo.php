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

#[Name('agregar-capitulo')]
#[Description(
    'Agrega un capítulo al final de un curso. Un capítulo es una parte del curso, como una semana o un módulo. '.
    'Podés incluir los pasos en el mismo momento. '.
    'Si un archivo de un paso no sirve, el capítulo no se agrega y te dice por qué.'
)]
class AgregarCapitulo extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [
            'name' => trim((string) $request->get('nombre')),
        ];

        if ($request->get('visible') !== null) {
            $body['is_published'] = LmsPayload::flag($request->get('visible'));
        }

        $steps = $request->get('pasos');

        if ($steps !== null && $steps !== '') {
            $decoded = LmsPayload::decode($steps, 'La lista de pasos');

            if ($decoded instanceof Response) {
                return $decoded;
            }

            $chapters = LmsPayload::chapters([[
                'nombre' => $body['name'],
                'pasos' => $decoded,
            ]]);

            if ($chapters instanceof Response) {
                return $chapters;
            }

            $body['steps'] = $chapters[0]['steps'] ?? [];
        }

        return LmsApi::post(
            'courses/'.(int) $request->get('curso').'/chapters',
            $body,
            [LmsPresent::class, 'capitulo'],
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
            'nombre' => $schema->string()
                ->description('Nombre del capítulo, por ejemplo Semana 1.')
                ->required(),
            'visible' => $schema->boolean()
                ->description('Falso si los estudiantes todavía no deben verlo. Si no lo indicás, se ve.'),
            'pasos' => $schema->string()
                ->description(
                    'Pasos del capítulo, si ya los tenés. Cada uno tiene nombre, tipo (texto, video, documento, imagen o enlace) y texto, archivo o enlace. '.
                    'Ejemplo: [{"nombre":"Clase","tipo":"video","archivo":8}]'
                ),
        ];
    }
}
