<?php

namespace App\Mcp\Tools\Heraldo;

use App\Support\HeraldoApi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('pedir-exportacion')]
#[Description(
    'Arma un ZIP para descargar. type pieces: las imágenes de los artes. type videos: los MP4 que ya están listos. '.
    'Si no indicas review, entra solo lo aprobado. No gasta. Solo puede haber un ZIP de cada tipo armándose a la vez. '.
    'Queda en cola: responde cuántos archivos lleva (files), y el enlace aparece en listar-exportaciones cuando está listo.'
)]
class PedirExportacion extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->withFilter($request, [
            'type' => $request->get('type'),
        ]);

        return HeraldoApi::post($this->campaignPath($request, 'exports'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'type' => $schema->string()
                ->enum(['pieces', 'videos'])
                ->description('pieces (imágenes de los artes) o videos (MP4).')
                ->required(),
            ...$this->filterArguments($schema),
        ];
    }
}
