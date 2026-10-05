<?php

namespace App\Support;

/**
 * Enriquece credit_limit de la API Zalo con etiquetas legibles para el agente MCP.
 */
class CreditLimitEnricher
{
    /**
     * Significado corto de cada source (para source_label).
     *
     * @var array<string, string>
     */
    public const SOURCE_LABELS = [
        'subscription' => 'Taller (suscripción)',
        'personal' => 'Personal (mensual)',
        'account' => 'Cuenta (mensual)',
        'none' => 'Sin límite',
    ];

    /**
     * Significado extendido de cada source.
     *
     * @var array<string, string>
     */
    public const SOURCE_MEANINGS = [
        'subscription' => 'Tope del taller: cada usuario puede gastar hasta este monto en todo el periodo de la suscripción vigente (window_start..window_end). No se reinicia al cambiar de mes.',
        'personal' => 'Límite personal permanente del usuario. Se mide por mes calendario.',
        'account' => 'Límite mensual de la cuenta (default para todos). Se mide por mes calendario.',
        'none' => 'No hay límite de créditos configurado para este usuario en esta cuenta.',
    ];

    /**
     * @param  array<string, mixed>|null  $creditLimit
     * @return array<string, mixed>|null
     */
    public static function enrich(?array $creditLimit): ?array
    {
        if ($creditLimit === null) {
            return null;
        }

        $source = (string) ($creditLimit['source'] ?? 'none');

        $creditLimit['source_label'] = self::SOURCE_LABELS[$source]
            ?? self::SOURCE_LABELS['none'];
        $creditLimit['source_meaning'] = self::SOURCE_MEANINGS[$source]
            ?? self::SOURCE_MEANINGS['none'];

        return $creditLimit;
    }

    /**
     * @param  array<int, mixed>  $users
     * @return array<int, mixed>
     */
    public static function enrichUsersList(array $users): array
    {
        return array_map(function ($user) {
            if (! is_array($user)) {
                return $user;
            }

            if (isset($user['credit_limit']) && is_array($user['credit_limit'])) {
                $user['credit_limit'] = self::enrich($user['credit_limit']);
            }

            return $user;
        }, $users);
    }

    /**
     * Ítems de usage/by-user: credit_limit va a nivel del ítem (junto a user, by_tool, …).
     *
     * @param  array<int, mixed>  $rows
     * @return array<int, mixed>
     */
    public static function enrichUsageByUserRows(array $rows): array
    {
        return array_map(function ($row) {
            if (! is_array($row)) {
                return $row;
            }

            if (isset($row['credit_limit']) && is_array($row['credit_limit'])) {
                $row['credit_limit'] = self::enrich($row['credit_limit']);
            }

            return $row;
        }, $rows);
    }
}
