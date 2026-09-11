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

#[Name('crear-envio')]
#[Description(
    'Crea un envío en borrador combinando campaña, plantilla y remitente. No manda ningún correo todavía. '.
    'Si no indicás asunto se usa el de la plantilla. '.
    'Podés limitar a quién le llega con audience_filter_json, sumar correos en copia con cc_emails_json '.
    'y agregar adjuntos con attachments_json (mismo archivo para todos o uno por persona, hasta 10 MB cada uno, con enlace de descarga directa). '.
    'El borrador es lo que después probás con preview-envio y test-envio.'
)]
class CrearEnvio extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');

        $body = [
            'name' => $request->get('name'),
            'campaign_id' => (int) $request->get('campaign_id'),
            'template_id' => (int) $request->get('template_id'),
        ];

        if ($request->get('mail_sender_id') !== null && $request->get('mail_sender_id') !== '') {
            $body['mail_sender_id'] = (int) $request->get('mail_sender_id');
    }
        if ($request->get('subject') !== null && $request->get('subject') !== '') {
            $body['subject'] = $request->get('subject');
    }

        foreach (['audience_filter' => 'audience_filter_json', 'attachments' => 'attachments_json', 'cc_emails' => 'cc_emails_json'] as $key => $arg) {
            $decoded = MailingApi::decodeOptionalJsonArg($request->get($arg), $arg);
            if ($decoded instanceof Response || $decoded instanceof ResponseFactory) {
                return $decoded;
            }
            if ($decoded !== null) {
                $body[$key] = $decoded;
            }
        }

        return MailingApi::post('accounts/'.$accountId.'/sends', $body);
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
            'name' => $schema->string()
                ->description('Nombre del envío.')
                ->required(),
            'campaign_id' => $schema->integer()
                ->description('Campaña con las personas que van a recibir el correo.')
                ->required(),
            'template_id' => $schema->integer()
                ->description('Plantilla con el diseño y el asunto del correo.')
                ->required(),
            'mail_sender_id' => $schema->integer()
                ->description('Dirección desde la que sale el correo; se obtiene con listar-remitentes.'),
            'subject' => $schema->string()
                ->description('Asunto propio para este envío. Si lo omitís se usa el de la plantilla.'),
            'audience_filter_json' => $schema->string()
                ->description('Opcional. Condiciones para elegir a quién le llega, en formato JSON: {"logic":"all","rules":[{"field":"email","operator":"contains","value":"@empresa.com"}]}. Sin filtro le llega a toda la campaña.'),
            'attachments_json' => $schema->string()
                ->description('Opcional. Adjuntos en formato JSON. Mismo archivo para todos: [{"mode":"fixed_url","name":"Guia.pdf","url":"https://ejemplo.com/guia.pdf"}]. Uno distinto por persona: [{"mode":"field","field":"enlace","name":"Documento"}], donde field es un campo extra de la campaña con una URL. Hasta 10 MB por archivo y el enlace debe descargar directamente.'),
            'cc_emails_json' => $schema->string()
                ->description('Opcional. Correos en copia, en formato JSON: ["copia@cliente.com"]. Solo se aceptan los dados de alta en el cliente de la campaña; el resto se descarta y se avisa en warnings.'),
        ];
    }
}
