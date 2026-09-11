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

#[Name('actualizar-envio')]
#[Description(
    'Cambia un envío mientras sigue en borrador: nombre, campaña, plantilla, remitente, asunto, filtro de audiencia, copias o adjuntos. '.
    'Si cambiás la plantilla y no mandás asunto, se toma el de la plantilla nueva. '.
    'Una vez lanzado el envío ya no se puede modificar.'
)]
class ActualizarEnvio extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $sendId = (int) $request->get('send_id');

        $body = [];
        foreach (['name', 'subject'] as $field) {
            $value = $request->get($field);
            if ($value !== null && $value !== '') {
                $body[$field] = $value;
            }
        }
        foreach (['campaign_id', 'template_id', 'mail_sender_id'] as $field) {
            $value = $request->get($field);
            if ($value !== null && $value !== '') {
                $body[$field] = (int) $value;
            }
        }

        foreach (['audience_filter' => 'audience_filter_json', 'attachments' => 'attachments_json', 'cc_emails' => 'cc_emails_json'] as $key => $arg) {
            if ($request->get($arg) === null || $request->get($arg) === '') {
                continue;
            }
            $decoded = MailingApi::decodeJsonArg($request->get($arg), $arg);
            if ($decoded instanceof Response || $decoded instanceof ResponseFactory) {
                return $decoded;
            }
            $body[$key] = $decoded;
        }

        if ($body === []) {
            return Response::error('Debes enviar al menos un campo a actualizar.');
    }

        return MailingApi::patch('accounts/'.$accountId.'/sends/'.$sendId, $body);
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
            'send_id' => $schema->integer()
                ->description('Envío sobre el que actuás.')
                ->required(),
            'name' => $schema->string()->description('Nuevo nombre del envío.'),
            'campaign_id' => $schema->integer()->description('Otra campaña de destinatarios.'),
            'template_id' => $schema->integer()->description('Otra plantilla. Si no mandás asunto, se toma el de esta plantilla.'),
            'mail_sender_id' => $schema->integer()->description('Otra dirección de salida; se obtiene con listar-remitentes.'),
            'subject' => $schema->string()->description('Asunto propio para este envío.'),
            'audience_filter_json' => $schema->string()->description('Condiciones para elegir a quién le llega, en formato JSON: {"logic":"all","rules":[{"field":"email","operator":"contains","value":"@empresa.com"}]}.'),
            'attachments_json' => $schema->string()->description('Adjuntos en formato JSON: [{"mode":"fixed_url","name":"Guia.pdf","url":"https://ejemplo.com/guia.pdf"}] o [{"mode":"field","field":"enlace","name":"Documento"}]. Reemplaza los adjuntos anteriores; mandá [] para quitarlos todos.'),
            'cc_emails_json' => $schema->string()->description('Correos en copia, en formato JSON: ["copia@cliente.com"]. Reemplaza los anteriores y solo admite los dados de alta en el cliente de la campaña.'),
        ];
    }
}
