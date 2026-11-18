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
        $url = self::url($service);
        $urlLine = $url === ''
            ? 'Sin URL.'
            : 'URL del '.(self::savedUrl($service) !== '' ? 'panel' : 'entorno').'.';

        $token = self::token($service);
        if ($token === '') {
            return $urlLine.' Sin token. Cargalo acá o en el entorno.';
        }

        $where = self::savedToken($service) !== '' ? 'panel' : 'entorno';

        return $urlLine.' Token del '.$where.', termina en '.substr($token, -4).'.';
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
     * Estado inicial del formulario de Conexiones.
     *
     * El token nunca va al navegador: el campo arranca vacío y la sección muestra solo los últimos
     * cuatro caracteres (hint). Antes iba el token completo, también el del entorno, con el ojo y el
     * botón de copiar: cualquiera con sesión en el panel se llevaba los tokens de todos los servicios.
     *
     * @return array<string, string|bool>
     */
    public static function formState(): array
    {
        $state = [];
        foreach (self::SERVICES as $service => $meta) {
            $state[$service.'_base_url'] = self::url($service);
            $state[$service.'_token'] = '';
            $state[$service.'_clear_token'] = false;
        }

        return $state;
    }

    /**
     * Qué token guardar a partir de lo que llegó del formulario.
     * - Escrito: ese.
     * - Vacío: el que ya estaba guardado en el panel (vacío ya no borra, porque el campo arranca vacío).
     * - Con "borrar": ninguno, y vuelve a mandar el del entorno.
     */
    public static function tokenToSave(string $service, ?string $typed, bool $clear): ?string
    {
        self::assertService($service);
        if ($clear) {
            return null;
        }

        $typed = trim((string) $typed);
        if ($typed !== '') {
            return $typed;
        }

        $saved = self::savedToken($service);

        return $saved !== '' ? $saved : null;
    }

    public static function persist(string $service, ?string $baseUrl, ?string $token, bool $clearToken): bool
    {
        self::assertService($service);
        if (! self::tableReady()) {
            return false;
        }

        $baseUrl = self::overrideUrl($service, $baseUrl);
        if ($baseUrl !== null && ! self::validUrl($baseUrl)) {
            throw new \InvalidArgumentException('La URL tiene que empezar con http:// o https://.');
        }

        $row = ServiceConnection::query()->firstOrNew(['service' => $service]);
        $row->base_url = $baseUrl;
        $row->token = self::overrideToken($service, $token, $clearToken);

        if ($row->base_url === null && self::blank($row->token)) {
            if ($row->exists) {
                $row->delete();
            }
            self::forget();

            return true;
        }

        $row->save();
        self::forget();

        return true;
    }

    public static function forget(): void
    {
        self::$rows = [];
        self::$loaded = false;
        self::$tableReady = null;
    }

    private static function savedUrl(string $service): string
    {
        return trim((string) (self::row($service)?->base_url ?? ''));
    }

    /**
     * Igual al entorno, o vacío, no es un reemplazo: se guarda vacío y sigue mandando el .env.
     */
    private static function overrideUrl(string $service, ?string $baseUrl): ?string
    {
        $baseUrl = trim((string) $baseUrl);
        if ($baseUrl === '') {
            return null;
        }

        $baseUrl = rtrim($baseUrl, '/');

        return $baseUrl === self::envUrl($service) ? null : $baseUrl;
    }

    private static function overrideToken(string $service, ?string $token, bool $clearToken): ?string
    {
        if ($clearToken || ! is_string($token)) {
            return null;
        }

        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $env = trim((string) config('services.'.self::SERVICES[$service]['config'].'.token'));

        return $token === $env ? null : $token;
    }

    private static function validUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && preg_match('#^https?://#i', $url) === 1;
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
