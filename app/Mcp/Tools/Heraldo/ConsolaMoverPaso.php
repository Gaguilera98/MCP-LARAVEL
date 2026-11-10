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

#[Name('consola-mover-paso')]
#[Description(
    'Cambia el estado de un paso: agent cuando el agente empieza, done cuando termina, skipped si no aplica, pending para volver atrás. note dice qué quedó hecho o qué falta. agent y done solo cuando los pasos anteriores están cerrados (las plantillas no esperan a fotos y textos); si no, 409 dice cuáles faltan. skipped y pending se pueden siempre. El plugin (plugin) no lo marca un agente: lo marca la persona en la consola (422). «Te necesita» no se marca: sale solo mientras haya una pregunta abierta. Marcar delivery como done entrega el pedido y retira lo que quedó abierto; si hay una aprobación de aprendizajes abierta responde 409: espera la respuesta de la persona. En pausa responde 409. '.
    'Un paso que no aplica a este pedido se marca skipped en cuanto se sabe, por ejemplo design y templates cuando el cliente ya trae sus diseños: decirlo solo en una nota no alcanza, porque el paso sigue en pending y el pedido se queda esperando en él.'
)]
class ConsolaMoverPaso extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->consoleBody($request, [0 => 'state',  1 => 'note'], []);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::consolePut($this->consolePath('orders/{order_id}/steps/{step}', $request), $body, $this->agentFromRequest($request));
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
                ->enum(['order', 'campaigns', 'design', 'content', 'templates', 'review', 'delivery'])
                ->description('El paso: order (1 Pedido), campaigns (2 Campañas), design (3 Diseño madre), content (4 Fotos y textos), templates (5 Plantillas), review (7 Revisión), delivery (8 Entrega). Sin plugin: ese lo marca la persona.')
                ->required(),
            'state' => $schema->string()
                ->enum(['pending', 'agent', 'done', 'skipped'])
                ->description('pending, agent, done o skipped.')
                ->required(),
            'note' => $schema->string()
                ->max(2000)
                ->description('Hasta 2.000 caracteres.'),
            ...$this->agentArgument($schema),
        ];
    }
}
