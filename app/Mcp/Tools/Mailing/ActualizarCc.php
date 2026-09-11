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
#[Description('Cambia el nombre, el correo o el cliente de una copia ya registrada. El correo no se puede repetir en el mismo cliente.')]
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
                ->description('Cuenta sobre la que trabajás; se obtiene con listar-cuentas.')
                ->required(),
            'cc_recipient_id' => $schema->integer()
                ->description('Correo en copia que querés modificar.')
                ->required(),
            'client_id' => $schema->integer()
                ->description('Cliente al que pasa a pertenecer.'),
            'name' => $schema->string()
                ->description('Nombre de la persona o área.'),
            'email' => $schema->string()
                ->description('Nuevo correo. No puede repetirse dentro del mismo cliente.'),
        ];
    }
}
