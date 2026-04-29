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

#[Name('chat-usuario')]
#[Description('Historial de chat por usuario: conversaciones con resumen en meta (totales, by_agent) y paginación. Opcionalmente incluye mensajes por conversación con include_messages=true. Filtra por last_message_at con date_from/date_to (YYYY-MM-DD).')]
class ChatUsuario extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId  = $request->get('tenant_id');
            $accountId = $request->get('account_id');
            $userId    = $request->get('user_id');

            $url = config('services.zalo_api.base_url')
                .'/api/v1/tenants/'.$tenantId
                .'/accounts/'.$accountId
                .'/users/'.$userId
                .'/chat';

            $query = [];
            foreach (['date_from', 'date_to'] as $key) {
                $v = $request->get($key);
                if ($v !== null && $v !== '') {
                    $query[$key] = $v;
                }
            }

            foreach (['page', 'per_page'] as $key) {
                $v = $request->get($key);
                if ($v !== null && $v !== '') {
                    $query[$key] = $v;
                }
            }

            $includeMessages = $request->get('include_messages');
            if ($includeMessages !== null) {
                if (is_bool($includeMessages)) {
                    $query['include_messages'] = $includeMessages ? '1' : '0';
                } else {
                    $parsed = filter_var($includeMessages, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    $query['include_messages'] = ($parsed === true) ? '1' : '0';
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
            'account_id' => $schema->string()
                ->description('Id numérico de la cuenta.')
                ->required(),
            'user_id' => $schema->string()
                ->description('Id numérico del usuario (debe pertenecer a la cuenta).')
                ->required(),
            'date_from' => $schema->string()
                ->description('Fecha inicio (YYYY-MM-DD). Filtra por last_message_at desde el inicio del día.'),
            'date_to' => $schema->string()
                ->description('Fecha fin (YYYY-MM-DD). Filtra por last_message_at hasta el fin del día.'),
            'page' => $schema->integer()
                ->description('Página de conversaciones (por defecto: 1).'),
            'per_page' => $schema->integer()
                ->description('Conversaciones por página (por defecto: 25, máximo: 100).'),
            'include_messages' => $schema->boolean()
                ->description('Si true, cada conversación incluye messages (role, content, etc.). Por defecto false en la API si no se envía.'),
        ];
    }
}
