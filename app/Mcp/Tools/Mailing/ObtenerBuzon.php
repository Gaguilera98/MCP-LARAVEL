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

#[Name('obtener-buzon')]
#[Description(
    'Detalle de un buzón: nombre, email, última sync y conteos (recibidos, enviados, no leídos). '.
    'mailbox acepta id numérico o el email del buzón.'
)]
class ObtenerBuzon extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $mailbox = MailingApi::mailboxSegment((string) $request->get('mailbox'));

        return MailingApi::get('accounts/'.$accountId.'/mailboxes/'.$mailbox);
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
                ->description('Id del buzón o su email (ej. eva@dominio.com). Se obtiene con listar-buzones.')
                ->required(),
        ];
    }
}
