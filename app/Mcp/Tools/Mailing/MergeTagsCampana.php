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

#[Name('merge-tags-campana')]
#[Description(
    'Variables que podés usar en el asunto y el diseño para esta campaña: '.
    '{{nombre}}, {{apellidos}}, {{email}} y una por cada campo extra definido.'
)]
class MergeTagsCampana extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $campaignId = (int) $request->get('campaign_id');

        return MailingApi::get('accounts/'.$accountId.'/campaigns/'.$campaignId.'/merge-tags');
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
            'campaign_id' => $schema->integer()
                ->description('Campaña sobre la que actuás.')
                ->required(),
        ];
    }
}
