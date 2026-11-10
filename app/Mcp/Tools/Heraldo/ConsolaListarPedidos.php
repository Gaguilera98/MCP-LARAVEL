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

#[Name('consola-listar-pedidos')]
#[Description(
    'Pedidos de la consola, los más recientes primero. Por defecto, los abiertos (en curso y en pausa).'
)]
class ConsolaListarPedidos extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $query = $this->consoleBody($request, [0 => 'status',  1 => 'tenant_id',  2 => 'page',  3 => 'per_page'], []);

        if ($query instanceof Response || $query instanceof ResponseFactory) {
            return $query;
        }

        return HeraldoApi::consoleGet($this->consolePath('orders', $request), $query, $this->agentFromRequest($request));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(['open', 'active', 'paused', 'delivered', 'cancelled'])
                ->description('Filtra por estado: open, active, paused, delivered o cancelled. Por defecto, open.'),
            'tenant_id' => $schema->string()
                ->description('Solo los de este cliente.'),
            'page' => $schema->integer()
                ->min(1)
                ->description('Número de página. Empieza en 1.'),
            'per_page' => $schema->integer()
                ->min(1)
                ->max(100)
                ->description('Hasta 100. Por defecto, 25.'),
        ];
    }
}
