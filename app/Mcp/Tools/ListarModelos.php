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

#[Name('listar-modelos')]
#[Description('Catálogo de modelos IA del tenant (ai_models + pricing vigente). Útil antes de filtrar generations por model/platform. Lifecycle deprecated/retired aún puede venir null (T12+).')]
class ListarModelos extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId = $request->get('tenant_id');
            $url = config('services.zalo_api.base_url').'/api/v1/tenants/'.$tenantId.'/models';

            $query = [];
            foreach (['status', 'model_type'] as $key) {
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
            'tenant_id' => $schema->string()
                ->description('UUID del tenant.')
                ->required(),
            'status' => $schema->string()
                ->description('Filtro opcional del catálogo (ej. active, inactive).'),
            'model_type' => $schema->string()
                ->description('Filtro opcional por tipo de modelo (según ai_models.model_type).'),
        ];
    }
}
