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

#[Name('listar-clientes')]
#[Description('Lista clientes de una cuenta (catálogo panel; el agente no crea clientes).')]
class ListarClientes extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $id = (int) $request->get('account_id');

        return MailingApi::get('accounts/'.$id.'/clients');
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
        ];
    }
}
