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

#[Name('get_account_chat')]
#[Description('Bulk chat por cuenta (laragent_messages): model_name, tokens, costo, conversation_id, status, usage_conversation_key, started_at, duration_seconds; attachments_summary por conversación y en meta.summary (image/document, total_bytes null si no hay bytes). has_attachments filtra sin include_messages. date_from/date_to OK (CAST DATETIME). include_messages=true solo para texto/adjuntos detallados.')]
class GetAccountChat extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $tenantId = $request->get('tenantId');
            $accountId = $request->get('accountId');

            $url = RemoteApi::url('zalo')
                .'/api/v1/tenants/'.$tenantId
                .'/accounts/'.$accountId
                .'/chat';

            $query = [];
            foreach ([
                'area_id',
                'area',
                'company_id',
                'position_id',
                'user_id',
                'date_from',
                'date_to',
            ] as $key) {
                $value = $request->get($key);
                if ($value !== null && $value !== '') {
                    $query[$key] = $value;
                }
            }

            foreach (['page', 'per_page'] as $key) {
                $v = $request->get($key);
                if ($v !== null && $v !== '') {
                    $query[$key] = $key === 'per_page'
                        ? min(100, max(1, (int) $v))
                        : max(1, (int) $v);
                }
            }

            $includeMessages = $request->get('include_messages');
            $wantsMessages = $includeMessages === true || filter_var($includeMessages, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true;
            // Con mensajes, la API de Zalo trae como mucho 10 conversaciones por página (fase 8a).
            if ($wantsMessages && isset($query['per_page'])) {
                $query['per_page'] = min(10, (int) $query['per_page']);
            }
            if ($includeMessages !== null) {
                if (is_bool($includeMessages)) {
                    $query['include_messages'] = $includeMessages ? '1' : '0';
                } else {
                    $parsed = filter_var($includeMessages, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    $query['include_messages'] = ($parsed === true) ? '1' : '0';
                }
            }

            $hasAttachments = $request->get('has_attachments');
            if ($hasAttachments !== null && $hasAttachments !== '') {
                if (is_bool($hasAttachments)) {
                    $query['has_attachments'] = $hasAttachments ? '1' : '0';
                } else {
                    $parsed = filter_var($hasAttachments, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if ($parsed !== null) {
                        $query['has_attachments'] = $parsed ? '1' : '0';
                    }
                }
            }

            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders([
                    'Accept'        => '*/*',
                    'Connection'    => 'keep-alive',
                    'Authorization' => 'Bearer '.RemoteApi::token('zalo'),
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
            'tenantId' => $schema->string()
                ->description('UUID del tenant.')
                ->required(),
            'accountId' => $schema->string()
                ->description('ID numérico de la cuenta.')
                ->required(),
            'area_id' => $schema->integer()
                ->description('Filtrar por ID de área.'),
            'area' => $schema->string()
                ->description('Nombre parcial de área (LIKE).'),
            'company_id' => $schema->integer()
                ->description('ID de empresa.'),
            'position_id' => $schema->integer()
                ->description('ID de cargo.'),
            'user_id' => $schema->integer()
                ->description('Un solo usuario dentro de la cuenta.'),
            'date_from' => $schema->string()
                ->description('YYYY-MM-DD. Filtro sobre last_message_at de la conversación (inicio del día).'),
            'date_to' => $schema->string()
                ->description('YYYY-MM-DD. Hasta fin del día.'),
            'page' => $schema->integer()
                ->description('Página de conversaciones (por defecto en API: 1).'),
            'per_page' => $schema->integer()
                ->description('Conversaciones por página (por defecto en API: 25, máximo 100; con include_messages=true, máximo 10).'),
            'include_messages' => $schema->boolean()
                ->description('Si true, cada conversación incluye messages (hasta ~100 más recientes; ver messages_truncated). Omitir o false para respuestas más livianas.'),
            'has_attachments' => $schema->boolean()
                ->description('true = solo conversaciones con adjuntos (imagen/PDF); false = sin adjuntos. No requiere include_messages.'),
        ];
    }
}
