<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('cambios-tenant')]
#[Description(
    'Quién cambió un interruptor de un tenant y cuándo (GET /api/v1/tenants/{tenant_id}/audit-events). '.
    'subject_type: setting (configuraciones; las claves salen como [secreto]), ai_model (estado y fecha de fin), model_pricing (precio y margen), '.
    'account (nombre y estado), account_credit_limit, user_credit_limit, user (solo estado). '.
    'Cada fila: at, event (created|updated|deleted), subject_type, subject_id, subject_label, changes {campo: {from, to}}, actor_id, actor_name, source (panel|consola|api). '.
    'Solo cambios guardados con Eloquent (panel, comandos que usan modelos). Solo responde con el token del equipo (*). Paginado (per_page 1-200, default 50).'
)]
class CambiosTenant extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $tenantId = (string) $request->get('tenant_id');
        if (preg_match('/^[A-Za-z0-9-]+$/', $tenantId) !== 1) {
            return Response::error('tenant_id inválido: tiene que ser el UUID del tenant.');
        }

        $query = ZaloAuditQuery::build($request, ['subject_type', 'subject_id']);

        return ZaloAuditQuery::get('/api/v1/tenants/'.$tenantId.'/audit-events', $query, 'cambios');
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'tenant_id' => $schema->string()->description('UUID del tenant.')->required(),
            'date_from' => $schema->string()->description('Desde (YYYY-MM-DD, inclusive).'),
            'date_to' => $schema->string()->description('Hasta (YYYY-MM-DD, inclusive).'),
            'subject_type' => $schema->string()
                ->enum(['setting', 'ai_model', 'model_pricing', 'account', 'account_credit_limit', 'user_credit_limit', 'user'])
                ->description('Solo este tipo de cambio.'),
            'subject_id' => $schema->string()->description('Solo los cambios de este registro (id).'),
            'page' => $schema->integer()->description('Página (por defecto 1).'),
            'per_page' => $schema->integer()->description('Filas por página, 1-200 (por defecto 50).'),
        ];
    }
}
