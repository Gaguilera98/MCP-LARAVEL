<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('resumen-moderador')]
#[Description(
    'Qué marcó el moderador en un período y cuánto costó (GET /api/v1/tenants/{tenant_id}/content-reviews/summary). '.
    'Trae total, by_verdict, by_phase, by_tool, flagged_by_category, flagged_by_severity (alta = urgente, media = para el informe), flagged_by_account, flagged_top_users (las 10 personas con más avisos) y '.
    'cost {reviews_billed, provider_cost_usd (lo que pagó God al proveedor), charged_to_client_usd (lo que se le cobró al cliente: 0 mientras God pague la revisión)}. '.
    'Sirve para ajustar el reglamento: si una categoría marca de más o de menos. Después, revisiones-moderador con los mismos filtros para ver cada caso. Solo con el token del equipo (*).'
)]
class ResumenModerador extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $tenantId = (string) $request->get('tenant_id');
        if (preg_match('/^[A-Za-z0-9-]+$/', $tenantId) !== 1) {
            return Response::error('tenant_id inválido: tiene que ser el UUID del tenant.');
        }

        $query = array_diff_key(RevisionesModerador::query($request), ['page' => true, 'per_page' => true]);

        return ZaloAuditQuery::get('/api/v1/tenants/'.$tenantId.'/content-reviews/summary', $query, 'resumen');
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
            'phase' => $schema->string()->enum(['input', 'output', 'turn', 'provider'])->description('Solo este momento (provider = rechazos del proveedor).'),
            'tool' => $schema->string()->enum(['image-generator', 'image-editor', 'video-generator', 'video-editor', 'chat'])->description('Solo esta herramienta.'),
            'account_id' => $schema->string()->description('Solo esta cuenta (id numérico).'),
        ];
    }
}
