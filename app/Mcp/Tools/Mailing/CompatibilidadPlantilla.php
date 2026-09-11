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

#[Name('compatibilidad-plantilla')]
#[Description(
    'Comprueba si la plantilla usa variables que la campaña no tiene definidas. No envía ningún correo. '.
    'Conviene revisarlo antes de crear el envío. Para ver cómo queda el correo usá previsualizar-envio.'
)]
class CompatibilidadPlantilla extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $templateId = (int) $request->get('template_id');

        return MailingApi::get(
            'accounts/'.$accountId.'/templates/'.$templateId.'/compatibility',
            ['campaign_id' => $request->get('campaign_id')]
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
            'template_id' => $schema->integer()
                ->description('Plantilla sobre la que actuas.')
                ->required(),
            'campaign_id' => $schema->integer()
                ->description('Campaña contra la que se comprueba la plantilla.')
                ->required(),
        ];
    }
}
