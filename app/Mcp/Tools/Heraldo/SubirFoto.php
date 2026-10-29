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

#[Name('subir-foto')]
#[Description(
    'Pone una foto en una casilla de la grilla de la campaña: una fila (strategy_id + image_index) y una columna (ratio), las tres de plan-produccion. '.
    'Mandá la foto con foto_url o con foto_base64, solo uno de los dos. JPG o PNG, hasta 10 MB. '.
    'Preferí foto_url: un base64 grande puede no entrar en la llamada. '.
    'Queda como foto subida a mano: no gasta. Si la casilla ya tenía foto, la reemplaza. '.
    'Si la campaña todavía no tenía producción, la crea. Si la proporción no es una columna, el error trae ratios con las válidas.'
)]
class SubirFoto extends Tool
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
            $this->campaignPath($request, 'production/photos'),
            $photo['bytes'],
            $photo['filename'],
            [
                'strategy_id' => (int) $request->get('strategy_id'),
                'image_index' => (int) $request->get('image_index'),
                'ratio' => $request->get('ratio'),
            ],
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'strategy_id' => $schema->integer()
                ->description('strategy_id de la fila, de rows en plan-produccion.')
                ->required(),
            'image_index' => $schema->integer()
                ->description('image_index de la misma fila, de rows en plan-produccion.')
                ->required(),
            'ratio' => $schema->string()
                ->description('La proporción de la columna, escrita igual que en columns de plan-produccion, por ejemplo 1:1.')
                ->required(),
            'foto_url' => $schema->string()
                ->description('Enlace público que descarga la imagen directo, sin pedir inicio de sesión. Un archivo de Drive tiene que estar compartido con cualquiera que tenga el enlace. No lo combines con foto_base64.'),
            'foto_base64' => $schema->string()
                ->description('Foto en base64, con o sin prefijo data:image. JPG o PNG, hasta 10 MB. No lo combines con foto_url.'),
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
            default => null,
        };

        if ($filename === null) {
            return Response::error('La foto tiene que ser JPG o PNG.');
        }

        return [
            'bytes' => $bytes,
            'filename' => $filename,
        ];
    }
}
