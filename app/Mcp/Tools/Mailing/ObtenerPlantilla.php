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
#[Description('Detalle de plantilla. include_design=true para traer el JSON Unlayer (pesado).')]
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
                ->description('ID de la cuenta Mailing.')
                ->required(),
            'template_id' => $schema->integer()
                ->description('ID de la plantilla.')
                ->required(),
            'include_design' => $schema->string()
                ->description('true para incluir design Unlayer.'),
        ];
    }
}
