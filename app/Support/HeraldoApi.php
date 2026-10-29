<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class HeraldoApi
{
    public static function client(): PendingRequest
    {
        return Http::timeout(60)
            ->connectTimeout(5)
            ->acceptJson()
            ->withToken((string) config('services.heraldo_api.token'));
    }

    public static function url(string $path): string
    {
        return rtrim((string) config('services.heraldo_api.base_url'), '/')
            .'/api/v1/'
            .ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public static function get(string $path, array $query = []): Response|ResponseFactory
    {
        return self::send(fn () => self::client()->get(self::url($path), self::filterQuery($query)));
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public static function post(string $path, array $body = []): Response|ResponseFactory
    {
        return self::send(fn () => self::client()->post(self::url($path), $body));
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public static function put(string $path, array $body = []): Response|ResponseFactory
    {
        return self::send(fn () => self::client()->put(self::url($path), $body));
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public static function postFile(string $path, string $contents, string $filename, array $fields = []): Response|ResponseFactory
    {
        return self::send(fn () => self::client()
            ->attach('photo', $contents, $filename)
            ->post(self::url($path), $fields));
    }

    /**
     * @return array<mixed>|Response|ResponseFactory
     */
    public static function decodeJsonArg(mixed $value, string $field): array|Response|ResponseFactory
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return Response::error("El campo {$field} es obligatorio (JSON).");
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return Response::error("JSON inválido en {$field}: ".$e->getMessage());
        }

        if (! is_array($decoded)) {
            return Response::error("El campo {$field} debe ser un objeto o array JSON.");
        }

        return $decoded;
    }

    /**
     * @return array<mixed>|null|Response|ResponseFactory
     */
    public static function decodeOptionalJsonArg(mixed $value, string $field): array|null|Response|ResponseFactory
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::decodeJsonArg($value, $field);
    }

    /**
     * @param  callable(): HttpResponse  $call
     */
    private static function send(callable $call): Response|ResponseFactory
    {
        try {
            $response = $call();

            if ($response->successful()) {
                $json = $response->json();

                if (! is_array($json)) {
                    return Response::error(
                        'La API Heraldo respondió '.$response->status().' sin JSON. Esa respuesta no se puede reenviar.'
                    );
                }

                return Response::structured($json);
            }

            $decoded = $response->json();
            $message = is_array($decoded) && is_string($decoded['message'] ?? null) && $decoded['message'] !== ''
                ? $decoded['message']
                : null;

            if ($message === null) {
                $body = $response->body();
                $message = $body === ''
                    ? 'sin cuerpo'
                    : (strlen($body) > 400 ? substr($body, 0, 400).'…' : $body);
            }

            $text = 'La API Heraldo respondió '.$response->status().': '.$message;
            $detail = is_array($decoded) ? array_diff_key($decoded, ['message' => true]) : [];

            if ($detail !== []) {
                $json = (string) json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $text .= ' Detalle: '.(strlen($json) > 4000 ? substr($json, 0, 4000).'…' : $json);
            }

            return Response::error($text);
        } catch (ConnectionException $e) {
            return Response::error('No se pudo conectar a la API Heraldo: '.$e->getMessage());
        } catch (\Throwable $e) {
            return Response::error('Error inesperado: '.$e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private static function filterQuery(array $query): array
    {
        return array_filter(
            $query,
            static fn (mixed $value): bool => $value !== null && $value !== ''
        );
    }
}
