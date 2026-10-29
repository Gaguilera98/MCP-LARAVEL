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

#[Name('extraer-capas')]
#[Description(
    'Lee desde Figma las capas de cada arte (foto, titular, logo, CTA…). Es el primer paso para animar: sin capas no hay receta ni video. '.
    'No gasta. Queda en cola: seguí in_progress.layer_extraction en estado-produccion; después listar-piezas muestra has_layers. '.
    'Sin filtros toma todos los artes importados de Figma. Las animaciones tienen que estar habilitadas para ese cliente.'
)]
class ExtraerCapas extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::post($this->campaignPath($request, 'animations/layers'), $this->withFilter($request));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            ...$this->filterArguments($schema),
        ];
    }
}
