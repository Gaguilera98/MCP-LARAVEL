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

#[Name('enlace-plugin-figma')]
#[Description(
    'Enlace para el plugin Heraldo Studio en Figma: la persona lo pega en el plugin y arma los artes con los copys de la campaña. '.
    'Responde url. Solo funciona con todos los copys completos (listar-copys). '.
    'Cuando los artes estén diseñados en Figma, se traen con importar-figma.'
)]
class EnlacePluginFigma extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->campaignPath($request, 'figma-plugin-link'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->campaignArguments($schema);
    }
}
