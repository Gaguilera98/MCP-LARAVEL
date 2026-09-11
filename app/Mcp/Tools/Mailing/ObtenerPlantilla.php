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

#[Name('obtener-plantilla')]
#[Description(
    'Contenido de una plantilla: nombre, asunto y HTML. '.
    'Pedí include_design solo si necesitás el diseño editable, porque la respuesta se vuelve muy larga.'
)]
class ObtenerPlantilla extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $templateId = (int) $request->get('template_id');

        return MailingApi::get('accounts/'.$accountId.'/templates/'.$templateId, [
            'include_design' => $request->get('include_design'),
        ]);
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
            'include_design' => $schema->string()
                ->description('true para incluir el diseño editable. Hace la respuesta mucho más larga.'),
        ];
    }
}
