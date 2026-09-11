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
    'Cambia una campaña: nombre, descripción, cliente, campos extra o correos en copia. '.
    'Al mandar field_schema_json reemplazás la lista completa de campos extra, así que incluí también los que querés conservar. '.
    'Si quitás un campo, ese dato se borra de todas las personas de la campaña. '.
    'Para renombrar un campo sin perder los datos, mandá la misma key con el label nuevo.'
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
                ->description('Cuenta sobre la que trabajás; se obtiene con listar-cuentas.')
                ->required(),
            'campaign_id' => $schema->integer()
                ->description('Campaña sobre la que actuás.')
                ->required(),
            'name' => $schema->string()
                ->description('Nuevo nombre de la campaña.'),
            'description' => $schema->string()
                ->description('Descripción interna de la campaña.'),
            'client_id' => $schema->integer()
                ->description('Cliente al que pertenece la campaña.'),
            'field_schema_json' => $schema->string()
                ->description('Lista completa de campos extra, en formato JSON: [{"label":"Enlace","type":"url"}]. Reemplaza a la anterior, así que incluí también los que querés conservar; los que falten se borran junto con sus datos. Para renombrar sin perder datos, mandá la key original: [{"key":"enlace","label":"Enlace del evento","type":"url"}].'),
            'cc_emails_json' => $schema->string()
                ->description('Correos en copia, en formato JSON: ["copia@cliente.com"]. Reemplaza a los anteriores y solo admite los dados de alta en ese cliente.'),
        ];
    }
}
