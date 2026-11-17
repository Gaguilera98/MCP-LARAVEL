<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('consultas-api-zalo')]
#[Description(
    'Registro de consultas a la API central de Zalo (GET /api/v1/api-requests): quién consultó qué, cuándo y cómo respondió. '.
    'Cada fila: at, token_id, user_id, method, route (plantilla con {tenantId}/{accountId}), path, tenant_id, account_id, query, status, duration_ms, ip. '.
    'Incluye las rechazadas por permisos (403); las que pasan del límite (429) y las sin token válido no se registran. No trae respuestas. '.
    'Solo responde con el token del equipo (*). Más reciente primero, paginado (per_page 1-200, default 50).'
)]
class ConsultasApiZalo extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $query = ZaloAuditQuery::build($request, ['token_id', 'tenant_id', 'status']);

        return ZaloAuditQuery::get('/api/v1/api-requests', $query, 'consultas');
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'date_from' => $schema->string()->description('Desde (YYYY-MM-DD, inclusive).'),
            'date_to' => $schema->string()->description('Hasta (YYYY-MM-DD, inclusive).'),
            'token_id' => $schema->integer()->description('Solo las consultas de este token (id numérico).'),
            'tenant_id' => $schema->string()->description('Solo las consultas a este tenant (UUID).'),
            'status' => $schema->integer()->description('Solo este código HTTP (por ejemplo 403).'),
            'page' => $schema->integer()->description('Página (por defecto 1).'),
            'per_page' => $schema->integer()->description('Filas por página, 1-200 (por defecto 50).'),
        ];
    }
}
