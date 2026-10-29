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

#[Name('listar-tenants')]
#[Description(
    'Clientes de Heraldo. Empezá siempre por acá. Cada cliente trae id, name y domains: ese id es el tenant_id de todas las demás herramientas.'
)]
class ListarTenants extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get('tenants');
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
