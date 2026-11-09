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

#[Name('consola-estimar')]
#[Description(
    'Deja el previsto del pedido: tú mandas cantidades (imágenes base, adaptaciones, textos con IA, piezas y videos) y Heraldo pone los precios, los suyos y la tarifa fija de los agentes por paso que aplica a este pedido. Devuelve las líneas y los totales (heraldo_usd, agents_usd, total_usd). Hazlo en el paso 1, con el resumen, y ponlo en el cuerpo de la aprobación del resumen. Se congela al cerrar el paso 1 (y si llega con el paso 1 ya cerrado, queda congelado al guardarlo): después responde 409, y solo una persona lo descongela desde la consola. En pausa, 409.'
)]
class ConsolaEstimar extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->consoleBody($request, [0 => 'base_images',  1 => 'adaptations',  2 => 'copy_texts',  3 => 'pieces',  4 => 'videos',  5 => 'note'], []);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::consolePost($this->consolePath('orders/{order_id}/estimate', $request), $body, $this->agentFromRequest($request));
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
            'base_images' => $schema->integer()
                ->description('Imágenes base que se generan con IA (una por producto y proporción). Las fotos que manda el cliente no cuentan.'),
            'adaptations' => $schema->integer()
                ->description('Adaptaciones de tamaño con IA.'),
            'copy_texts' => $schema->integer()
                ->description('Textos que escribe la IA (copys).'),
            'pieces' => $schema->integer()
                ->description('Piezas finales (para los textos de publicación).'),
            'videos' => $schema->integer()
                ->description('Videos que se animan.'),
            'note' => $schema->string()
                ->description('De dónde salen las cantidades, en una línea. Hasta 500 caracteres.'),
            ...$this->agentArgument($schema),
        ];
    }
}
