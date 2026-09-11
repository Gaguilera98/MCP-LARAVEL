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

#[Name('actualizar-plantilla')]
#[Description(
    'Cambia una plantilla. Podés tocar solo el nombre o el asunto, '.
    'o reemplazar todo el diseño mandando template_json con la estructura que devuelve formato-plantilla.'
)]
class ActualizarPlantilla extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $templateId = (int) $request->get('template_id');

        $json = $request->get('template_json');
        if ($json !== null && $json !== '') {
            $decoded = MailingApi::decodeJsonArg($json, 'template_json');
            if ($decoded instanceof Response || $decoded instanceof ResponseFactory) {
                return $decoded;
            }

            if (filter_var($request->get('include_design'), FILTER_VALIDATE_BOOLEAN)) {
                $decoded['include_design'] = true;
            }

            return MailingApi::patch(
                'accounts/'.$accountId.'/templates/'.$templateId,
                $decoded
            );
        }

        $body = [];
        if ($request->get('name') !== null && $request->get('name') !== '') {
            $body['name'] = $request->get('name');
        }
        if ($request->get('subject') !== null && $request->get('subject') !== '') {
            $body['subject'] = $request->get('subject');
        }

        if ($body === []) {
            return Response::error('Enviá name/subject o template_json completo.');
        }

        if (filter_var($request->get('include_design'), FILTER_VALIDATE_BOOLEAN)) {
            $body['include_design'] = true;
        }

        return MailingApi::patch('accounts/'.$accountId.'/templates/'.$templateId, $body);
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
            'name' => $schema->string()
                ->description('Nuevo nombre de la plantilla.'),
            'subject' => $schema->string()
                ->description('Nuevo asunto. Puede incluir variables, por ejemplo "Hola {{nombre}}".'),
            'template_json' => $schema->string()
                ->description('La plantilla completa en formato JSON, con la estructura que devuelve formato-plantilla. Reemplaza el diseño actual.'),
            'include_design' => $schema->boolean()
                ->description('true para devolver también el diseño editable. Hace la respuesta mucho más larga.'),
        ];
    }
}
