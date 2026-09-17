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

#[Name('listar-mensajes-buzon')]
#[Description(
    'Mensajes de un buzón IMAP (recibidos y/o enviados), con preview o cuerpo y adjuntos (url pública). '.
    'Caso evaluador: pasá contact_email (o contact_id) + from/to para ver si esa persona escribió en el rango; '.
    'orden default desc (últimos primero); include_body=true para traer el texto completo. '.
    'folder: inbox, sent o all (default all).'
)]
class ListarMensajesBuzon extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $mailbox = MailingApi::mailboxSegment((string) $request->get('mailbox'));

        return MailingApi::get('accounts/'.$accountId.'/mailboxes/'.$mailbox.'/messages', [
            'from' => $request->get('from'),
            'to' => $request->get('to'),
            'contact_id' => $request->get('contact_id'),
            'contact_email' => $request->get('contact_email'),
            'folder' => $request->get('folder'),
            'has_attachments' => $request->get('has_attachments'),
            'unread' => $request->get('unread'),
            'search' => $request->get('search'),
            'order' => $request->get('order'),
            'include_body' => $request->get('include_body'),
            'include_html' => $request->get('include_html'),
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
                ->description('Fecha inicio inclusiva (Y-m-d o ISO).'),
            'to' => $schema->string()
                ->description('Fecha fin inclusiva (Y-m-d o ISO).'),
            'contact_email' => $schema->string()
                ->description('Email del contacto a evaluar; filtra la conversación con esa persona.'),
            'contact_id' => $schema->integer()
                ->description('Id del contacto (alternativa a contact_email); sale de listar-contactos-buzon.'),
            'folder' => $schema->string()
                ->description('inbox, sent o all (default all).'),
            'has_attachments' => $schema->string()
                ->description('true para solo mensajes con adjuntos.'),
            'unread' => $schema->string()
                ->description('true para solo no leídos.'),
            'search' => $schema->string()
                ->description('Buscar en asunto, remitente, destinatario o cuerpo.'),
            'order' => $schema->string()
                ->description('desc (default, últimos primero) o asc (cronológico).'),
            'include_body' => $schema->string()
                ->description('true para incluir body_text completo en cada ítem (recomendado al evaluar).'),
            'include_html' => $schema->string()
                ->description('true para incluir body_html (solo si include_body o en obtener-mensaje-buzon).'),
            'page' => $schema->integer()
                ->description('Número de página; empieza en 1.'),
            'per_page' => $schema->integer()
                ->description('Cuántos resultados por página; máximo 200.'),
        ];
    }
}
