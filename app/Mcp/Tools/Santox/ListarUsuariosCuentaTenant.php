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

#[Name('listar-usuarios-cuenta-tenant')]
#[Description('Lista usuarios de una cuenta dentro de un tenant Santox (paginado).')]
class ListarUsuariosCuentaTenant extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId = $request->get('tenant_id');
            $accountId = $request->get('account_id');
            $url = config('services.santox_api.base_url')
                .'/api/v1/tenants/'.$tenantId.'/accounts/'.$accountId.'/users';

            $query = array_filter([
                'page' => $request->get('page'),
                'per_page' => $request->get('per_page'),
            ], fn ($v) => $v !== null && $v !== '');

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.config('services.santox_api.token'),
                ])
                ->get($url, $query);

            if ($response->successful()) {
                return Response::structured($response->json() ?? []);
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
            'page' => $schema->integer()
                ->description('Página (opcional).'),
            'per_page' => $schema->integer()
                ->description('Tamaño de página, máx 100 (opcional).'),
        ];
    }
}
