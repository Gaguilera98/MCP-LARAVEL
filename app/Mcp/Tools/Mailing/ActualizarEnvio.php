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
    'PATCH solo si status=draft. Campos opcionales iguales a crear-envio '.
    '(name, campaign_id, template_id, mail_sender_id, subject, audience_filter_json, attachments_json, cc_emails_json). '.
    'Si cambiás template_id sin mandar subject, el asunto pasa al de la plantilla nueva. '.
    'Adjuntos: mismos límites que crear-envio (≤10MB, fixed_url|field, Drive archivo público, sin carpetas).'
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
                ->description('ID de la cuenta Mailing.')
                ->required(),
            'send_id' => $schema->integer()
                ->description('ID del envío.')
                ->required(),
            'name' => $schema->string()->description('Nombre (opcional).'),
            'campaign_id' => $schema->integer()->description('Campaña (opcional).'),
            'template_id' => $schema->integer()->description('Plantilla (opcional).'),
            'mail_sender_id' => $schema->integer()->description('Remitente (opcional).'),
            'subject' => $schema->string()->description('Asunto (opcional).'),
            'audience_filter_json' => $schema->string()->description('JSON audience_filter (opcional).'),
            'attachments_json' => $schema->string()->description('JSON adjuntos (opcional).'),
            'cc_emails_json' => $schema->string()->description('JSON CC (opcional).'),
        ];
    }
}
