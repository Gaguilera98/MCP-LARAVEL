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
#[Description(
    'Carga o actualiza personas de una campaña en lote, hasta 500 por vez. '.
    'Se identifican por correo: si ya existe se actualiza y si no se crea. '.
    'Cada persona se guarda con lo que mandes en esa llamada, así que incluí siempre todos sus datos: '.
    'lo que omitas (nombre, apellidos o campos extra) queda vacío. '.
    'En attributes solo se aceptan los campos extra definidos en la campaña; cualquier otro dato se descarta y te avisa en warnings.'
)]
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
                ->description('Cuenta sobre la que trabajás; se obtiene con listar-cuentas.')
                ->required(),
            'campaign_id' => $schema->integer()
                ->description('Campaña sobre la que actuás.')
                ->required(),
            'participants_json' => $schema->string()
                ->description('Lista de personas en formato JSON: [{"email":"ana@ejemplo.com","first_name":"Ana","last_name":"Paz","attributes":{"enlace":"https://ejemplo.com"}}]. El correo es obligatorio y attributes solo admite los campos extra de la campaña. Si la persona ya existe, incluí igual todos sus datos: lo que omitas queda vacío.')
                ->required(),
        ];
    }
}
