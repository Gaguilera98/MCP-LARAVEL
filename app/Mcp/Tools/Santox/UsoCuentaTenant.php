<?php

namespace App\Mcp\Tools\Santox;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('uso-cuenta-tenant')]
#[Description(
    'Analítica de uso/costos de una cuenta Santox (BillingAnalyticsService). '.
    'Incluye kpis (revenue, provider_cost, profit, wallets), by_application, by_provider, by_model y by_day. '.
    'date_from/date_to opcionales (YYYY-MM-DD).'
)]
class UsoCuentaTenant extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId = $request->get('tenant_id');
            $accountId = $request->get('account_id');
            $url = config('services.santox_api.base_url')
                .'/api/v1/tenants/'.$tenantId.'/accounts/'.$accountId.'/usage';

            $query = [];
            foreach (['date_from', 'date_to'] as $key) {
                $value = $request->get($key);
                if ($value !== null && $value !== '') {
                    $query[$key] = $value;
                }
            }

            $response = Http::timeout(30)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.config('services.santox_api.token'),
                ])
                ->get($url, $query);

            if ($response->successful()) {
                return Response::structured([
                    'uso' => $response->json('data') ?? $response->json(),
                ]);
            }

            $body = $response->body();
            $hint = strlen($body) > 300 ? substr($body, 0, 300).'…' : $body;

            return Response::error(
                'La API Santox respondió con error '.$response->status().' ('.$url.'). Cuerpo: '.$hint
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return Response::error('No se pudo conectar a la API Santox: '.$e->getMessage());
        } catch (\Exception $e) {
            return Response::error('Error inesperado: '.$e->getMessage());
        }
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'tenant_id' => $schema->string()
                ->description('UUID del tenant.')
                ->required(),
            'account_id' => $schema->string()
                ->description('Id numérico de la cuenta.')
                ->required(),
            'date_from' => $schema->string()
                ->description('Inicio del periodo YYYY-MM-DD (opcional).'),
            'date_to' => $schema->string()
                ->description('Fin del periodo YYYY-MM-DD (opcional).'),
        ];
    }
}
