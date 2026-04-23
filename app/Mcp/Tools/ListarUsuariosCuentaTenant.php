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
#[Description('Lista los usuarios de una cuenta dentro de un tenant. Incluye meta (cuenta, tenant_id, pagination) y links si la API los envía; count es la cantidad de usuarios en la página actual.')]
class ListarUsuariosCuentaTenant extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId = $request->get('tenant_id');
            $accountId = $request->get('account_id');
            $url = config('services.zalo_api.base_url')
                .'/api/v1/tenants/'.$tenantId.'/accounts/'.$accountId.'/users';

            $query = [];
            foreach (['page', 'per_page'] as $key) {
                $v = $request->get($key);
                if ($v !== null && $v !== '') {
                    $query[$key] = $v;
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
                $usuarios = $response->json('data') ?? [];
                $payload = [
                    'usuarios' => $usuarios,
                    'count' => count($usuarios),
                ];
                $meta = $response->json('meta');
                if (is_array($meta)) {
                    $payload['meta'] = $meta;
                }
                $links = $response->json('links');
                if (is_array($links)) {
                    $payload['links'] = $links;
                }

                return Response::structured($payload);
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
            'page' => $schema->string()
                ->description('Página de resultados (opcional), p. ej. "2".'),
            'per_page' => $schema->string()
                ->description('Tamaño de página (opcional), si la API lo soporta.'),
        ];
    }
}
