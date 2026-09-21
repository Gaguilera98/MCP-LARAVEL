<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class MailingApi
{
    public static function client(): PendingRequest
    {
        return Http::timeout(30)
            ->connectTimeout(5)
            ->acceptJson()
            ->withToken((string) config('services.mailing_api.token'));
    }

    public static function url(string $path): string
    {
        return rtrim((string) config('services.mailing_api.base_url'), '/')
            .'/api/v1/'
            .ltrim($path, '/');
    }

    /**
     * Segmento de ruta para {mailbox}: id o email (URL-encoded).
     */
    public static function mailboxSegment(string $mailbox): string
    {
        return rawurlencode(trim($mailbox));
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public static function get(string $path, array $query = [], ?int $timeout = null): Response|ResponseFactory
    {
        return self::send(function () use ($path, $query, $timeout) {
            $client = self::client();

            if ($timeout !== null) {
                $client = $client->timeout($timeout);
            }

            return $client->get(self::url($path), self::filterQuery($query));
        });
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
    public static function patch(string $path, array $body = []): Response|ResponseFactory
    {
        return self::send(fn () => self::client()->patch(self::url($path), $body));
    }

    /**
     * Decode a JSON string argument; returns error Response on failure.
     *
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
     * Optional JSON arg: null/empty → null; otherwise decode.
     *
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
            $url = (string) $response->effectiveUri();

            if ($response->successful()) {
                $json = $response->json();

                return Response::structured(is_array($json) ? $json : ['data' => $json]);
            }

            $body = $response->body();
            $hint = strlen($body) > 400 ? substr($body, 0, 400).'…' : $body;

            return Response::error(
                'La API Mailing respondió con error '.$response->status().' ('.$url.'). Cuerpo: '.$hint
            );
        } catch (ConnectionException $e) {
            return Response::error('No se pudo conectar a la API Mailing: '.$e->getMessage());
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
