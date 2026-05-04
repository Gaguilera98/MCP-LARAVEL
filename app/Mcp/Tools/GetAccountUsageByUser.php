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

#[Name('get_account_usage_by_user')]
#[Description('Uso y costo por usuario (UsageRecord), desglosado por herramienta (by_tool: tool, count, subtotal_cost_usd) con total_uses y total_cost_usd. Mismos filtros organizacionales que get_account_generations; date_from/date_to filtran created_at de UsageRecord. Pagina usuarios (meta.pagination, links). Para total agregado de cuenta usar uso-cuenta-tenant.')]
class GetAccountUsageByUser extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId = $request->get('tenantId');
            $accountId = $request->get('accountId');

            $url = config('services.zalo_api.base_url')
                .'/api/v1/tenants/'.$tenantId
                .'/accounts/'.$accountId
                .'/usage/by-user';

            $query = [];
            foreach ([
                'area_id',
                'area',
                'company_id',
                'position_id',
                'user_id',
                'date_from',
                'date_to',
            ] as $key) {
                $value = $request->get($key);
                if ($value !== null && $value !== '') {
                    $query[$key] = $value;
                }
            }

            foreach (['page', 'per_page'] as $key) {
                $v = $request->get($key);
                if ($v !== null && $v !== '') {
                    $query[$key] = $key === 'per_page'
                        ? min(100, max(1, (int) $v))
                        : max(1, (int) $v);
                }
            }

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept'        => '*/*',
                    'Connection'    => 'keep-alive',
                    'Authorization' => 'Bearer '.env('ZALO_API_TOKEN'),
                ])
                ->get($url, $query);

            if ($response->successful()) {
                return Response::structured($response->json());
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
            'tenantId' => $schema->string()
                ->description('UUID del tenant.')
                ->required(),
            'accountId' => $schema->string()
                ->description('ID numérico de la cuenta.')
                ->required(),
            'area_id' => $schema->integer()
                ->description('Filtrar usuarios por ID de área (exacto).'),
            'area' => $schema->string()
                ->description('Nombre parcial de área (LIKE).'),
            'company_id' => $schema->integer()
                ->description('ID de empresa.'),
            'position_id' => $schema->integer()
                ->description('ID de cargo.'),
            'user_id' => $schema->integer()
                ->description('Limitar a un usuario concreto.'),
            'date_from' => $schema->string()
                ->description('YYYY-MM-DD. Inicio del rango sobre created_at de UsageRecord (opcional).'),
            'date_to' => $schema->string()
                ->description('YYYY-MM-DD. Fin del rango (opcional).'),
            'page' => $schema->integer()
                ->description('Página de usuarios (por defecto en API: 1).'),
            'per_page' => $schema->integer()
                ->description('Usuarios por página (por defecto en API: 25, máximo 100).'),
        ];
    }
}
