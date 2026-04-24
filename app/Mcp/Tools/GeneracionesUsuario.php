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

#[Name('generaciones-usuario')]
#[Description('Consulta las generaciones del asistente creativo de un usuario en un rango de fechas, con paginación opcional.')]
class GeneracionesUsuario extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId  = $request->get('tenant_id');
            $accountId = $request->get('account_id');
            $userId    = $request->get('user_id');
            $dateFrom  = $request->get('date_from');
            $dateTo    = $request->get('date_to');
            $page      = $request->get('page', 1);
            $perPage   = $request->get('per_page', 25);

            $url = config('services.zalo_api.base_url')
                .'/api/v1/tenants/'.$tenantId
                .'/accounts/'.$accountId
                .'/users/'.$userId
                .'/generations';

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept'        => '*/*',
                    'Connection'    => 'keep-alive',
                    'Authorization' => 'Bearer '.env('ZALO_API_TOKEN'),
                ])
                ->get($url, [
                    'date_from' => $dateFrom,
                    'date_to'   => $dateTo,
                    'page'      => $page,
                    'per_page'  => $perPage,
                ]);

            if ($response->successful()) {
                $json = $response->json();

                return Response::structured([
                    'meta'       => $json['meta'] ?? [],
                    'by_tool'    => $json['data']['by_tool'] ?? [],
                    'links'      => $json['links'] ?? [],
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
            'user_id' => $schema->string()
                ->description('Id numérico del usuario.')
                ->required(),
            'date_from' => $schema->string()
                ->description('Fecha inicio del período (YYYY-MM-DD).')
                ->required(),
            'date_to' => $schema->string()
                ->description('Fecha fin del período (YYYY-MM-DD).')
                ->required(),
            'page' => $schema->integer()
                ->description('Número de página (por defecto: 1).'),
            'per_page' => $schema->integer()
                ->description('Resultados por página (por defecto: 25).'),
        ];
    }
}
