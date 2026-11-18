<?php

namespace App\Mcp\Tools;

use App\Support\RemoteApi;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\ResponseFactory;

#[Name('listar-cuentas-tenants')]
#[Description('Cuentas de un tenant de Zalo, con cantidad de usuarios activos e inactivos, de a 200 por página. meta trae page, per_page, total y last_page.')]
class ListarCuentasTenants extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenant_id = $request->get('tenant_id');
            $url = RemoteApi::url('zalo').'/api/v1/tenants/'.$tenant_id.'/accounts';

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept'     => '*/*',
                    'Connection' => 'keep-alive',
                    'Authorization' => 'Bearer '.RemoteApi::token('zalo'),
                ])
                ->get($url, array_filter([
                    'page' => $request->get('page'),
                    'per_page' => $request->get('per_page') !== null ? min((int) $request->get('per_page'), 200) : null,
                ], fn ($value) => $value !== null && $value !== ''));

            if ($response->successful()) {
                $data = $response->json('data') ?? [];

                return Response::structured([
                    'cuentas' => $data,
                    'count'   => count($data),
                    'meta'    => $response->json('meta'),
                ]);
            }

            $body = $response->body();
            $hint = strlen($body) > 300 ? substr($body, 0, 300).'…' : $body;

            return Response::error(
                'La API respondió con error '.$response->status().' ('.$url.'). Cuerpo: '.$hint
            );

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return Response::error('No se pudo conectar a la API: ' . $e->getMessage());
        } catch (\Exception $e) {
            return Response::error('Error inesperado: ' . $e->getMessage());
        }
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        
            return [
                'tenant_id' => $schema->string()
                    ->description('Id del tenant que se desea listar las cuentas.')
                    ->required(),
                'page' => $schema->integer()
                    ->description('Página, desde 1. Por defecto 1.'),
                'per_page' => $schema->integer()
                    ->description('Cuántas por página, hasta 200. Por defecto 200.'),
            ];
        
    }
}
