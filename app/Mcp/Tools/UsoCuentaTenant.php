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
#[Description('Obtiene el uso de una cuenta de un tenant en un rango de fechas (date_from / date_to, formato YYYY-MM-DD).')]
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

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept' => '*/*',
                    'Connection' => 'keep-alive',
                    'Authorization' => 'Bearer '.env('ZALO_API_TOKEN'),
                ])
                ->get($url, [
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ]);

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
                ->description('Fecha inicio (YYYY-MM-DD).')
                ->required(),
            'date_to' => $schema->string()
                ->description('Fecha fin (YYYY-MM-DD).')
                ->required(),
        ];
    }
}
