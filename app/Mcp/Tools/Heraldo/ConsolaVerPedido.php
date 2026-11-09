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

#[Name('consola-ver-pedido')]
#[Description(
    'Todo el pedido: resumen, enlaces de Figma (de la ficha de la marca y del pedido, con from), instrucciones, trato, legal y notas de la marca, guidelines (los lineamientos de la marca que aplican a este pedido: respétalos siempre), estimate (lo previsto, congelado al cerrar el paso 1), costs (lo gastado: Heraldo con su desglose más los agentes, contra lo previsto), los 8 pasos con su estado y nota, ready_steps, las campañas con su enlace a Heraldo, las preguntas, la bitácora y los archivos con su enlace firmado. heraldo trae piezas, revisión, videos y gasto de las campañas, leídos de Heraldo al llamar (un pedido entregado ya no se vuelve a leer).'
)]
class ConsolaVerPedido extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $query = $this->consoleBody($request, [0 => 'refresh'], []);

        if ($query instanceof Response || $query instanceof ResponseFactory) {
            return $query;
        }

        return HeraldoApi::consoleGet($this->consolePath('orders/{order_id}', $request), $query, $this->agentFromRequest($request));
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
            'refresh' => $schema->boolean()
                ->description('false no vuelve a leer Heraldo (más rápido). Por defecto, true.'),
        ];
    }
}
