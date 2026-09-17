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

#[Name('listar-contactos-buzon')]
#[Description(
    'Contactos con conversación en un buzón (thread_count, unread, último asunto). '.
    'Filtrá por from/to (fechas Y-m-d) si querés saber quién escribió en un rango. '.
    'Para leer los mensajes de una persona usá listar-mensajes-buzon con contact_email o contact_id.'
)]
class ListarContactosBuzon extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $mailbox = MailingApi::mailboxSegment((string) $request->get('mailbox'));

        return MailingApi::get('accounts/'.$accountId.'/mailboxes/'.$mailbox.'/contacts', [
            'from' => $request->get('from'),
            'to' => $request->get('to'),
            'folder' => $request->get('folder'),
            'search' => $request->get('search'),
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
                ->description('Cuenta sobre la que trabajás; se obtiene con listar-cuentas.')
                ->required(),
            'mailbox' => $schema->string()
                ->description('Id del buzón o su email (ej. eva@dominio.com).')
                ->required(),
            'from' => $schema->string()
                ->description('Fecha inicio inclusiva (Y-m-d o ISO), sobre received_at.'),
            'to' => $schema->string()
                ->description('Fecha fin inclusiva (Y-m-d o ISO), sobre received_at.'),
            'folder' => $schema->string()
                ->description('inbox, sent o all (default all).'),
            'search' => $schema->string()
                ->description('Buscar en email o nombre del contacto.'),
            'page' => $schema->integer()
                ->description('Número de página; empieza en 1.'),
            'per_page' => $schema->integer()
                ->description('Cuántos resultados por página; máximo 200.'),
        ];
    }
}
