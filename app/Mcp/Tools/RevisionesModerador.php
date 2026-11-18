<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('revisiones-moderador')]
#[Description(
    'Revisiones del moderador de un tenant (GET /api/v1/tenants/{tenant_id}/content-reviews), de la más nueva a la más vieja. '.
    'El moderador lee todo en segundo plano (pedidos, resultados de imagen, turnos del chat y documentos) y marca posibles problemas de derechos de autor, ética o confidencialidad. Nunca frena a la persona. '.
    'Cada fila: created_at, account, user, tool, phase (input = pedido, output = resultado, turn = turno del chat, provider = el proveedor rechazó el pedido por su política de contenido; scan.provider_message trae su texto), subject_type y subject_id (la pieza), '.
    'verdict (flag = aviso, allow, error = no se pudo revisar), categories (copyright, etica, confidencial, marca, persona_publica: señales de «posible», no dictamen legal), reason, '.
    'tags (marca, campana, tipo_pieza, estilo, resumen: para la biblioteca de la organización), scan (escaneo sin IA de documentos: palabras clave y cantidades, sin los datos), notified_at. '.
    'Para ver solo los avisos: verdict=flag. Solo con el token del equipo (*). Paginado (per_page 1-200, default 50).'
)]
class RevisionesModerador extends Tool
{
    public const FILTERS = ['date_from', 'date_to', 'verdict', 'phase', 'tool', 'category', 'account_id', 'page', 'per_page'];

    public function handle(Request $request): Response|ResponseFactory
    {
        $tenantId = (string) $request->get('tenant_id');
        if (preg_match('/^[A-Za-z0-9-]+$/', $tenantId) !== 1) {
            return Response::error('tenant_id inválido: tiene que ser el UUID del tenant.');
        }

        return ZaloAuditQuery::get('/api/v1/tenants/'.$tenantId.'/content-reviews', self::query($request), 'revisiones');
    }

    /** @return array<string, string|int> */
    public static function query(Request $request): array
    {
        $query = [];
        foreach (self::FILTERS as $filter) {
            $value = $request->get($filter);
            if ($value !== null && $value !== '' && is_scalar($value)) {
                $query[$filter] = $filter === 'per_page' ? min(200, max(1, (int) $value)) : (string) $value;
            }
        }

        return $query;
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
            'verdict' => $schema->string()->enum(['flag', 'allow', 'error'])->description('flag = solo los avisos.'),
            'phase' => $schema->string()->enum(['input', 'output', 'turn', 'provider'])->description('input = pedido, output = resultado, turn = turno del chat, provider = rechazo del proveedor.'),
            'tool' => $schema->string()->enum(['image-generator', 'image-editor', 'video-generator', 'video-editor', 'chat'])->description('Solo esta herramienta.'),
            'category' => $schema->string()->enum(['copyright', 'etica', 'confidencial', 'marca', 'persona_publica'])->description('Solo las que tienen esta categoría.'),
            'account_id' => $schema->string()->description('Solo esta cuenta (id numérico).'),
            'page' => $schema->integer()->description('Página (por defecto 1).'),
            'per_page' => $schema->integer()->description('Filas por página, 1-200 (por defecto 50).'),
        ];
    }
}
