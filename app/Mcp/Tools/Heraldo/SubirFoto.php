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
    'Manda la foto con foto_url, foto_base64 o console_file_id, solo uno de los tres. JPG o PNG, hasta 10 MB. '.
    'Si la foto es un archivo de un pedido de la consola, usa console_file_id: es lo más simple, porque no necesita enlace ni base64. '.
    'Si no, mejor usa foto_url: un base64 grande puede no entrar en la llamada. '.
    'Queda como foto subida a mano: no gasta. Si la casilla ya tenía foto, la reemplaza. '.
    'Si la campaña todavía no tenía producción, la crea. Si la proporción no es una columna, el error trae ratios con las válidas. '.
    'Si la respuesta trae ratio_warning, la foto se guardó, pero no tiene la proporción de su columna: díselo a la persona con ese texto o revisa que sea la foto correcta antes de seguir.'
)]
class SubirFoto extends Tool
{
    use CallsHeraldo;

    private const int MAX_BYTES = 10 * 1024 * 1024;

    public function handle(Request $request): Response|ResponseFactory
    {
        $sources = $this->photoSources($request);

        if ($sources['count'] !== 1) {
            return Response::error('Indica foto_url, foto_base64 o console_file_id, una sola.');
        }

        $cell = [
            'strategy_id' => (int) $request->get('strategy_id'),
            'image_index' => (int) $request->get('image_index'),
            'ratio' => $request->get('ratio'),
        ];

        if ($sources['console_file_id'] !== null) {
            return HeraldoApi::post($this->campaignPath($request, 'production/photos'), [
                ...$cell,
                'console_file_id' => $sources['console_file_id'],
            ]);
        }

        $photo = $this->photoBytes($request, $sources);

        if ($photo instanceof Response) {
            return $photo;
        }

        return HeraldoApi::postFile(
            $this->campaignPath($request, 'production/photos'),
            $photo['bytes'],
            $photo['filename'],
            $cell,
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
                ->min(1)
                ->description('image_index de la misma fila, de rows en plan-produccion.')
                ->required(),
            'ratio' => $schema->string()
                ->description('La proporción de la columna, escrita igual que en columns de plan-produccion, por ejemplo 1:1.')
                ->required(),
            'foto_url' => $schema->string()
                ->description('Enlace público que descarga la imagen directo, sin pedir inicio de sesión. Un archivo de Drive tiene que estar compartido con cualquiera que tenga el enlace. No lo combines con foto_base64 ni con console_file_id.'),
            'foto_base64' => $schema->string()
                ->description('Foto en base64, con o sin prefijo data:image. JPG o PNG, hasta 10 MB. No lo combines con foto_url ni con console_file_id.'),
            'console_file_id' => $schema->integer()
                ->min(1)
                ->description('id de un archivo de un pedido de la consola, de files en consola-ver-pedido. Heraldo lo lee directo: no hace falta enlace público. Solo vale un archivo de un pedido de este mismo cliente, JPG o PNG de hasta 10 MB. No lo combines con foto_url ni con foto_base64.'),
        ];
    }

    /**
     * @return array{count: int, has_url: bool, has_encoded: bool, console_file_id: ?int}
     */
    private function photoSources(Request $request): array
    {
        $url = $request->get('foto_url');
        $encoded = $request->get('foto_base64');
        $consoleFileId = $request->get('console_file_id');
        $hasUrl = is_string($url) && trim($url) !== '';
        $hasEncoded = is_string($encoded) && trim($encoded) !== '';
        $hasConsoleFile = $consoleFileId !== null && $consoleFileId !== '';

        return [
            'count' => (int) $hasUrl + (int) $hasEncoded + (int) $hasConsoleFile,
            'has_url' => $hasUrl,
            'has_encoded' => $hasEncoded,
            'console_file_id' => $hasConsoleFile ? (int) $consoleFileId : null,
        ];
    }

    /**
     * @param  array{count: int, has_url: bool, has_encoded: bool, console_file_id: ?int}  $sources
     * @return array{bytes: string, filename: string}|Response
     */
    private function photoBytes(Request $request, array $sources): array|Response
    {
        if ($sources['has_url']) {
            try {
                $download = Http::timeout(60)->get((string) $request->get('foto_url'));
            } catch (\Throwable $e) {
                return Response::error('No se pudo descargar la foto: '.$e->getMessage());
            }

            if (! $download->successful()) {
                return Response::error('No se pudo descargar la foto ('.$download->status().').');
            }

            $bytes = $download->body();
        } else {
            $payload = trim((string) $request->get('foto_base64'));

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
