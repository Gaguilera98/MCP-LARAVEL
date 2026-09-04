<?php

namespace App\Support;

use InvalidArgumentException;

final class McpServers
{
    /**
     * @return array<string, string> id => label
     */
    public static function all(): array
    {
        return [
            'zalo-tenants' => 'Zalo Tenants',
            'santox-tenants' => 'Santox Tenants',
        ];
    }

    public static function assertValid(string $id): void
    {
        if (! array_key_exists($id, self::all())) {
            throw new InvalidArgumentException("Unknown MCP server: {$id}");
        }
    }

    public static function label(string $id): string
    {
        return self::all()[$id] ?? $id;
    }
}
