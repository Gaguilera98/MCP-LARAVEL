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

#[Name('crear-campana')]
#[Description(
    'Crea campaña. Requiere name. Opcional: description, client_id, field_schema_json, cc_emails_json. '.
    'field_schema_json: [{"label":"Enlace","type":"url"}] — type=text|url|bool; key opcional (si no, se genera del label). '.
    'Luego usá upsert-participantes con attributes usando esas keys. Devuelve warnings si CC se descartan.'
)]
class CrearCampana extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');

        $body = [
            'name' => $request->get('name'),
        ];

        if ($request->get('description') !== null && $request->get('description') !== '') {
            $body['description'] = $request->get('description');
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

        return MailingApi::post('accounts/'.$accountId.'/campaigns', $body);
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
                ->description('Nombre de la campaña.')
                ->required(),
            'description' => $schema->string()
                ->description('Descripción (opcional).'),
            'client_id' => $schema->integer()
                ->description('Cliente de la cuenta (opcional; necesario para CC).'),
            'field_schema_json' => $schema->string()
                ->description('JSON array de campos [{label, type, key?}].'),
            'cc_emails_json' => $schema->string()
                ->description('JSON array de CC del catálogo del cliente (opcional).'),
        ];
    }
}
