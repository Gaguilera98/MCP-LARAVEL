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

#[Name('crear-plantilla')]
#[Description(
    'Crea plantilla. Opciones: (1) from_example=true (+ name/subject opcionales) clona el ejemplo oficial; '.
    '(2) template_json = JSON format_version 1 completo (ver formato-plantilla). '.
    'include_design=true para devolver el design Unlayer en la respuesta.'
)]
class CrearPlantilla extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $fromExample = filter_var($request->get('from_example'), FILTER_VALIDATE_BOOLEAN);

        if ($fromExample) {
            $body = ['from_example' => true];
            if ($request->get('name') !== null && $request->get('name') !== '') {
                $body['name'] = $request->get('name');
            }
            if ($request->get('subject') !== null && $request->get('subject') !== '') {
                $body['subject'] = $request->get('subject');
            }
            if (filter_var($request->get('include_design'), FILTER_VALIDATE_BOOLEAN)) {
                $body['include_design'] = true;
            }

            return MailingApi::post('accounts/'.$accountId.'/templates', $body);
        }

        $decoded = MailingApi::decodeJsonArg($request->get('template_json'), 'template_json');
        if ($decoded instanceof Response || $decoded instanceof ResponseFactory) {
            return $decoded;
        }

        if (filter_var($request->get('include_design'), FILTER_VALIDATE_BOOLEAN)) {
            $decoded['include_design'] = true;
        }

        return MailingApi::post('accounts/'.$accountId.'/templates', $decoded);
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
            'from_example' => $schema->boolean()
                ->description('true = clonar ejemplo Bienvenida Zalo.'),
            'name' => $schema->string()
                ->description('Nombre (con from_example o dentro de template_json).'),
            'subject' => $schema->string()
                ->description('Asunto (con from_example).'),
            'template_json' => $schema->string()
                ->description('JSON format_version 1 completo (si no usás from_example).'),
            'include_design' => $schema->boolean()
                ->description('Incluir design en la respuesta.'),
        ];
    }
}
