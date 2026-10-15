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

#[Name('actualizar-capitulo')]
#[Description(
    'Cambia el nombre de un capítulo, si los estudiantes lo ven, o el orden en que aparece. '.
    'El capítulo tiene que ser de ese curso. Para ocultarlo sin borrarlo, marcalo como no visible.'
)]
class ActualizarCapitulo extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [];

        if ($request->get('nombre') !== null) {
            $body['name'] = trim((string) $request->get('nombre'));
        }

        if ($request->get('visible') !== null) {
            $body['is_published'] = LmsPayload::flag($request->get('visible'));
        }

        if ($request->get('orden') !== null && $request->get('orden') !== '') {
            $body['order'] = (int) $request->get('orden');
        }

        if ($body === []) {
            return Response::error('Decí qué querés cambiar del capítulo.');
        }

        return LmsApi::patch(
            'courses/'.(int) $request->get('curso').'/chapters/'.(int) $request->get('capitulo'),
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
            'capitulo' => $schema->integer()
                ->description('Número del capítulo. Lo ves dentro del curso.')
                ->required(),
            'nombre' => $schema->string()
                ->description('Nombre nuevo.'),
            'visible' => $schema->boolean()
                ->description('Verdadero si los estudiantes lo ven. Falso si queda oculto.'),
            'orden' => $schema->integer()
                ->description('Lugar del capítulo en el curso. 1 es el primero.'),
        ];
    }
}
