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
#[Description('Freno: verifica plantilla ↔ campaña (merge tags). Llamar antes de crear/lanzar envío.')]
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
                ->description('ID de la cuenta Mailing.')
                ->required(),
            'template_id' => $schema->integer()
                ->description('ID de la plantilla.')
                ->required(),
            'campaign_id' => $schema->integer()
                ->description('ID de la campaña a cruzar.')
                ->required(),
        ];
    }
}
