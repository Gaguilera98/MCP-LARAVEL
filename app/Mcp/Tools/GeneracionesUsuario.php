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

#[Name('generaciones-usuario')]
#[Description('Historial creativo por usuario (imágenes, videos, prompts, presentaciones). Paginación y tools en CSV. Con tools=chat, data.chat es solo resumen de facturación (UsageRecord), no conversaciones; para mensajes usar chat-usuario. Sin tools: creativo + presentaciones, sin bloque chat.')]
class GeneracionesUsuario extends Tool
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
                .'/generations';

            // Construir query solo con params no vacíos
            $query = [];
            foreach (['date_from', 'date_to', 'page', 'per_page', 'tools'] as $key) {
                $v = $request->get($key);
                if ($v !== null && $v !== '') {
                    $query[$key] = $v;
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
                $json = $response->json();

                $data = $json['data'] ?? [];

                $payload = [
                    'meta'  => $json['meta']  ?? [],
                    'data'  => [
                        // by_tool solo si la API lo devolvió
                        ...( isset($data['by_tool']) ? ['by_tool' => $data['by_tool']] : [] ),
                        // chat solo si la API lo devolvió (requiere tools=chat explícito)
                        ...( isset($data['chat'])    ? ['chat'    => $data['chat']]    : [] ),
                    ],
                    'links' => $json['links'] ?? [],
                ];

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
            'user_id' => $schema->string()
                ->description('Id numérico del usuario (debe pertenecer a la cuenta).')
                ->required(),
            'date_from' => $schema->string()
                ->description('Fecha inicio del período (YYYY-MM-DD). Filtra por created_at desde el inicio del día.'),
            'date_to' => $schema->string()
                ->description('Fecha fin del período (YYYY-MM-DD). Filtra por created_at hasta el fin del día.'),
            'page' => $schema->integer()
                ->description('Página del historial creativo (mínimo 1, por defecto: 1). No afecta a presentation_generator ni a chat.'),
            'per_page' => $schema->integer()
                ->description('Resultados por página del historial creativo, entre 1 y 100 (por defecto: 25).'),
            'tools' => $schema->string()
                ->description(
                    'CSV estricto. chat aquí solo añade resumen de uso para facturación, no texto de conversaciones (eso es chat-usuario). '.
                    'Valores: image_generator, image_editor, video_generator, video_editor, prompt_generator, presentation_generator, chat. '.
                    'Sin tools: seis herramientas en by_tool sin chat. Ejemplo: "image_generator,chat"'
                ),
        ];
    }
}
