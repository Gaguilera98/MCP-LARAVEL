<?php

namespace App\Mcp\Tools;

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
    'Resumen de uso/costos por cuenta (UsageRecord) más distribución estadística de usuarios registrados. '.
    'Devuelve entre otros: total_records, total_cost_real_usd, total_cost_final_usd, by_tool[] (tool, count, total_cost_usd) y user_distribution con total_users, by_area[], by_position[] y by_company[] — cada uno con user_count y percentage del total de usuarios registrados en la cuenta. '.
    'Útil para ver composición del equipo (qué porcentaje pertenece a cada área/cargo/empresa) junto con el consumo. '.
    'NOTA: user_distribution refleja todos los usuarios registrados, independiente del período de fechas; by_tool y costos sí respetan el rango. '.
    'date_from y date_to son opcionales: sin ambos devuelve histórico completo de UsageRecord; con ambos aplica rango por created_at.'
)]
class UsoCuentaTenant extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId = $request->get('tenant_id');
            $accountId = $request->get('account_id');
            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');

            $url = config('services.zalo_api.base_url')
                .'/api/v1/tenants/'.$tenantId.'/accounts/'.$accountId.'/usage';

            $query = [];
            foreach (['date_from' => $dateFrom, 'date_to' => $dateTo] as $key => $value) {
                if ($value !== null && $value !== '') {
                    $query[$key] = $value;
                }
            }

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept' => '*/*',
                    'Connection' => 'keep-alive',
                    'Authorization' => 'Bearer '.env('ZALO_API_TOKEN'),
                ])
                ->get($url, $query);

            if ($response->successful()) {
                return Response::structured([
                    'uso' => $response->json(),
                ]);
            }

            $body = $response->body();
            $hint = strlen($body) > 300 ? substr($body, 0, 300).'…' : $body;

            return Response::error(
                'La API respondió con error '.$response->status().' ('.$url.'). Cuerpo: '.$hint
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return Response::error('No se pudo conectar a la API: '.$e->getMessage());
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
                ->description('Fecha inicio (YYYY-MM-DD). Filtra UsageRecord por created_at. No afecta user_distribution.'),
            'date_to' => $schema->string()
                ->description('Fecha fin (YYYY-MM-DD). Filtra UsageRecord por created_at. No afecta user_distribution.'),
        ];
    }
}
