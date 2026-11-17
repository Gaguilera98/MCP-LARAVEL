<?php

namespace App\Support;

use App\Models\ServiceConnection;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * URL y token de salida hacia Zalo, Santox, Mailing, el aula y Heraldo.
 * Lo guardado en el panel pisa al entorno. Si el panel está vacío, queda el .env.
 */
final class RemoteApi
{
    /** @var array<string, array{label: string, config: string}> */
    public const SERVICES = [
        'zalo' => ['label' => 'Zalo', 'config' => 'zalo_api'],
        'santox' => ['label' => 'Santox', 'config' => 'santox_api'],
        'mailing' => ['label' => 'Godai Mailing', 'config' => 'mailing_api'],
        'lms' => ['label' => 'Aula', 'config' => 'lms_api'],
        'heraldo' => ['label' => 'Heraldo', 'config' => 'heraldo_api'],
    ];

    /** @var array<string, ServiceConnection|null> */
    private static array $rows = [];

    private static bool $loaded = false;

    private static ?bool $tableReady = null;

    public static function url(string $service): string
    {
        self::assertService($service);
        $saved = trim((string) (self::row($service)?->base_url ?? ''));
        $url = $saved !== '' ? $saved : (string) config('services.'.self::SERVICES[$service]['config'].'.base_url');

        return rtrim($url, '/');
    }

    public static function token(string $service): string
    {
        self::assertService($service);
        $saved = self::savedToken($service);
        if ($saved !== '') {
            return $saved;
        }

        return trim((string) config('services.'.self::SERVICES[$service]['config'].'.token'));
    }

    public static function hint(string $service): string
    {
        $token = self::token($service);
        if ($token === '') {
            return 'Sin token. Cargalo acá o en el entorno.';
        }

        $where = self::savedToken($service) !== '' ? 'panel' : 'entorno';

        return 'Token del '.$where.', termina en '.substr($token, -4).'.';
    }

    public static function envUrl(string $service): string
    {
        self::assertService($service);

        return rtrim((string) config('services.'.self::SERVICES[$service]['config'].'.base_url'), '/');
    }

    public static function hasPanelToken(string $service): bool
    {
        return self::savedToken($service) !== '';
    }

    /**
     * @return array<string, string>
     */
    public static function formState(): array
    {
        $state = [];
        foreach (self::SERVICES as $service => $meta) {
            $state[$service.'_base_url'] = trim((string) (self::row($service)?->base_url ?? ''));
            $state[$service.'_token'] = '';
            $state[$service.'_clear_token'] = false;
        }

        return $state;
    }

    public static function persist(string $service, ?string $baseUrl, ?string $token, bool $clearToken): void
    {
        self::assertService($service);
        if (! self::tableReady()) {
            return;
        }

        $row = ServiceConnection::query()->firstOrNew(['service' => $service]);
        $row->base_url = trim((string) $baseUrl) === '' ? null : rtrim(trim((string) $baseUrl), '/');

        if ($clearToken) {
            $row->token = null;
        }
        if (is_string($token) && trim($token) !== '') {
            $row->token = trim($token);
        }

        if ($row->base_url === null && self::blank($row->token)) {
            if ($row->exists) {
                $row->delete();
            }
            self::forget();

            return;
        }

        $row->save();
        self::forget();
    }

    public static function forget(): void
    {
        self::$rows = [];
        self::$loaded = false;
        self::$tableReady = null;
    }

    private static function savedToken(string $service): string
    {
        $row = self::row($service);
        if (! $row) {
            return '';
        }

        try {
            $token = $row->token;
        } catch (Throwable) {
            return '';
        }

        return is_string($token) ? trim($token) : '';
    }

    private static function row(string $service): ?ServiceConnection
    {
        self::load();

        return self::$rows[$service] ?? null;
    }

    private static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        self::$loaded = true;
        if (! self::tableReady()) {
            return;
        }

        $rows = ServiceConnection::query()
            ->whereIn('service', array_keys(self::SERVICES))
            ->get();

        foreach ($rows as $row) {
            self::$rows[$row->service] = $row;
        }
    }

    private static function tableReady(): bool
    {
        if (self::$tableReady !== null) {
            return self::$tableReady;
        }

        try {
            return self::$tableReady = Schema::hasTable('service_connections');
        } catch (Throwable) {
            return self::$tableReady = false;
        }
    }

    private static function assertService(string $service): void
    {
        if (! isset(self::SERVICES[$service])) {
            throw new \InvalidArgumentException('Servicio desconocido: '.$service);
        }
    }

    private static function blank(mixed $token): bool
    {
        try {
            return ! is_string($token) || trim($token) === '';
        } catch (Throwable) {
            return true;
        }
    }
}
