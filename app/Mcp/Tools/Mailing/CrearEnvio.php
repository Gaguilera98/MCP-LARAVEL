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
#[Description('Crea envío en borrador. Requiere name, campaign_id, template_id. Opcional: mail_sender_id, subject, audience_filter_json, attachments_json (solo fixed_url|field), cc_emails_json. Devuelve warnings si CC se descartan.')]
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
                ->description('ID de la cuenta Mailing.')
                ->required(),
            'name' => $schema->string()
                ->description('Nombre del envío.')
                ->required(),
            'campaign_id' => $schema->integer()
                ->description('ID de campaña.')
                ->required(),
            'template_id' => $schema->integer()
                ->description('ID de plantilla.')
                ->required(),
            'mail_sender_id' => $schema->integer()
                ->description('Remitente (opcional).'),
            'subject' => $schema->string()
                ->description('Asunto (opcional; puede venir de plantilla).'),
            'audience_filter_json' => $schema->string()
                ->description('JSON del audience_filter (opcional).'),
            'attachments_json' => $schema->string()
                ->description('JSON adjuntos modo fixed_url|field (opcional).'),
            'cc_emails_json' => $schema->string()
                ->description('JSON array de CC (opcional; se validan contra catálogo).'),
        ];
    }
}
