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

#[Name('consola-senales')]
#[Description(
    'Lo que no funcionó en el pedido, para proponer aprendizajes al entregar: changes_requested (lo que la persona pidió cambiar, con su texto), rounds (rondas por paso), problems (lo que anotaste como problema), rejected_pieces (piezas rechazadas en Artes finales con su nota, hasta 200), regenerations (cuántas imágenes se rehicieron y cuánto costaron) y guidelines (los lineamientos que ya existen, para no repetirlos). Solo lee.'
)]
class ConsolaSenales extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $query = $this->consoleBody($request, [], []);

        if ($query instanceof Response || $query instanceof ResponseFactory) {
            return $query;
        }

        return HeraldoApi::consoleGet($this->consolePath('orders/{order_id}/signals', $request), $query, $this->agentFromRequest($request));
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
        ];
    }
}
