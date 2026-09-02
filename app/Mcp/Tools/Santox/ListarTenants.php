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

#[Name('listar-tenants')]
#[Description('Lista los tenants de Santox (API central /api/v1/tenants). Requiere Bearer de un user central.')]
class ListarTenants extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $url = config('services.santox_api.base_url').'/api/v1/tenants';

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Connection' => 'keep-alive',
                    'Authorization' => 'Bearer '.config('services.santox_api.token'),
                ])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json('data') ?? [];

                return Response::structured([
                    'tenants' => $data,
                    'count' => count($data),
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
        return [];
    }
}
