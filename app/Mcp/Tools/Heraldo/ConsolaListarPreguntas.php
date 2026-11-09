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

#[Name('consola-listar-preguntas')]
#[Description(
    'Las preguntas de un pedido, las más nuevas primero.'
)]
class ConsolaListarPreguntas extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $query = $this->consoleBody($request, [0 => 'status'], []);

        if ($query instanceof Response || $query instanceof ResponseFactory) {
            return $query;
        }

        return HeraldoApi::consoleGet($this->consolePath('orders/{order_id}/questions', $request), $query, $this->agentFromRequest($request));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()
                ->description('id del pedido, de consola-pendientes, consola-listar-pedidos o consola-crear-pedido.')
                ->required(),
            'status' => $schema->string()
                ->description('status'),
        ];
    }
}
