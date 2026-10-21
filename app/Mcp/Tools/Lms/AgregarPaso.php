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

#[Name('agregar-paso')]
#[Description(
    'Agrega un paso al final de un capítulo. Un paso es una pieza del capítulo: un texto, un video, un documento, una imagen o un enlace. '.
    'El video propio se sube con subir-video y se usa ese número. Una imagen o un documento solo se usan si ya tienen número en la biblioteca: desde acá no se suben. El enlace es una dirección, como YouTube, Zoom o una página. '.
    'Si el archivo no existe o no es del tipo pedido, el paso no se agrega y te dice por qué.'
)]
class AgregarPaso extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $body = LmsPayload::step($request->all());

        if ($body instanceof Response) {
            return $body;
        }

        unset($body['order']);

        return LmsApi::post(
            'courses/'.(int) $request->get('curso').'/chapters/'.(int) $request->get('capitulo').'/steps',
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
                ->description('Número del capítulo donde va el paso.')
                ->required(),
            'nombre' => $schema->string()
                ->description('Nombre del paso.')
                ->required(),
            'tipo' => $schema->string()
                ->description('texto, video, documento, imagen o enlace.')
                ->required(),
            'texto' => $schema->string()
                ->description('Contenido cuando el tipo es texto.'),
            'archivo' => $schema->integer()
                ->description('Número de un video de subir-video, o de una imagen o documento que ya está en la biblioteca. Si un video trae archivo y enlace, se usa el archivo.'),
            'enlace' => $schema->string()
                ->description('Dirección cuando el tipo es enlace, o un video de YouTube u otra página.'),
            'opcional' => $schema->boolean()
                ->description('Verdadero si el estudiante puede saltearlo. Si no lo indicás, es obligatorio.'),
        ];
    }
}
