<?php

namespace App\Mcp\Tools\Lms;

use App\Support\LmsApi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('subir-archivo')]
#[Description(
    'Guarda un archivo que ya está en este equipo para usarlo después en un curso. '.
    'Decí si es la portada del curso, una imagen dentro de un paso o un documento (guía, pdf, presentación, planilla, zip o texto). '.
    'Te devuelve un número: ese número es el que tenés que poner al armar el curso o el paso. '.
    'La portada puede pesar hasta 2 MB (jpg, png, webp o gif). Una imagen de paso, hasta 10 MB, los mismos formatos. Un documento, hasta 20 MB.'
)]
class SubirArchivo extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        return LmsApi::subirArchivo((string) $request->get('ruta'), (string) $request->get('uso'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ruta' => $schema->string()
                ->description('Ruta completa del archivo en este equipo, por ejemplo C:\\clases\\guia.pdf.')
                ->required(),
            'uso' => $schema->string()
                ->description('portada, imagen o documento.')
                ->required(),
        ];
    }
}
