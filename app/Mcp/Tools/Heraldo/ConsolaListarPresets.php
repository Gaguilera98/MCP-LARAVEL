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

#[Name('consola-listar-presets')]
#[Description(
    'Los presets del cliente en cualquier estado (borrador, publicado, archivado), con su clave y versión. Úsala antes de crear un preset, para no repetir uno que ya existe.'
)]
class ConsolaListarPresets extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $query = $this->consoleBody($request, [], []);

        if ($query instanceof Response || $query instanceof ResponseFactory) {
            return $query;
        }

        return HeraldoApi::consoleGet($this->consolePath('tenants/{tenant_id}/presets', $request), $query, $this->agentFromRequest($request));
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
        ];
    }
}
