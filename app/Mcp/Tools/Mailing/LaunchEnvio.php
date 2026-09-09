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

#[Name('launch-envio')]
#[Description('Lanza el envío. OBLIGATORIO confirm_recipient_count = valid_emails de audiencia-envio. Si no coincide → 409 sin enviar.')]
class LaunchEnvio extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $sendId = (int) $request->get('send_id');

        return MailingApi::post('accounts/'.$accountId.'/sends/'.$sendId.'/launch', [
            'confirm_recipient_count' => (int) $request->get('confirm_recipient_count'),
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
            'confirm_recipient_count' => $schema->integer()
                ->description('Debe igualar valid_emails de audiencia-envio.')
                ->required(),
        ];
    }
}
