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

#[Name('consola-crear-marca')]
#[Description(
    'Crea la ficha de una marca: enlaces de Figma (sistema de diseño, plantillas, referencias), instrucciones, trato y legal. Cada pedido de esa marca los suma. Los lineamientos no se crean por aquí: los escribe una persona en la consola o salen de los aprendizajes que propones con consola-preguntar.'
)]
class ConsolaCrearMarca extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->consoleBody($request, [0 => 'tenant_id',  1 => 'name',  2 => 'instructions',  3 => 'tone',  4 => 'legal_text',  5 => 'notes'], ['figma_links_json' => 'figma_links']);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::consolePost($this->consolePath('brands', $request), $body, $this->agentFromRequest($request));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'tenant_id' => $schema->string()
                ->description('id del cliente, de listar-tenants.')
                ->required(),
            'name' => $schema->string()
                ->description('name')
                ->required(),
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
