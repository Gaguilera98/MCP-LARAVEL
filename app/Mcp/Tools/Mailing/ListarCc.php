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

#[Name('listar-cc')]
#[Description('Lista destinatarios CC registrados. Filtrar con client_id cuando la campaña tiene cliente.')]
class ListarCc extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $id = (int) $request->get('account_id');

        return MailingApi::get('accounts/'.$id.'/cc-recipients', [
            'client_id' => $request->get('client_id'),
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
            'client_id' => $schema->integer()
                ->description('Filtrar CC del cliente (recomendado).'),
            'page' => $schema->integer()
                ->description('Página (default 1).'),
            'per_page' => $schema->integer()
                ->description('Por página (max 200).'),
        ];
    }
}
