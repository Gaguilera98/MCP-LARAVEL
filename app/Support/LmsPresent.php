<?php

namespace App\Support;

final class LmsPresent
{
    /**
     * @param  array<string, mixed>  $json
     * @param  callable(array<string, mixed>): array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function pagina(array $json, callable $item): array
    {
        $rows = [];

        foreach (is_array($json['data'] ?? null) ? $json['data'] : [] as $row) {
            if (is_array($row)) {
                $rows[] = $item($row);
            }
        }

        $next = $json['links']['next'] ?? null;

        return [
            'resultados' => $rows,
            'pagina' => $json['meta']['current_page'] ?? 1,
            'por_pagina' => $json['meta']['per_page'] ?? null,
            'total' => $json['meta']['total'] ?? count($rows),
            'hay_mas' => is_string($next) && $next !== '',
        ];
    }

    /**
     * @param  array<string, mixed>  $course
     * @return array<string, mixed>
     */
    public static function curso(array $course): array
    {
        return [
            'id' => $course['id'] ?? null,
            'nombre' => $course['name'] ?? null,
            'descripcion' => $course['description'] ?? null,
            'privado' => $course['is_private'] ?? null,
            'cierra_el' => $course['expires_at'] ?? null,
            'portada' => $course['cover_library_item_id'] ?? null,
            'categorias' => array_map(
                static fn (mixed $category): array => is_array($category) ? [
                    'id' => $category['id'] ?? null,
                    'nombre' => $category['name'] ?? null,
                ] : [],
                is_array($course['categories'] ?? null) ? $course['categories'] : [],
            ),
            'capitulos' => array_map(
                static fn (mixed $chapter): array => is_array($chapter) ? self::capitulo($chapter) : [],
                is_array($course['chapters'] ?? null) ? $course['chapters'] : [],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    public static function archivo(array $json): array
    {
        return [
            'id' => $json['id'] ?? null,
            'nombre' => $json['name'] ?? null,
            'uso' => match ($json['kind'] ?? '') {
                'cover' => 'portada',
                'image' => 'imagen',
                'document' => 'documento',
                default => null,
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    public static function video(array $json): array
    {
        return [
            'id' => $json['id'] ?? null,
            'nombre' => $json['name'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $student
     * @return array<string, mixed>
     */
    public static function estudiante(array $student): array
    {
        return [
            'id' => $student['id'] ?? null,
            'nombre' => $student['name'] ?? null,
            'apellidos' => $student['last_name'] ?? null,
            'correo' => $student['email'] ?? null,
            'usuario' => $student['username'] ?? null,
            'activo' => $student['is_active'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function inscripcion(array $row): array
    {
        return [
            'nombre' => $row['name'] ?? null,
            'apellidos' => $row['last_name'] ?? null,
            'correo' => $row['email'] ?? null,
            'inscrito_el' => $row['enrolled_at'] ?? null,
            'calificacion' => $row['level'] ?? null,
            'comentario' => $row['comment'] ?? null,
            'empezo' => $row['started'] ?? null,
            'avance' => $row['progress_percent'] ?? null,
            'termino' => $row['completed'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $tutor
     * @return array<string, mixed>
     */
    public static function tutor(array $tutor): array
    {
        return [
            'id' => $tutor['id'] ?? null,
            'nombre' => $tutor['name'] ?? null,
            'apellidos' => $tutor['last_name'] ?? null,
            'correo' => $tutor['email'] ?? null,
            'activo' => $tutor['is_active'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    public static function lote(array $json, string $okKey, string $okLabel): array
    {
        $done = [];

        foreach (is_array($json[$okKey] ?? null) ? $json[$okKey] : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $item = [];

            if (array_key_exists('email', $row)) {
                $item['correo'] = $row['email'];
            }

            if (array_key_exists('student_id', $row)) {
                $item['estudiante'] = $row['student_id'];
            }

            if (array_key_exists('id', $row)) {
                $item['id'] = $row['id'];
            }

            if (array_key_exists('level', $row)) {
                $item['calificacion'] = $row['level'];
            }

            if (array_key_exists('comment', $row)) {
                $item['comentario'] = $row['comment'];
            }

            $done[] = $item;
        }

        $skipped = [];

        foreach (is_array($json['skipped'] ?? null) ? $json['skipped'] : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $item = ['motivo' => $row['reason'] ?? null];

            if (array_key_exists('email', $row)) {
                $item['correo'] = $row['email'];
            }

            if (array_key_exists('id', $row)) {
                $item['id'] = $row['id'];
            }

            $skipped[] = $item;
        }

        return [
            $okLabel => $done,
            'omitidos' => $skipped,
        ];
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    public static function quitado(array $json): array
    {
        return [
            'listo' => ($json['deleted'] ?? false) === true,
        ];
    }

    /**
     * @param  array<string, mixed>  $chapter
     * @return array<string, mixed>
     */
    public static function capitulo(array $chapter): array
    {
        return [
            'id' => $chapter['id'] ?? null,
            'nombre' => $chapter['name'] ?? null,
            'visible' => $chapter['is_published'] ?? null,
            'orden' => $chapter['order'] ?? null,
            'tutores' => array_map(
                static fn (mixed $tutor): array => is_array($tutor) ? [
                    'id' => $tutor['id'] ?? null,
                    'nombre' => $tutor['name'] ?? null,
                    'apellidos' => $tutor['last_name'] ?? null,
                    'correo' => $tutor['email'] ?? null,
                ] : [],
                is_array($chapter['tutors'] ?? null) ? $chapter['tutors'] : [],
            ),
            'pasos' => array_map(
                static fn (mixed $step): array => is_array($step) ? self::paso($step) : [],
                is_array($chapter['steps'] ?? null) ? $chapter['steps'] : [],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $step
     * @return array<string, mixed>
     */
    public static function paso(array $step): array
    {
        $row = [
            'id' => $step['id'] ?? null,
            'nombre' => $step['name'] ?? null,
            'orden' => $step['order'] ?? null,
            'opcional' => $step['is_optional'] ?? null,
            'tipo' => match ($step['type'] ?? '') {
                'text' => 'texto',
                'document' => 'documento',
                'image' => 'imagen',
                'link' => 'enlace',
                'video' => 'video',
                default => $step['type'] ?? null,
            },
        ];

        if (array_key_exists('text', $step)) {
            $row['texto'] = $step['text'];
        }

        if (array_key_exists('library_item_id', $step)) {
            $row['archivo'] = $step['library_item_id'];
        }

        if (array_key_exists('url', $step)) {
            $row['enlace'] = $step['url'];
        }

        return $row;
    }
}
