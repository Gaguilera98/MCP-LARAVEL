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
    'Crea una plantilla de correo, es decir el diseño y el asunto. Hay dos caminos: '.
    'copiar el ejemplo oficial con from_example y cambiarle nombre y asunto, o mandar tu propia plantilla en template_json '.
    'con la estructura que devuelve formato-plantilla. Podés usar variables como {{nombre}} o los campos extra de la campaña.'
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
                ->description('Cuenta sobre la que trabajás; se obtiene con listar-cuentas.')
                ->required(),
            'from_example' => $schema->boolean()
                ->description('true para partir de la plantilla de ejemplo y solo cambiarle nombre y asunto. Dejalo en false si mandás template_json.'),
            'name' => $schema->string()
                ->description('Nombre de la plantilla. Si mandás template_json el nombre puede ir ahí dentro.'),
            'subject' => $schema->string()
                ->description('Asunto del correo. Puede incluir variables, por ejemplo "Hola {{nombre}}".'),
            'template_json' => $schema->string()
                ->description('La plantilla completa en formato JSON, con la estructura que devuelve formato-plantilla. Alternativa a from_example.'),
            'include_design' => $schema->boolean()
                ->description('true para devolver también el diseño editable. Hace la respuesta mucho más larga.'),
        ];
    }
}
