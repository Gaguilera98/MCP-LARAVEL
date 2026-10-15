<?php

namespace App\Support;

use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Throwable;

final class LmsApi
{
    /**
     * @param  array<string, mixed>  $query
     * @param  callable(array<string, mixed>): array<string, mixed>|null  $present
     */
    public static function get(string $path, array $query = [], ?callable $present = null): Response|ResponseFactory
    {
        return self::send(fn () => self::client()->get(self::url($path), $query), $present);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  callable(array<string, mixed>): array<string, mixed>|null  $present
     */
    public static function post(string $path, array $data = [], ?callable $present = null): Response|ResponseFactory
    {
        return self::send(fn () => self::client()->post(self::url($path), $data), $present);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  callable(array<string, mixed>): array<string, mixed>|null  $present
     */
    public static function patch(string $path, array $data = [], ?callable $present = null): Response|ResponseFactory
    {
        return self::send(fn () => self::client()->patch(self::url($path), $data), $present);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  callable(array<string, mixed>): array<string, mixed>|null  $present
     */
    public static function delete(string $path, array $data = [], ?callable $present = null): Response|ResponseFactory
    {
        return self::send(fn () => self::client()->delete(self::url($path), $data), $present);
    }

    /**
     * @return array<string, int>
     */
    public static function pageQuery(Request $request): array
    {
        $query = [];
        $page = $request->get('pagina');
        $amount = $request->get('cantidad');

        if ($page !== null && $page !== '') {
            $query['page'] = (int) $page;
        }

        if ($amount !== null && $amount !== '') {
            $query['per_page'] = (int) $amount;
        }

        return $query;
    }

    public static function subirArchivo(string $path, string $uso): Response|ResponseFactory
    {
        if (! is_file($path) || ! is_readable($path)) {
            return Response::error('No encuentro ese archivo. Indicá la ruta completa en este equipo.');
        }

        $kind = match (mb_strtolower(trim($uso))) {
            'portada' => 'cover',
            'imagen' => 'image',
            'documento' => 'document',
            default => null,
        };

        if ($kind === null) {
            return Response::error('Indicá si el archivo es la portada del curso, una imagen de un paso o un documento.');
        }

        $max = match ($kind) {
            'cover' => 2 * 1024 * 1024,
            'image' => 10 * 1024 * 1024,
            default => 20 * 1024 * 1024,
        };
        $size = filesize($path);

        if ($size === false || $size > $max) {
            $limit = match ($kind) {
                'cover' => '2 MB',
                'image' => '10 MB',
                default => '20 MB',
            };

            return Response::error('Ese archivo pesa demasiado. El máximo para este uso es '.$limit.'.');
        }

        return self::send(function () use ($path, $kind) {
            return self::plainClient()
                ->timeout(120)
                ->attach('file', Utils::tryFopen($path, 'r'), basename($path))
                ->post(self::url('library/files'), ['kind' => $kind]);
        }, [LmsPresent::class, 'archivo']);
    }

    public static function subirVideo(string $path): Response|ResponseFactory
    {
        if (! is_file($path) || ! is_readable($path)) {
            return Response::error('No encuentro ese video. Indicá la ruta completa en este equipo.');
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            default => null,
        };

        if ($mime === null) {
            return Response::error('El video tiene que ser mp4, webm o mov.');
        }

        $size = filesize($path);

        if ($size === false || $size < 1 || $size > 2147483648) {
            return Response::error('Ese video pesa demasiado. El máximo es 2 GB.');
        }

        if (self::missingKey()) {
            return Response::error('No puedo entrar al aula. Falta la clave de acceso en la configuración.');
        }

        try {
            $started = self::client()->post(self::url('library/uploads'), [
                'name' => basename($path),
                'mime' => $mime,
                'size' => $size,
            ]);
        } catch (ConnectionException) {
            return Response::error('No pude conectar con el aula.');
        }

        if (! $started->successful()) {
            return Response::error(self::failureMessage($started));
        }

        $ticket = $started->json('uuid');
        $target = $started->json('url');
        $headers = $started->json('headers');

        if (! is_string($ticket) || $ticket === '' || ! is_string($target) || $target === '' || ! is_array($headers)) {
            return Response::error('No pude preparar la subida del video.');
        }

        $flatHeaders = [];

        foreach ($headers as $name => $value) {
            $flatHeaders[(string) $name] = is_array($value) ? implode(', ', $value) : (string) $value;
        }

        try {
            $put = Http::timeout(600)
                ->connectTimeout(15)
                ->withHeaders($flatHeaders)
                ->send('PUT', $target, [
                    'body' => Utils::streamFor(Utils::tryFopen($path, 'r')),
                ]);
        } catch (ConnectionException) {
            return Response::error('No pude subir el video. Intentá de nuevo.');
        }

        if (! $put->successful()) {
            return Response::error('No pude subir el video. Intentá de nuevo.');
        }

        return self::send(
            fn () => self::client()->timeout(120)->post(self::url('library/uploads/'.$ticket)),
            [LmsPresent::class, 'video'],
        );
    }

    /**
     * @param  callable(): HttpResponse  $call
     * @param  callable(array<string, mixed>): array<string, mixed>|null  $present
     */
    private static function send(callable $call, ?callable $present = null): Response|ResponseFactory
    {
        if (self::missingKey()) {
            return Response::error('No puedo entrar al aula. Falta la clave de acceso en la configuración.');
        }

        try {
            $response = $call();

            if ($response->successful()) {
                $json = $response->json();
                $data = is_array($json) ? $json : ['dato' => $json];

                if ($present !== null) {
                    $data = $present($data);
                }

                if ($data === []) {
                    $data = ['listo' => true];
                }

                return Response::structured($data);
            }

            return Response::error(self::failureMessage($response));
        } catch (ConnectionException) {
            return Response::error('No pude conectar con el aula.');
        } catch (Throwable) {
            return Response::error('No se pudo completar la acción.');
        }
    }

    private static function client(): PendingRequest
    {
        return self::plainClient()->asJson();
    }

    private static function plainClient(): PendingRequest
    {
        return Http::timeout(60)
            ->connectTimeout(10)
            ->acceptJson()
            ->withToken((string) config('services.lms_api.token'));
    }

    private static function url(string $path): string
    {
        return rtrim((string) config('services.lms_api.base_url'), '/').'/api/v1/'.ltrim($path, '/');
    }

    private static function missingKey(): bool
    {
        return trim((string) config('services.lms_api.token')) === '';
    }

    private static function failureMessage(HttpResponse $response): string
    {
        if ($response->status() === 404) {
            return 'No encuentro eso en el aula. Revisá el número del curso, del capítulo, del paso o del estudiante.';
        }

        if (in_array($response->status(), [401, 403], true)) {
            return 'No tengo permiso para hacer eso en el aula.';
        }

        $json = $response->json();

        if (! is_array($json)) {
            return 'No se pudo completar la acción.';
        }

        $bits = [];

        if (isset($json['errors']) && is_array($json['errors'])) {
            foreach ($json['errors'] as $messages) {
                foreach ((array) $messages as $text) {
                    if (is_string($text) && trim($text) !== '') {
                        $bits[] = trim($text);
                    }
                }
            }
        }

        if ($bits !== []) {
            return implode(' ', array_unique($bits));
        }

        $message = $json['message'] ?? null;

        if (is_string($message) && trim($message) !== '' && ! str_contains($message, 'The given data was invalid')) {
            return trim($message);
        }

        return 'No se pudo completar la acción.';
    }
}
