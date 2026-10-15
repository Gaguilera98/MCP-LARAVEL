<?php

namespace App\Support;

use Laravel\Mcp\Response;

final class LmsPayload
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>|Response
     */
    public static function course(array $input, bool $withChapters): array|Response
    {
        $body = [];

        if (array_key_exists('nombre', $input)) {
            $body['name'] = trim((string) $input['nombre']);
        }

        if (array_key_exists('descripcion', $input)) {
            $body['description'] = $input['descripcion'];
        }

        if (array_key_exists('privado', $input)) {
            $body['is_private'] = self::flag($input['privado']);
        }

        if (array_key_exists('cierra_el', $input)) {
            $body['expires_at'] = $input['cierra_el'];
        }

        if (array_key_exists('categorias', $input)) {
            $body['category_ids'] = self::ids($input['categorias']);
        }

        if (array_key_exists('portada', $input)) {
            $cover = $input['portada'];
            $body['cover_library_item_id'] = $cover === null || $cover === '' ? null : (int) $cover;
        }

        if ($withChapters && array_key_exists('capitulos', $input) && $input['capitulos'] !== null && $input['capitulos'] !== '') {
            $chapters = self::chapters($input['capitulos']);

            if ($chapters instanceof Response) {
                return $chapters;
            }

            $body['chapters'] = $chapters;
        }

        return $body;
    }

    /**
     * @return list<array<string, mixed>>|Response
     */
    public static function chapters(mixed $value): array|Response
    {
        $decoded = self::decode($value, 'La lista de capítulos');

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $rows = self::asList($decoded);
        $chapters = [];

        foreach ($rows as $chapter) {
            if (! is_array($chapter)) {
                return Response::error('Cada capítulo tiene que traer nombre y, si querés, sus pasos.');
            }

            $chapters[] = self::chapter($chapter);
        }

        return $chapters;
    }

    /**
     * @return array<string, mixed>|Response
     */
    public static function step(mixed $value): array|Response
    {
        $decoded = is_array($value) ? $value : self::decode($value, 'El paso');

        if ($decoded instanceof Response) {
            return $decoded;
        }

        if (self::isList($decoded)) {
            return Response::error('El paso tiene que ser uno solo, con nombre y tipo.');
        }

        return self::stepBody($decoded);
    }

    /**
     * @return list<array<string, mixed>>|Response
     */
    public static function grades(mixed $value): array|Response
    {
        $decoded = self::decode($value, 'La lista de notas');

        if ($decoded instanceof Response) {
            return $decoded;
        }

        $grades = [];

        foreach (self::asList($decoded) as $row) {
            if (! is_array($row)) {
                return Response::error('Cada nota tiene que traer el correo de la persona.');
            }

            $grades[] = [
                'email' => trim((string) ($row['correo'] ?? '')),
                'level' => self::blankToNull($row['calificacion'] ?? null),
                'comment' => self::blankToNull($row['comentario'] ?? null),
            ];
        }

        return $grades;
    }

    /**
     * @return list<string>
     */
    public static function emails(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(
                static fn (mixed $email): string => trim((string) $email),
                self::asList($value),
            )));
        }

        $parts = preg_split('/\s*,\s*/', trim((string) $value)) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }

    /**
     * @return list<int>
     */
    public static function ids(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_map('intval', self::asList($value)));
        }

        $text = trim((string) $value);

        if ($text === '') {
            return [];
        }

        $parts = preg_split('/\s*,\s*/', $text) ?: [];

        return array_values(array_map('intval', array_filter($parts, static fn (string $part): bool => $part !== '')));
    }

    public static function flag(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        return in_array(mb_strtolower(trim((string) $value)), ['1', 'true', 'si', 'sí', 'yes'], true);
    }

    /**
     * @return array<string, mixed>|Response
     */
    public static function decode(mixed $value, string $label): array|Response
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return Response::error($label.' está vacía.');
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return Response::error($label.' no se entiende. Revisá comas, llaves y comillas.');
        }

        if (! is_array($decoded)) {
            return Response::error($label.' tiene que ser una lista.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $chapter
     * @return array<string, mixed>
     */
    private static function chapter(array $chapter): array
    {
        $body = [
            'name' => trim((string) ($chapter['nombre'] ?? '')),
        ];

        if (array_key_exists('visible', $chapter)) {
            $body['is_published'] = self::flag($chapter['visible']);
        }

        if (array_key_exists('orden', $chapter) && $chapter['orden'] !== null && $chapter['orden'] !== '') {
            $body['order'] = (int) $chapter['orden'];
        }

        if (isset($chapter['pasos']) && is_array($chapter['pasos'])) {
            $body['steps'] = array_map(
                static fn (mixed $step): array => is_array($step) ? self::stepBody($step) : [],
                self::asList($chapter['pasos']),
            );
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $step
     * @return array<string, mixed>
     */
    private static function stepBody(array $step): array
    {
        $body = [];

        if (array_key_exists('nombre', $step)) {
            $body['name'] = trim((string) $step['nombre']);
        }

        if (array_key_exists('opcional', $step)) {
            $body['is_optional'] = self::flag($step['opcional']);
        }

        if (array_key_exists('orden', $step) && $step['orden'] !== null && $step['orden'] !== '') {
            $body['order'] = (int) $step['orden'];
        }

        if (array_key_exists('tipo', $step) && is_string($step['tipo']) && $step['tipo'] !== '') {
            $body['type'] = match (mb_strtolower(trim($step['tipo']))) {
                'texto' => 'text',
                'video' => 'video',
                'documento' => 'document',
                'imagen' => 'image',
                'enlace' => 'link',
                default => trim($step['tipo']),
            };
        }

        if (array_key_exists('texto', $step)) {
            $body['text'] = $step['texto'];
        }

        if (array_key_exists('archivo', $step)) {
            $file = $step['archivo'];
            $body['library_item_id'] = $file === null || $file === '' ? null : (int) $file;
        }

        if (array_key_exists('enlace', $step)) {
            $body['url'] = $step['enlace'];
        }

        return $body;
    }

    private static function blankToNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<mixed>  $value
     * @return list<mixed>
     */
    private static function asList(array $value): array
    {
        if ($value === []) {
            return [];
        }

        return self::isList($value) ? array_values($value) : [$value];
    }

    /**
     * @param  array<mixed>  $value
     */
    private static function isList(array $value): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($value);
        }

        $index = 0;

        foreach ($value as $key => $unused) {
            if ($key !== $index) {
                return false;
            }

            $index++;
        }

        return true;
    }
}
