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

#[Name('listar-usuarios-cuenta-tenant')]
#[Description('Lista los usuarios de una cuenta dentro de un tenant con paginación y filtros opcionales (area_id, area). Devuelve meta (cuenta, tenant_id, filters, pagination), data (array de usuarios) y links de paginación.')]
class ListarUsuariosCuentaTenant extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId  = $request->get('tenant_id');
            $accountId = $request->get('account_id');
            $url = config('services.zalo_api.base_url')
                .'/api/v1/tenants/'.$tenantId.'/accounts/'.$accountId.'/users';

            $query = [];
            foreach (['page', 'per_page', 'area_id', 'area'] as $key) {
                $v = $request->get($key);
                if ($v !== null && $v !== '') {
                    $query[$key] = $v;
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
                $json = $response->json();

                return Response::structured([
                    'meta'  => $json['meta']  ?? [],
                    'data'  => $json['data']  ?? [],
                    'links' => $json['links'] ?? [],
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
            'page' => $schema->integer()
                ->description('Número de página (mínimo 1, por defecto: 1).'),
            'per_page' => $schema->integer()
                ->description('Resultados por página, entre 1 y 100 (por defecto: 25).'),
            'area_id' => $schema->string()
                ->description('Filtra usuarios por el ID exacto de su área.'),
            'area' => $schema->string()
                ->description('Búsqueda parcial por nombre de área (LIKE %valor%).'),
        ];
    }
}
