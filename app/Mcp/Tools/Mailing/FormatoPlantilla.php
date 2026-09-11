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
    'Devuelve el formato JSON de plantilla (format_version 1) + ejemplo Bienvenida Zalo. '.
    'Usá ese schema en crear-plantilla (template_json) o from_example=true. '.
    'Campos: format_version, name, subject, content.html, content.design (Unlayer). '.
    'Merge tags: {{nombre}}, {{apellidos}}, {{email}}, {{keys del field_schema}}.'
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
                ->description('ID de la cuenta Mailing (scoped).')
                ->required(),
        ];
    }
}
