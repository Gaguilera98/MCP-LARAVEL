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

#[Name('uso-tenant')]
#[Description(
    'Lo que gastó un cliente, en dólares: total_usd, images_usd y animations_usd. Trae el total (kpis), por mes (by_month) y por campaña (by_campaign). '.
    'Sin fechas trae todo el historial.'
)]
class UsoTenant extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->tenantPath($request, 'usage'), [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->tenantArguments($schema),
            'date_from' => $schema->string()
                ->format('date')
                ->description('Desde ese día, en YYYY-MM-DD. No puede ser posterior a date_to.'),
            'date_to' => $schema->string()
                ->format('date')
                ->description('Hasta ese día inclusive, en YYYY-MM-DD.'),
        ];
    }
}
