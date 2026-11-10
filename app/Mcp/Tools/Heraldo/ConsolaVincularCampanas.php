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
    'Vincula al pedido campañas que ya existen en el cliente (por ejemplo, las que creó con crear-campana). Una campaña que no existe en ese cliente responde 422. '.
    'Si ya sabes qué Buzz va con cada campaña, mándalo en buzz_urls_json: la persona lo abre desde el paso 6. '.
    'Devuelve linked y el pedido con las piezas leídas de Heraldo.'
)]
class ConsolaVincularCampanas extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->consoleBody($request, [], [
            'campaign_ids_json' => 'campaign_ids',
            'buzz_urls_json' => 'buzz_urls',
        ]);

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
                ->description('Los ids de las campañas que se vinculan, como lista JSON: [44, 45]. Son campañas que ya existen en el cliente del pedido.')
                ->required(),
            'buzz_urls_json' => $schema->string()
                ->description('El archivo de Buzz de cada campaña, como objeto JSON con el id de la campaña y su enlace: {"44": "https://www.figma.com/…"}. Solo enlaces https de figma.com. Con null en una campaña, se borra su Buzz. Una campaña que ya está en el pedido también se puede mandar, para cambiarle el Buzz.'),
            ...$this->agentArgument($schema),
        ];
    }
}
