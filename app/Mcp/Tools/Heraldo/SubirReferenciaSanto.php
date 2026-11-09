<?php

namespace App\Mcp\Tools\Heraldo;

use App\Support\HeraldoApi;
use finfo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('subir-referencia-santo')]
#[Description(
    'Sube la imagen de referencia que Santo usa para las fotos base, por ejemplo el producto o la pantalla del teléfono. Se sube una vez y sirve para todas las filas. '.
    'Devuelve path: pásalo como reference en configurar-santo. No gasta. JPG, PNG o WebP de hasta 10 MB. '.
    'Manda la imagen con foto_url (mejor) o con foto_base64, solo uno.'
)]
class SubirReferenciaSanto extends Tool
{
    use CallsHeraldo;

    private const int MAX_BYTES = 10 * 1024 * 1024;

    public function handle(Request $request): Response|ResponseFactory
    {
        $photo = $this->photoBytes($request);

        if ($photo instanceof Response) {
            return $photo;
        }

        return HeraldoApi::postFile(
            $this->campaignPath($request, 'production/santo/reference'),
            $photo['bytes'],
            $photo['filename'],
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'foto_url' => $schema->string()
                ->description('Enlace público que descarga la imagen directo, sin pedir inicio de sesión. No lo combines con foto_base64.'),
            'foto_base64' => $schema->string()
                ->description('La imagen en base64, con o sin prefijo data:image. JPG, PNG o WebP, hasta 10 MB. No lo combines con foto_url.'),
        ];
    }

    /**
     * @return array{bytes: string, filename: string}|Response
     */
    private function photoBytes(Request $request): array|Response
    {
        $url = $request->get('foto_url');
        $encoded = $request->get('foto_base64');
        $hasUrl = is_string($url) && trim($url) !== '';
        $hasEncoded = is_string($encoded) && trim($encoded) !== '';

        if ($hasUrl === $hasEncoded) {
            return Response::error('Indicá foto_url o foto_base64, una sola.');
        }

        if ($hasUrl) {
            try {
                $download = Http::timeout(60)->get($url);
            } catch (\Throwable $e) {
                return Response::error('No se pudo descargar la foto: '.$e->getMessage());
            }

            if (! $download->successful()) {
                return Response::error('No se pudo descargar la foto ('.$download->status().').');
            }

            $bytes = $download->body();
        } else {
            $payload = trim((string) $encoded);

            if (str_contains($payload, ',')) {
                $payload = substr($payload, (int) strpos($payload, ',') + 1);
            }

            $decoded = base64_decode($payload, true);

            if ($decoded === false) {
                return Response::error('foto_base64 no es un base64 válido.');
            }

            $bytes = $decoded;
        }

        if ($bytes === '') {
            return Response::error('La foto está vacía.');
        }

        if (strlen($bytes) > self::MAX_BYTES) {
            return Response::error('La foto supera los 10 MB.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);

        $filename = match ($mime) {
            'image/jpeg' => 'foto.jpg',
            'image/png' => 'foto.png',
            'image/webp' => 'foto.webp',
            default => null,
        };

        if ($filename === null) {
            return Response::error('La foto tiene que ser JPG, PNG o WebP.');
        }

        return [
            'bytes' => $bytes,
            'filename' => $filename,
        ];
    }
}
