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
    'Crea una campaña: el grupo de personas al que vas a escribir y los datos extra que querés personalizar. '.
    'Definí acá los campos extra (field_schema_json), por ejemplo [{"label":"Enlace","type":"url"}], antes de cargar personas: '.
    'cada campo genera una variable para la plantilla, como {{enlace}}, y los datos que no estén definidos se descartan al cargar participantes. '.
    'Si indicás un cliente podés sumarle correos en copia con cc_emails_json.'
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
                ->description('Cuenta sobre la que trabajás; se obtiene con listar-cuentas.')
                ->required(),
            'name' => $schema->string()
                ->description('Nombre de la campaña.')
                ->required(),
            'description' => $schema->string()
                ->description('Descripción interna de la campaña.'),
            'client_id' => $schema->integer()
                ->description('Cliente al que pertenece la campaña. Hace falta si querés usar correos en copia.'),
            'field_schema_json' => $schema->string()
                ->description('Campos extra para personalizar el correo, en formato JSON: [{"label":"Enlace","type":"url"}]. type puede ser text, url o bool. Cada campo genera una variable para la plantilla, por ejemplo {{enlace}}.'),
            'cc_emails_json' => $schema->string()
                ->description('Correos en copia, en formato JSON: ["copia@cliente.com"]. Solo se aceptan los dados de alta en ese cliente.'),
        ];
    }
}
