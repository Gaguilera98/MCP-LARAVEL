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

#[Name('test-envio')]
#[Description(
    'Correo de prueba REAL sobre un draft send_id (no lanza masivo). Dos usos: '.
    '(1) Probar plantilla — draft mínimo con esa template_id: validás HTML, asunto heredado y merge tags. '.
    '(2) Probar envío — draft ya configurado: validás subject override, audience_filter, attachments (p. ej. Drive) y CC. '.
    'Requiere participant_id (datos de merge) y test_recipient_ids_json=[ids de listar-destinatarios-prueba]. '.
    'Opcional cc_test_recipient_ids_json. Sin draft creado no hay prueba de adjuntos/filtro/CC.'
)]
class TestEnvio extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $sendId = (int) $request->get('send_id');

        $ids = MailingApi::decodeJsonArg($request->get('test_recipient_ids_json'), 'test_recipient_ids_json');
        if ($ids instanceof Response || $ids instanceof ResponseFactory) {
            return $ids;
        }

        $body = [
            'participant_id' => (int) $request->get('participant_id'),
            'test_recipient_ids' => $ids,
        ];

        $cc = MailingApi::decodeOptionalJsonArg($request->get('cc_test_recipient_ids_json'), 'cc_test_recipient_ids_json');
        if ($cc instanceof Response || $cc instanceof ResponseFactory) {
            return $cc;
        }
        if ($cc !== null) {
            $body['cc_test_recipient_ids'] = $cc;
        }

        return MailingApi::post('accounts/'.$accountId.'/sends/'.$sendId.'/test', $body);
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
            'participant_id' => $schema->integer()
                ->description('Participante cuyos datos se usan en merge tags.')
                ->required(),
            'test_recipient_ids_json' => $schema->string()
                ->description('JSON array de IDs de destinatarios de prueba.')
                ->required(),
            'cc_test_recipient_ids_json' => $schema->string()
                ->description('JSON array de IDs CC de prueba (opcional).'),
        ];
    }
}
