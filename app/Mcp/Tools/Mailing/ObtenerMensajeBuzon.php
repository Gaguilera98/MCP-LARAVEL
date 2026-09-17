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

#[Name('obtener-mensaje-buzon')]
#[Description(
    'Un mensaje concreto del buzón: cuerpo en texto, contacto y adjuntos con url pública. '.
    'Pasá include_html=true solo si necesitás el HTML. El message_id sale de listar-mensajes-buzon.'
)]
class ObtenerMensajeBuzon extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $mailbox = MailingApi::mailboxSegment((string) $request->get('mailbox'));
        $messageId = (int) $request->get('message_id');

        return MailingApi::get(
            'accounts/'.$accountId.'/mailboxes/'.$mailbox.'/messages/'.$messageId,
            [
                'include_html' => $request->get('include_html'),
            ]
        );
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
            'mailbox' => $schema->string()
                ->description('Id del buzón o su email (ej. eva@dominio.com).')
                ->required(),
            'message_id' => $schema->integer()
                ->description('Id del mensaje; se obtiene con listar-mensajes-buzon.')
                ->required(),
            'include_html' => $schema->string()
                ->description('true para incluir body_html además del texto.'),
        ];
    }
}
