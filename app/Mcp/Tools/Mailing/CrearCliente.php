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

#[Name('crear-cliente')]
#[Description(
    'Crea cliente (marca/agencia) en la cuenta. Requiere name. Opcional: description. '.
    'Nombre único por cuenta. Usalo antes de crear-campana con client_id (y CC del cliente).'
)]
class CrearCliente extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');

        $body = [
            'name' => $request->get('name'),
        ];

        if ($request->get('description') !== null) {
            $body['description'] = $request->get('description');
        }

        return MailingApi::post('accounts/'.$accountId.'/clients', $body);
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
            'name' => $schema->string()
                ->description('Nombre del cliente.')
                ->required(),
            'description' => $schema->string()
                ->description('Descripción (opcional).'),
        ];
    }
}
