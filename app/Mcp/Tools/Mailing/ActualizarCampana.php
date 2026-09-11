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

#[Name('actualizar-campana')]
#[Description(
    'PATCH campaña: name, description, client_id, field_schema_json, cc_emails_json. '.
    'Al quitar un campo del schema se borran esos attributes de los participantes. '.
    'Si cambiás label pero mandás la misma key, la key se conserva.'
)]
class ActualizarCampana extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $campaignId = (int) $request->get('campaign_id');

        $body = [];

        foreach (['name', 'description'] as $field) {
            $value = $request->get($field);
            if ($value !== null && $value !== '') {
                $body[$field] = $value;
            }
        }

        if ($request->get('client_id') !== null && $request->get('client_id') !== '') {
            $body['client_id'] = (int) $request->get('client_id');
        }

        foreach (['field_schema' => 'field_schema_json', 'cc_emails' => 'cc_emails_json'] as $key => $arg) {
            $decoded = MailingApi::decodeOptionalJsonArg($request->get($arg), $arg);
            if ($decoded instanceof Response || $decoded instanceof ResponseFactory) {
                return $decoded;
            }
            if ($decoded !== null) {
                $body[$key] = $decoded;
            }
        }

        if ($body === []) {
            return Response::error('Debes enviar al menos un campo a actualizar.');
        }

        return MailingApi::patch('accounts/'.$accountId.'/campaigns/'.$campaignId, $body);
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
            'name' => $schema->string()
                ->description('Nombre (opcional).'),
            'description' => $schema->string()
                ->description('Descripción (opcional).'),
            'client_id' => $schema->integer()
                ->description('Cliente (opcional).'),
            'field_schema_json' => $schema->string()
                ->description('JSON array de campos [{label, type, key?}].'),
            'cc_emails_json' => $schema->string()
                ->description('JSON array de CC (opcional).'),
        ];
    }
}
