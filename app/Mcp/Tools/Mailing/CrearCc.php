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

#[Name('crear-cc')]
#[Description(
    'Da de alta un correo en copia dentro de un cliente. Esto todavía no lo agrega a ningún envío: '.
    'después lo elegís en la campaña o en el envío con cc_emails_json. El correo no se puede repetir en el mismo cliente.'
)]
class CrearCc extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');

        $body = [
            'client_id' => (int) $request->get('client_id'),
            'email' => $request->get('email'),
        ];

        if ($request->get('name') !== null && $request->get('name') !== '') {
            $body['name'] = $request->get('name');
        }

        return MailingApi::post('accounts/'.$accountId.'/cc-recipients', $body);
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
                ->description('Cliente al que pertenece este correo en copia.')
                ->required(),
            'email' => $schema->string()
                ->description('Correo en copia.')
                ->required(),
            'name' => $schema->string()
                ->description('Nombre de la persona o área, para identificarla en la lista.'),
        ];
    }
}
