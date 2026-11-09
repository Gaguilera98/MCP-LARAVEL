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

#[Name('regenerar-casilla')]
#[Description(
    'Rehace la foto de una sola casilla del paso 2, como «Regenerar» de la página: en la columna base pide a Santo la foto base; en otra columna, la adaptación desde la foto base. Si la casilla está vacía, la genera. Sirve para arreglar una foto rechazada sin rehacer todas. Gasta: exige max_usd; sin max_usd responde 422 con estimate_usd y santo_balance_usd sin pedir nada (la foto base se estima con el modelo más caro de la campaña). Responde 409 sin pedir nada, con un message que dice qué hacer, si: lo estimado supera max_usd o el saldo de Santo; la foto la subió una persona (esa no se pisa: cámbiala con subir-foto o quítala con quitar-foto); la campaña no tiene lote de Santo (primero generar-fotos-base o pedir-adaptaciones); algo se está generando en la campaña o hay un pedido en cola (espera con estado-produccion); en una adaptación, la foto base de la fila no está lista; hay otro pedido a Santo en curso (reintenta en un momento); o la producción está finalizada. Responde 202: el avance se sigue con estado-produccion y la foto nueva se ve con plan-produccion.'
)]
class RegenerarCasilla extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [
            'strategy_id' => (int) $request->get('strategy_id'),
            'image_index' => (int) $request->get('image_index'),
            'ratio' => $request->get('ratio'),
        ];
        $maxUsd = $this->optionalNumber($request, 'max_usd');

        if ($maxUsd !== null) {
            $body['max_usd'] = $maxUsd;
        }

        return HeraldoApi::post($this->campaignPath($request, 'production/cells/regenerate'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'strategy_id' => $schema->integer()
                ->description('strategy_id de la fila, como sale en plan-produccion (rows).')
                ->required(),
            'image_index' => $schema->integer()
                ->description('image_index de la fila (1, 2…), como sale en plan-produccion (rows).')
                ->required(),
            'ratio' => $schema->string()
                ->description('La columna, como sale en plan-produccion: «4:5», «9:16». En la columna base es la foto base; en las demás, la adaptación.')
                ->required(),
            'max_usd' => $schema->number()
                ->description('Tope de gasto en dólares. Si lo estimado lo supera, no se pide nada.'),
        ];
    }
}
