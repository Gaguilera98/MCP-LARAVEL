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

#[Name('subir-video')]
#[Description(
    'Sube el video de una clase que ya está en este equipo y te devuelve el número para usarlo en un paso de video. '.
    'Sirve para mp4, webm o mov, hasta 2 GB. El archivo se guarda solo: no hace falta pegar un enlace. '.
    'Un video de YouTube, Zoom u otra página no se sube por acá: se escribe como enlace al armar el paso.'
)]
class SubirVideo extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        return LmsApi::subirVideo((string) $request->get('ruta'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ruta' => $schema->string()
                ->description('Ruta completa del video en este equipo, por ejemplo C:\\clases\\semana-1.mp4.')
                ->required(),
        ];
    }
}
