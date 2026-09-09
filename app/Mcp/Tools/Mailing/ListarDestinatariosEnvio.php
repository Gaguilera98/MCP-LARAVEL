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

#[Name('listar-destinatarios-envio')]
#[Description('Destinatarios de un envío. status=failed para ver fallidos antes de retry-failed-envio.')]
class ListarDestinatariosEnvio extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $sendId = (int) $request->get('send_id');

        return MailingApi::get('accounts/'.$accountId.'/sends/'.$sendId.'/recipients', [
            'status' => $request->get('status'),
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
            'send_id' => $schema->integer()
                ->description('ID del envío.')
                ->required(),
            'status' => $schema->string()
                ->description('Filtrar status (pending, sent, failed, skipped, …).'),
            'page' => $schema->integer()
                ->description('Página (default 1).'),
            'per_page' => $schema->integer()
                ->description('Por página (max 200).'),
        ];
    }
}
