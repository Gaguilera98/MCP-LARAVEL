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
    'Alta de CC en el catálogo de un cliente. Requiere client_id + email. Opcional: name. '.
    'Email único por cliente. Luego asigná en campaña (cc_emails_json) y/o en el envío.'
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
                ->description('ID de la cuenta Mailing.')
                ->required(),
            'client_id' => $schema->integer()
                ->description('Cliente dueño del CC.')
                ->required(),
            'email' => $schema->string()
                ->description('Correo en copia.')
                ->required(),
            'name' => $schema->string()
                ->description('Nombre visible (opcional).'),
        ];
    }
}
