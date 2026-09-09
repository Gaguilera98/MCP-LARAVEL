<?php

namespace App\Mcp\Tools\Mailing;

use App\Support\MailingApi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('listar-participantes')]
#[Description('Lista participantes de una campaña. Filtros email y q (nombre/email).')]
class ListarParticipantes extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $campaignId = (int) $request->get('campaign_id');

        return MailingApi::get(
            'accounts/'.$accountId.'/campaigns/'.$campaignId.'/participants',
            [
                'email' => $request->get('email'),
                'q' => $request->get('q'),
                'page' => $request->get('page'),
                'per_page' => $request->get('per_page'),
            ]
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'account_id' => $schema->integer()
                ->description('ID de la cuenta Mailing.')
                ->required(),
            'campaign_id' => $schema->integer()
                ->description('ID de la campaña.')
                ->required(),
            'email' => $schema->string()
                ->description('Filtro parcial por email.'),
            'q' => $schema->string()
                ->description('Búsqueda en email/nombre/apellidos.'),
            'page' => $schema->integer()
                ->description('Página (default 1).'),
            'per_page' => $schema->integer()
                ->description('Por página (max 200).'),
        ];
    }
}
