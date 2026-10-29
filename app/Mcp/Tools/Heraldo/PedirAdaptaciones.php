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

#[Name('pedir-adaptaciones')]
#[Description(
    'Completa con IA las casillas vacías de la grilla a partir de las fotos de la columna base. Antes tiene que haber columna base (usar-columna-base). '.
    'Gasta dinero. Heraldo exige max_usd: si no lo mandás o lo estimado lo supera, no hace nada y el error trae estimate_usd. '.
    'Usá ese estimado para confirmar con la persona y volvé a llamarla con su tope. '.
    'Queda en cola: responde cuántas casillas va a adaptar (adaptations) y el estimado; el avance se ve en photos de estado-produccion.'
)]
class PedirAdaptaciones extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [];
        $maxUsd = $this->optionalNumber($request, 'max_usd');

        if ($maxUsd !== null) {
            $body['max_usd'] = $maxUsd;
        }

        return HeraldoApi::post($this->campaignPath($request, 'production/adaptations'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'max_usd' => $schema->number()
                ->description('Tope de gasto en dólares que aprobó la persona. Sin este dato no se hace nada.'),
        ];
    }
}
