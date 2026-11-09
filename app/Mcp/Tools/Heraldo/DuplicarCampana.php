<?php

namespace App\Mcp\Tools\Heraldo;

use App\Support\HeraldoApi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('duplicar-campana')]
#[Description(
    'Duplica la campaña, como el botón «Duplicar» de Mis campañas: copia canales, formatos, filas, mensajes, cuadros, «Foto que usa» y «La foto llega en», y los textos salvo que copy_texts sea false. '.
    'Nunca copia fotos, artes ni videos: para las fotos, usa traer-fotos desde la campaña original. La copia queda en borrador. Sin name, se llama «… (copia)». Devuelve el id de la campaña nueva.'
)]
class DuplicarCampana extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [];
        $name = $request->get('name');
        $copyTexts = $this->optionalBool($request, 'copy_texts');

        if (is_string($name) && trim($name) !== '') {
            $body['name'] = $name;
        }

        if ($copyTexts !== null) {
            $body['copy_texts'] = $copyTexts;
        }

        return HeraldoApi::post($this->campaignPath($request, 'duplicate'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'name' => $schema->string()
                ->description('Nombre de la campaña nueva, hasta 255 caracteres. Sin él, se llama «… (copia)».'),
            'copy_texts' => $schema->boolean()
                ->description('false para no copiar los textos. Por defecto, true.'),
        ];
    }
}
