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

#[Name('consola-anotar')]
#[Description(
    'Deja una línea en la bitácora del pedido: un avance, una decisión o un problema. La persona la ve en la consola.'
)]
class ConsolaAnotar extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->consoleBody($request, [0 => 'text',  1 => 'step',  2 => 'kind'], []);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::consolePost($this->consolePath('orders/{order_id}/events', $request), $body, $this->agentFromRequest($request));
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
            'text' => $schema->string()
                ->description('Hasta 2.000 caracteres.')
                ->required(),
            'step' => $schema->string()
                ->description('El paso: order (1 Pedido), campaigns (2 Campañas), design (3 Diseño madre), content (4 Fotos y textos), templates (5 Plantillas), plugin (6 Plugin), review (7 Revisión), delivery (8 Entrega).'),
            'kind' => $schema->string()
                ->description('note, progress, decision o problem. Por defecto, note.'),
            ...$this->agentArgument($schema),
        ];
    }
}
