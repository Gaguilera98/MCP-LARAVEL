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

#[Name('consola-editar-marca')]
#[Description(
    'Cambia la ficha de una marca: lo que se manda reemplaza lo que había (notes también), así que lee antes con consola-ver-marca. El cliente no cambia. Los lineamientos no se tocan por aquí.'
)]
class ConsolaEditarMarca extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->consoleBody($request, [0 => 'name',  1 => 'instructions',  2 => 'tone',  3 => 'legal_text',  4 => 'notes'], ['figma_links_json' => 'figma_links']);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::consolePatch($this->consolePath('brands/{brand_id}', $request), $body, $this->agentFromRequest($request));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'brand_id' => $schema->integer()
                ->description('brand_id')
                ->required(),
            'name' => $schema->string()
                ->description('name'),
            'figma_links_json' => $schema->string()
                ->description('Enlaces de Figma: cada uno {label, url}. Solo https.'),
            'instructions' => $schema->string()
                ->description('instructions'),
            'tone' => $schema->string()
                ->description('Trato: tu, vos, usted o mixto.'),
            'legal_text' => $schema->string()
                ->description('legal_text'),
            'notes' => $schema->string()
                ->description('notes'),
        ];
    }
}
