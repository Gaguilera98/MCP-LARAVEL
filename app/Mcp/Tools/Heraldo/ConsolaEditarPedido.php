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

#[Name('consola-editar-pedido')]
#[Description(
    'Cambia datos del pedido: nombre, plazo, resumen, instrucciones, enlaces o a quién avisar. Solo lo que se manda cambia. La marca del pedido no se cambia por aquí: la cambia una persona en la consola (con ella cambian los lineamientos). Un pedido en pausa o cerrado responde 409.'
)]
class ConsolaEditarPedido extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->consoleBody($request, [0 => 'name',  1 => 'due_on',  2 => 'summary',  3 => 'instructions'], ['figma_links_json' => 'figma_links',  'notify_emails_json' => 'notify_emails']);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::consolePatch($this->consolePath('orders/{order_id}', $request), $body, $this->agentFromRequest($request));
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
            'name' => $schema->string()
                ->description('name'),
            'due_on' => $schema->string()
                ->description('due_on'),
            'summary' => $schema->string()
                ->description('summary'),
            'instructions' => $schema->string()
                ->description('instructions'),
            'figma_links_json' => $schema->string()
                ->description('Enlaces de Figma: cada uno {label, url}. Solo https.'),
            'notify_emails_json' => $schema->string()
                ->description('notify_emails'),
            ...$this->agentArgument($schema),
        ];
    }
}
