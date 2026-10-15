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

#[Name('actualizar-paso')]
#[Description(
    'Cambia el nombre, si es opcional o el contenido de un paso. '.
    'El paso tiene que ser de ese capítulo. Si cambiás el tipo, mandá también el texto, el archivo o el enlace que corresponda.'
)]
class ActualizarPaso extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $input = $request->all();
        unset($input['curso'], $input['capitulo'], $input['paso']);
        $body = LmsPayload::step($input);

        if ($body instanceof Response) {
            return $body;
        }

        if ($body === []) {
            return Response::error('Decí qué querés cambiar del paso.');
        }

        return LmsApi::patch(
            'courses/'.(int) $request->get('curso').'/chapters/'.(int) $request->get('capitulo').'/steps/'.(int) $request->get('paso'),
            $body,
            [LmsPresent::class, 'paso'],
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
                ->description('Número del paso.')
                ->required(),
            'nombre' => $schema->string()
                ->description('Nombre nuevo.'),
            'tipo' => $schema->string()
                ->description('texto, video, documento, imagen o enlace.'),
            'texto' => $schema->string()
                ->description('Texto nuevo del paso.'),
            'archivo' => $schema->integer()
                ->description('Número del video, la imagen o el documento.'),
            'enlace' => $schema->string()
                ->description('Dirección nueva.'),
            'opcional' => $schema->boolean()
                ->description('Verdadero si se puede saltear. Falso si pasa a ser obligatorio.'),
        ];
    }
}
