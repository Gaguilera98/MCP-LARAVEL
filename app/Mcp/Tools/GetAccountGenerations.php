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

#[Name('get_account_generations')]
#[Description('Consulta generaciones por cuenta filtradas por organizacion y otros criterios. Soporta paginacion con page/per_page y retorna el payload completo (meta, data, links).')]
class GetAccountGenerations extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId = $request->get('tenantId');
            $accountId = $request->get('accountId');

            $url = config('services.zalo_api.base_url')
                .'/api/v1/tenants/'.$tenantId
                .'/accounts/'.$accountId
                .'/generations';

            $query = [];
            foreach ([
                'area_id',
                'area',
                'company_id',
                'position_id',
                'user_id',
                'date_from',
                'date_to',
                'tools',
            ] as $key) {
                $value = $request->get($key);
                if ($value !== null && $value !== '') {
                    $query[$key] = $value;
                }
            }

            $page = (int) ($request->get('page') ?? 1);
            $perPage = (int) ($request->get('per_page') ?? 25);

            $query['page'] = max(1, $page);
            $query['per_page'] = min(100, max(1, $perPage));

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
            $hint = strlen($body) > 300 ? substr($body, 0, 300).'...' : $body;

            return Response::error(
                'La API respondio con error '.$response->status().' ('.$url.'). Cuerpo: '.$hint
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
                ->description('ID del tenant.')
                ->required(),
            'accountId' => $schema->string()
                ->description('ID de la cuenta.')
                ->required(),
            'area_id' => $schema->integer()
                ->description('ID del area.'),
            'area' => $schema->string()
                ->description('Nombre del area.'),
            'company_id' => $schema->integer()
                ->description('ID de la organizacion/empresa.'),
            'position_id' => $schema->integer()
                ->description('ID de posicion/cargo.'),
            'user_id' => $schema->integer()
                ->description('ID de usuario.'),
            'date_from' => $schema->string()
                ->description('Fecha inicio del rango (YYYY-MM-DD).'),
            'date_to' => $schema->string()
                ->description('Fecha fin del rango (YYYY-MM-DD).'),
            'tools' => $schema->string()
                ->description('Herramientas en CSV. Ej: image_generator,image_editor,video_generator,video_editor,prompt_generator,presentation_generator'),
            'page' => $schema->integer()
                ->description('Pagina a consultar (default: 1).'),
            'per_page' => $schema->integer()
                ->description('Resultados por pagina (default: 25, maximo: 100).'),
        ];
    }
}
