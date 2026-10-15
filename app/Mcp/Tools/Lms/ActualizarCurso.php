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

#[Name('actualizar-curso')]
#[Description(
    'Cambia datos de un curso que ya existe: nombre, descripción, si es privado, fecha de cierre, categorías o portada. '.
    'No toca los capítulos ni los pasos. '.
    'Si querés sacar la portada, dejá el número de portada vacío. '.
    'Si mandás categorías, reemplazan a las actuales; un texto vacío deja el curso sin categorías. El nombre nuevo no puede ser el de otro curso.'
)]
class ActualizarCurso extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $input = $request->all();
        unset($input['curso']);
        $body = LmsPayload::course($input, withChapters: false);

        if ($body instanceof Response) {
            return $body;
        }

        if ($body === []) {
            return Response::error('Decí qué querés cambiar del curso.');
        }

        return LmsApi::patch('courses/'.(int) $request->get('curso'), $body, [LmsPresent::class, 'curso']);
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
                ->description('Nombre nuevo. Si no lo mandás, queda el actual.'),
            'descripcion' => $schema->string()
                ->description('Descripción nueva.'),
            'privado' => $schema->boolean()
                ->description('Verdadero si solo lo ven los inscriptos. Falso si es público.'),
            'cierra_el' => $schema->string()
                ->description('Nueva fecha y hora de cierre, por ejemplo 2026-12-01 18:00.'),
            'categorias' => $schema->string()
                ->description('Números de categoría separados por coma. Reemplazan a las actuales. Vacío las quita.'),
            'portada' => $schema->string()
                ->description('Número de la portada nueva. Un texto vacío saca la portada actual.'),
        ];
    }
}
