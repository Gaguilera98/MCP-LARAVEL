<?php

namespace App\Mcp\Tools;

use App\Support\RemoteApi;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('listar-envios-formulario-tenant')]
#[Description('Envíos (submissions) de un formulario, de a 200 por página. meta trae page, per_page, total y last_page: si last_page es mayor que 1, pedí las siguientes con page.')]
class ListarEnviosFormularioTenant extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId = $request->get('tenant_id');
            $formId = $request->get('form_id');
            $url = RemoteApi::url('zalo')
                .'/api/v1/tenants/'.$tenantId.'/forms/'.$formId.'/submissions';

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept' => '*/*',
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
                    'envios' => $data,
                    'count' => count($data['submissions'] ?? []),
                    'meta' => $response->json('meta'),
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
            'form_id' => $schema->string()
                ->description('Id del formulario.')
                ->required(),
            'page' => $schema->integer()
                ->description('Página, desde 1. Por defecto 1.'),
            'per_page' => $schema->integer()
                ->description('Cuántos por página, hasta 200. Por defecto 200.'),
        ];
    }
}
