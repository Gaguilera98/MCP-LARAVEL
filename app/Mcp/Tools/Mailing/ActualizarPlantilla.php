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
    'PATCH plantilla. Parcial: name y/o subject. Completo: template_json format_version 1 '.
    '(content.html + content.design). Ver formato-plantilla.'
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
                ->description('ID de la cuenta Mailing.')
                ->required(),
            'template_id' => $schema->integer()
                ->description('ID de la plantilla.')
                ->required(),
            'name' => $schema->string()
                ->description('Nombre (opcional, PATCH parcial).'),
            'subject' => $schema->string()
                ->description('Asunto (opcional, PATCH parcial).'),
            'template_json' => $schema->string()
                ->description('JSON format_version 1 completo (opcional).'),
            'include_design' => $schema->boolean()
                ->description('Incluir design en la respuesta.'),
        ];
    }
}
