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

#[Name('estado-produccion')]
#[Description(
    'Dónde está la campaña en cada paso, para saber qué sigue. Trae photos (casillas con foto, en proceso, fallidas y la columna base), '.
    'copies (si los textos están completos), pieces (artes por revisión), headlines (los textos de publicación), animations (capas y videos), '.
    'in_progress (lo que sigue en cola) y cost (lo gastado y lo estimado). '.
    'Si production viene vacío, la campaña todavía no empezó: el primer paso es subir una foto. '.
    'Cuando otra herramienta deja algo en cola, vuelve a consultar aquí hasta que salga de in_progress. '.
    'cost.santo_balance_usd es el saldo de la cuenta de Santo del cliente, en dólares; es null si Santo no lo informa.'
)]
class EstadoProduccion extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->campaignPath($request, 'production'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->campaignArguments($schema);
    }
}
