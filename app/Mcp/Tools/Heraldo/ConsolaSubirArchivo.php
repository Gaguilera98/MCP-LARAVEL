<?php

namespace App\Mcp\Tools\Heraldo;

use App\Support\HeraldoApi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('consola-subir-archivo')]
#[Description(
    'Sube un archivo al pedido: capturas de las madres, el resumen, un Excel. kind: client (lo que mandó el cliente), summary, capture, other. Devuelve su id (para file_ids) y su enlace firmado. Hasta 50 MB. Las imágenes PNG, JPEG, GIF y WebP se ven en la consola; lo demás se baja.'
)]
class ConsolaSubirArchivo extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    private const int MAX_BYTES = 50 * 1024 * 1024;

    public function handle(Request $request): Response|ResponseFactory
    {
        $fields = [];

        foreach (['name', 'mime', 'step', 'kind'] as $key) {
            $value = $request->get($key);

            if (is_string($value) && $value !== '') {
                $fields[$key] = $value;
            }
        }

        $path = $this->consolePath('orders/{order_id}/files', $request);
        $agent = $this->agentFromRequest($request);
        $url = $request->get('archivo_url');
        $encoded = $request->get('archivo_base64');
        $hasUrl = is_string($url) && trim($url) !== '';
        $hasEncoded = is_string($encoded) && trim($encoded) !== '';

        if ($hasUrl === $hasEncoded) {
            return Response::error('Indicá archivo_url o archivo_base64, una sola.');
        }

        if ($hasUrl) {
            try {
                $download = Http::timeout(60)->get($url);
            } catch (\Throwable $e) {
                return Response::error('No se pudo descargar el archivo: '.$e->getMessage());
            }

            if (! $download->successful()) {
                return Response::error('No se pudo descargar el archivo ('.$download->status().').');
            }

            $bytes = $download->body();

            if ($bytes === '') {
                return Response::error('El archivo está vacío.');
            }

            if (strlen($bytes) > self::MAX_BYTES) {
                return Response::error('El archivo supera los 50 MB.');
            }

            $filename = is_string($request->get('name')) && trim((string) $request->get('name')) !== ''
                ? trim((string) $request->get('name'))
                : 'archivo.bin';

            return HeraldoApi::consolePostFile($path, $bytes, $filename, $fields, $agent);
        }

        $payload = trim((string) $encoded);

        if (str_contains($payload, ',')) {
            $payload = substr($payload, (int) strpos($payload, ',') + 1);
        }

        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            return Response::error('archivo_base64 no es un base64 válido.');
        }

        if ($decoded === '') {
            return Response::error('El archivo está vacío.');
        }

        if (strlen($decoded) > self::MAX_BYTES) {
            return Response::error('El archivo supera los 50 MB.');
        }

        $fields['content_base64'] = base64_encode($decoded);

        return HeraldoApi::consolePost($path, $fields, $agent);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()
                ->description('id del pedido, de consola-pendientes, consola-listar-pedidos o consola-crear-pedido.')
                ->required(),
            'archivo_url' => $schema->string()
                ->description('Enlace https del archivo. El MCP lo baja y lo manda en el campo file. No lo combines con archivo_base64.'),
            'archivo_base64' => $schema->string()
                ->description('El archivo en base64. No lo combines con archivo_url.'),
            'name' => $schema->string()
                ->description('Nombre del archivo con su extensión.'),
            'mime' => $schema->string(),
            'step' => $schema->string()
                ->enum(['order', 'campaigns', 'design', 'content', 'templates', 'plugin', 'review', 'delivery'])
                ->description('El paso: order (1 Pedido), campaigns (2 Campañas), design (3 Diseño madre), content (4 Fotos y textos), templates (5 Plantillas), plugin (6 Plugin), review (7 Revisión), delivery (8 Entrega).'),
            'kind' => $schema->string()
                ->enum(['client', 'summary', 'capture', 'other'])
                ->description('client, summary, capture u other.'),
            ...$this->agentArgument($schema),
        ];
    }
}
