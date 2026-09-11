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

#[Name('actualizar-cliente')]
#[Description('Cambia el nombre o la descripción de un cliente. El nombre no se puede repetir en la misma cuenta.')]
class ActualizarCliente extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $clientId = (int) $request->get('client_id');

        $body = [];

        if ($request->get('name') !== null && $request->get('name') !== '') {
            $body['name'] = $request->get('name');
        }

        if ($request->get('description') !== null) {
            $body['description'] = $request->get('description') === ''
                ? null
                : $request->get('description');
        }

        if ($body === []) {
            return Response::error('Debes enviar al menos un campo a actualizar.');
        }

        return MailingApi::patch('accounts/'.$accountId.'/clients/'.$clientId, $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'account_id' => $schema->integer()
                ->description('Cuenta sobre la que trabajás; se obtiene con listar-cuentas.')
                ->required(),
            'client_id' => $schema->integer()
                ->description('Cliente sobre el que actuas.')
                ->required(),
            'name' => $schema->string()
                ->description('Nuevo nombre del cliente.'),
            'description' => $schema->string()
                ->description('Nueva descripción. Mandá texto vacío para borrarla.'),
        ];
    }
}
