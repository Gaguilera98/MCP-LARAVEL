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

#[Name('upsert-participantes')]
#[Description('Upsert masivo por email (max 500). Body: participants_json = [{"email","first_name","last_name","attributes":{...}}]. attributes solo keys del field_schema.')]
class UpsertParticipantes extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $campaignId = (int) $request->get('campaign_id');
        $participants = MailingApi::decodeJsonArg($request->get('participants_json'), 'participants_json');
        if ($participants instanceof Response || $participants instanceof ResponseFactory) {
            return $participants;
        }

        return MailingApi::post(
            'accounts/'.$accountId.'/campaigns/'.$campaignId.'/participants',
            ['participants' => $participants]
        );
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
            'campaign_id' => $schema->integer()
                ->description('ID de la campaña.')
                ->required(),
            'participants_json' => $schema->string()
                ->description('JSON array de participantes (email obligatorio).')
                ->required(),
        ];
    }
}
