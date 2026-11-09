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

#[Name('consola-crear-pedido')]
#[Description(
    'Crea un pedido con sus 8 pasos. skip_steps marca los que no aplican (por ejemplo design si las madres ya están aprobadas). notify_emails reciben un correo cada vez que el pedido necesita a una persona. campaign_ids vincula campañas que ya existen.'
)]
class ConsolaCrearPedido extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->consoleBody($request, [0 => 'tenant_id',  1 => 'name',  2 => 'brand_id',  3 => 'due_on',  4 => 'summary',  5 => 'instructions'], ['figma_links_json' => 'figma_links',  'notify_emails_json' => 'notify_emails',  'skip_steps_json' => 'skip_steps',  'campaign_ids_json' => 'campaign_ids']);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::consolePost($this->consolePath('orders', $request), $body, $this->agentFromRequest($request));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'tenant_id' => $schema->string()
                ->description('id del cliente, de listar-tenants.')
                ->required(),
            'name' => $schema->string()
                ->description('Nombre del pedido. Hasta 250 caracteres.')
                ->required(),
            'brand_id' => $schema->integer()
                ->description('La ficha de marca, del mismo cliente.'),
            'due_on' => $schema->string()
                ->description('Plazo, AAAA-MM-DD.'),
            'summary' => $schema->string()
                ->description('El resumen del pedido en Markdown: campañas, piezas, fotos, lo que falta confirmar.'),
            'instructions' => $schema->string()
                ->description('instructions'),
            'figma_links_json' => $schema->string()
                ->description('Enlaces de Figma: cada uno {label, url}. Solo https.'),
            'notify_emails_json' => $schema->string()
                ->description('notify_emails'),
            'skip_steps_json' => $schema->string()
                ->description('skip_steps'),
            'campaign_ids_json' => $schema->string()
                ->description('campaign_ids'),
            ...$this->agentArgument($schema),
        ];
    }
}
