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

#[Name('listar-envios')]
#[Description('Lista envíos de la cuenta. Filtros opcionales status y campaign_id.')]
class ListarEnvios extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $id = (int) $request->get('account_id');

        return MailingApi::get('accounts/'.$id.'/sends', [
            'status' => $request->get('status'),
            'campaign_id' => $request->get('campaign_id'),
            'page' => $request->get('page'),
            'per_page' => $request->get('per_page'),
        ]);
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
            'status' => $schema->string()
                ->description('Filtrar por status (draft, queued, sending, paused, completed, failed, …).'),
            'campaign_id' => $schema->integer()
                ->description('Filtrar por campaña.'),
            'page' => $schema->integer()
                ->description('Página (default 1).'),
            'per_page' => $schema->integer()
                ->description('Por página (max 200).'),
        ];
    }
}
