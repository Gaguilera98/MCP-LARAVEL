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

#[Name('consola-registrar-corrida')]
#[Description(
    'Registra lo que costó una corrida de un agente en un paso. Lo manda el programa que lanza a los agentes (source launcher: queda medido) o el agente mismo al cerrar el paso (source agent: queda informado, sin medir). Se puede registrar en un pedido entregado; en uno cancelado, 409. No deja nada en la bitácora: se ve en la entrega.'
)]
class ConsolaRegistrarCorrida extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->consoleBody($request, [0 => 'step',  1 => 'usd',  2 => 'agent',  3 => 'model',  4 => 'input_tokens',  5 => 'output_tokens',  6 => 'source',  7 => 'note'], []);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::consolePost($this->consolePath('orders/{order_id}/agent-runs', $request), $body, $this->agentFromRequest($request));
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
            'step' => $schema->string()
                ->enum(['order', 'campaigns', 'design', 'content', 'templates', 'plugin', 'review', 'delivery'])
                ->description('El paso: order (1 Pedido), campaigns (2 Campañas), design (3 Diseño madre), content (4 Fotos y textos), templates (5 Plantillas), plugin (6 Plugin), review (7 Revisión), delivery (8 Entrega).')
                ->required(),
            'usd' => $schema->number()
                ->min(0)
                ->max(10000)
                ->description('Lo que costó, en dólares.')
                ->required(),
            'agent' => $schema->string()
                ->description('Quién corrió, por ejemplo «Claude Code · agente de Heraldo». Si no viene, el de la cabecera o el que hace el paso.'),
            'model' => $schema->string()
                ->description('model'),
            'input_tokens' => $schema->integer()
                ->description('input_tokens'),
            'output_tokens' => $schema->integer()
                ->description('output_tokens'),
            'source' => $schema->string()
                ->enum(['launcher', 'agent', 'person'])
                ->description('launcher, agent o person. Por defecto, agent.'),
            'note' => $schema->string()
                ->max(500)
                ->description('Hasta 500 caracteres.'),
            ...$this->agentArgument($schema),
        ];
    }
}
