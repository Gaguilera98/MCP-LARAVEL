<?php

namespace App\Mcp\Tools;

use App\Support\RemoteApi;

use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

/**
 * Lo común de las herramientas de registros de Zalo (consultas a la API y cambios de un tenant).
 */
final class ZaloAuditQuery
{
    /**
     * date_from/date_to → from/to de la API; page y per_page; y los filtros extra que se pidan.
     *
     * @param  list<string>  $filters
     * @return array<string, string|int>
     */
    public static function build(Request $request, array $filters): array
    {
        $query = [];
        foreach (['date_from' => 'from', 'date_to' => 'to'] as $argument => $param) {
            $value = $request->get($argument);
            if (is_string($value) && $value !== '') {
                $query[$param] = $value;
            }
        }
        foreach ($filters as $filter) {
            $value = $request->get($filter);
            if ($value !== null && $value !== '' && is_scalar($value)) {
                $query[$filter] = (string) $value;
            }
        }
        $page = $request->get('page');
        if ($page !== null && $page !== '') {
            $query['page'] = max(1, (int) $page);
        }
        $perPage = $request->get('per_page');
        if ($perPage !== null && $perPage !== '') {
            $query['per_page'] = min(200, max(1, (int) $perPage));
        }

        return $query;
    }

    /**
     * @param  array<string, string|int>  $query
     */
    public static function get(string $path, array $query, string $key): Response|ResponseFactory
    {
        $url = RemoteApi::url('zalo').$path;

        try {
            $response = Http::timeout(15)
                ->connectTimeout(5)
                ->acceptJson()
                ->withToken(RemoteApi::token('zalo'))
                ->get($url, $query);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return Response::error('No se pudo conectar a la API: '.$e->getMessage());
        }

        if ($response->successful()) {
            return Response::structured([$key => $response->json()]);
        }

        if ($response->status() === 403) {
            return Response::error('La API rechazó la consulta (403): los registros solo se leen con el token del equipo (*). Revisá ZALO_API_TOKEN.');
        }
        if ($response->status() === 422) {
            return Response::error('La API rechazó los filtros (422): las fechas van en formato YYYY-MM-DD.');
        }

        $body = $response->body();
        $hint = strlen($body) > 300 ? substr($body, 0, 300).'…' : $body;

        return Response::error('La API respondió con error '.$response->status().' ('.$url.'). Cuerpo: '.$hint);
    }
}
