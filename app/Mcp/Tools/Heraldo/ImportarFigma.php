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

#[Name('importar-figma')]
#[Description(
    'Trae a la campaña los artes ya diseñados en Figma. Hace falta tener todos los copys completos y que no haya otra importación en curso. '.
    'Llámala primero con confirm false: analiza el archivo y no importa nada. preflight dice qué frames reconoció, cuáles faltan y cuáles están repetidos. '.
    'Si está bien, llámala con confirm true: queda en cola (queued true, frames) y el avance se ve en in_progress.figma_import de estado-produccion. '.
    'No gasta.'
)]
class ImportarFigma extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [
            'url' => $request->get('url'),
        ];

        $confirm = $this->optionalBool($request, 'confirm');

        if ($confirm !== null) {
            $body['confirm'] = $confirm;
        }

        return HeraldoApi::post($this->campaignPath($request, 'figma-import'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'url' => $schema->string()
                ->description('Enlace del archivo de Figma (o de la página o frame) donde están los artes de la campaña.')
                ->required(),
            'confirm' => $schema->boolean()
                ->description('false solo analiza. true importa. Si no lo indicas, solo analiza.'),
        ];
    }
}
