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

#[Name('generar-fotos-base')]
#[Description(
    'Pide a Santo las fotos base de la columna base. Gasta: exige max_usd. Sin max_usd responde 422 con estimate_usd y santo_balance_usd sin pedir nada, y así se sabe cuánto cuesta. '.
    'Responde 409 sin pedir nada si lo estimado supera max_usd o el saldo de Santo, si falta configurar Santo (configurar-santo) o si ya hay un pedido en cola. '.
    'Con only_pending en true pide solo las casillas que no tienen foto. El avance se sigue con estado-produccion.'
)]
class GenerarFotosBase extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [];
        $maxUsd = $this->optionalNumber($request, 'max_usd');
        $onlyPending = $this->optionalBool($request, 'only_pending');

        if ($maxUsd !== null) {
            $body['max_usd'] = $maxUsd;
        }

        if ($onlyPending !== null) {
            $body['only_pending'] = $onlyPending;
        }

        return HeraldoApi::post($this->campaignPath($request, 'production/santo/generate'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'max_usd' => $schema->number()
                ->min(0)
                ->description('Tope de gasto en dólares. Si lo estimado lo supera, no se pide nada. Sin este dato no se hace nada y el error trae el estimado.'),
            'only_pending' => $schema->boolean()
                ->description('true pide solo las casillas sin foto. Por defecto, false.'),
        ];
    }
}
