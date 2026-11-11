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

#[Name('listar-piezas')]
#[Description(
    'Los artes de la campaña, ya importados de Figma. Cada uno trae id (para revisar-piezas), name, platform, format, product, has_image, '.
    'review_status (in_review, approved o rejected (en Heraldo, «Ajuste pedido»)), review_note, has_layers, animation (la receta) y video_status. '.
    'counts resume cuántos hay en cada estado de revisión. Los filtros son opcionales; sin filtros vienen todos. '.
    'Cada arte trae preview_url, para verlo con ver-imagen (vence a las 24 h). Para revisar muchos juntos, hoja-piezas.'
)]
class ListarPiezas extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->campaignPath($request, 'pieces'), $this->withFilter($request));
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
