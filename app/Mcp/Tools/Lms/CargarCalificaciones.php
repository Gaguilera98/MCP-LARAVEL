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

#[Name('cargar-calificaciones')]
#[Description(
    'Carga la nota y un comentario corto en el diploma de quienes ya están inscriptos en el curso. '.
    'Las notas son A, B, C o D. El comentario puede tener hasta 80 caracteres y se lee entero en el diploma. '.
    'Mandá siempre la nota y el comentario de esa persona: si uno va vacío, se borra ese dato. '.
    'Quien no esté inscripto, o tenga una nota que no sea A, B, C o D, se salta y te dice por qué. '.
    'No arma el pdf: el diploma del aula lo muestra cuando la persona terminó el curso y tiene nota.'
)]
class CargarCalificaciones extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $grades = LmsPayload::grades($request->get('notas'));

        if ($grades instanceof Response) {
            return $grades;
        }

        if ($grades === []) {
            return Response::error('Indicá al menos una nota.');
        }

        return LmsApi::post(
            'courses/'.(int) $request->get('curso').'/grades',
            ['grades' => $grades],
            fn (array $json): array => LmsPresent::lote($json, 'saved', 'guardadas'),
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
            'notas' => $schema->string()
                ->description(
                    'Lista de notas. Cada una tiene correo, calificacion (A, B, C o D) y comentario. '.
                    'Ejemplo: [{"correo":"ana@ejemplo.com","calificacion":"A","comentario":"Tiene estructura, pensamiento y ejecución."}]'
                )
                ->required(),
        ];
    }
}
