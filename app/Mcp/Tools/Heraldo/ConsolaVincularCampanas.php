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

#[Name('consola-vincular-campanas')]
#[Description(
    'Vincula al pedido campañas que ya existen en el cliente (por ejemplo, las que creó con crear-campana). Una campaña que no existe en ese cliente responde 422. Devuelve linked y el pedido con las piezas leídas de Heraldo.'
)]
class ConsolaVincularCampanas extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->consoleBody($request, [], ['campaign_ids_json' => 'campaign_ids']);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::consolePost($this->consolePath('orders/{order_id}/campaigns', $request), $body, $this->agentFromRequest($request));
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
            'campaign_ids_json' => $schema->string()
                ->description('campaign_ids')
                ->required(),
            ...$this->agentArgument($schema),
        ];
    }
}
