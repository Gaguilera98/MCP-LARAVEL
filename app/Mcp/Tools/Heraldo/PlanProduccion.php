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

#[Name('plan-produccion')]
#[Description(
    'La grilla de fotos de la campaña. Cada fila de rows es un producto × ángulo × foto y trae strategy_id, image_index, product, variation y angle. '.
    'Cada columna de columns es una proporción, como 1:1 o 9:16; la que tiene is_base true es la columna base. '.
    'En cada fila, cells dice por proporción si la casilla está vacía (empty), en proceso o con foto (has_image). '.
    'De acá salen strategy_id, image_index y ratio para subir-foto y usar-columna-base. '.
    'Cada casilla con foto trae preview_url, para verla con ver-imagen (vence a las 24 h). Para revisar muchas juntas, hoja-fotos. '.
    'Para rehacer una sola casilla, regenerar-casilla; para borrarla, quitar-foto. Hacia dónde crece la foto en las adaptaciones: ver-direcciones.'
)]
class PlanProduccion extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->campaignPath($request, 'production/plan'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->campaignArguments($schema);
    }
}
