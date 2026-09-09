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

#[Name('actualizar-participante')]
#[Description('PATCH de un participante. Campos opcionales: email, first_name, last_name, attributes_json.')]
class ActualizarParticipante extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $campaignId = (int) $request->get('campaign_id');
        $participantId = (int) $request->get('participant_id');

        $body = [];
        foreach (['email', 'first_name', 'last_name'] as $field) {
            $value = $request->get($field);
            if ($value !== null && $value !== '') {
                $body[$field] = $value;
            }
        }

        $attributes = MailingApi::decodeOptionalJsonArg($request->get('attributes_json'), 'attributes_json');
        if ($attributes instanceof Response || $attributes instanceof ResponseFactory) {
            return $attributes;
        }
        if ($attributes !== null) {
            $body['attributes'] = $attributes;
        }

        if ($body === []) {
            return Response::error('Debes enviar al menos un campo a actualizar.');
    }

        return MailingApi::patch(
            'accounts/'.$accountId.'/campaigns/'.$campaignId.'/participants/'.$participantId,
            $body
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
            'participant_id' => $schema->integer()
                ->description('ID del participante.')
                ->required(),
            'email' => $schema->string()
                ->description('Nuevo email (opcional).'),
            'first_name' => $schema->string()
                ->description('Nombre (opcional).'),
            'last_name' => $schema->string()
                ->description('Apellidos (opcional).'),
            'attributes_json' => $schema->string()
                ->description('JSON objeto de atributos de campaña (opcional).'),
        ];
    }
}
