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

#[Name('formato-plantilla')]
#[Description(
    'Explica cómo se arma una plantilla y devuelve un ejemplo completo listo para copiar. '.
    'Consultala antes de crear o reemplazar una plantilla propia; el resultado es lo que va en template_json.'
)]
class FormatoPlantilla extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');

        return MailingApi::get('accounts/'.$accountId.'/templates/format');
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'account_id' => $schema->integer()
                ->description('Cuenta sobre la que trabajás.')
                ->required(),
        ];
    }
}
