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

#[Name('listar-usuarios-formulario-tenant')]
#[Description(
    'Usuarios elegibles de un formulario publicado según asignaciones del tenant (cuenta + reglas empresa/área/cargo; misma lógica que el portal). '.
    'Devuelve account_id, account_name, assigned_users[] (id, name, email) y total. Sin paginación. '.
    'No equivale a listar-usuarios-cuenta-tenant: aquí aplica el alcance del formulario. '.
    '422 si el formulario no tiene cuenta asignada. Conviene llamarla tras listar-formularios-tenant y antes de listar-envios-formulario-tenant o evaluar envíos.'
)]
class ListarUsuariosFormularioTenant extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId = $request->get('tenant_id');
            $formId = $request->get('form_id');

            $url = config('services.zalo_api.base_url')
                .'/api/v1/tenants/'.$tenantId
                .'/forms/'.$formId
                .'/assigned-users';

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept'        => '*/*',
                    'Connection'    => 'keep-alive',
                    'Authorization' => 'Bearer '.env('ZALO_API_TOKEN'),
                ])
                ->get($url);

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
            'form_id' => $schema->string()
                ->description('Id numérico del formulario publicado.')
                ->required(),
        ];
    }
}
