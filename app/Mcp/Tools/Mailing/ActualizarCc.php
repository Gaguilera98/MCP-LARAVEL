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

#[Name('actualizar-cc')]
#[Description('PATCH de un CC del catálogo: name, email y/o client_id. Email único por cliente.')]
class ActualizarCc extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $ccId = (int) $request->get('cc_recipient_id');

        $body = [];

        if ($request->get('client_id') !== null && $request->get('client_id') !== '') {
            $body['client_id'] = (int) $request->get('client_id');
        }

        if ($request->get('name') !== null) {
            $body['name'] = $request->get('name') === '' ? null : $request->get('name');
        }

        if ($request->get('email') !== null && $request->get('email') !== '') {
            $body['email'] = $request->get('email');
        }

        if ($body === []) {
            return Response::error('Debes enviar al menos un campo a actualizar.');
        }

        return MailingApi::patch('accounts/'.$accountId.'/cc-recipients/'.$ccId, $body);
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
            'cc_recipient_id' => $schema->integer()
                ->description('ID del CC.')
                ->required(),
            'client_id' => $schema->integer()
                ->description('Mover a otro cliente (opcional).'),
            'name' => $schema->string()
                ->description('Nombre (opcional).'),
            'email' => $schema->string()
                ->description('Email (opcional).'),
        ];
    }
}
