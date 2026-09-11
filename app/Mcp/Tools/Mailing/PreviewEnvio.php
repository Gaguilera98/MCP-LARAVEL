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

#[Name('preview-envio')]
#[Description(
    'Preview sobre un draft send_id (sin correo real). Dos usos: '.
    '(1) Probar plantilla — draft con esa template_id: HTML/asunto/merge tags del participant_id. '.
    '(2) Probar envío — mismo draft ya con subject override, filtro o adjuntos: ves el render con esa config. '.
    'Devuelve missing_tags. Adjuntos no se descargan en preview (eso se valida en test-envio).'
)]
class PreviewEnvio extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $sendId = (int) $request->get('send_id');

        return MailingApi::post('accounts/'.$accountId.'/sends/'.$sendId.'/preview', [
            'participant_id' => (int) $request->get('participant_id'),
        ]);
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
                ->description('Participante de la campaña del envío.')
                ->required(),
        ];
    }
}
