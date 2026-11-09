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

#[Name('quitar-foto')]
#[Description(
    'Quita la foto de una casilla del paso 2, como «Quitar» de la página: borra la foto, la haya subido una persona o la haya hecho Santo. No gasta. Después se puede subir otra (subir-foto) o generarla (regenerar-casilla). Responde removed_stage (base o adaptation). 404 si la casilla no tiene foto y 409 si se está generando o la producción está finalizada.'
)]
class QuitarFoto extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::post($this->campaignPath($request, 'production/cells/remove'), [
            'strategy_id' => (int) $request->get('strategy_id'),
            'image_index' => (int) $request->get('image_index'),
            'ratio' => $request->get('ratio'),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'strategy_id' => $schema->integer()
                ->description('strategy_id de la fila, como sale en plan-produccion (rows).')
                ->required(),
            'image_index' => $schema->integer()
                ->description('image_index de la fila (1, 2…), como sale en plan-produccion (rows).')
                ->required(),
            'ratio' => $schema->string()
                ->description('La columna, como sale en plan-produccion: «4:5», «9:16». En la columna base es la foto base; en las demás, la adaptación.')
                ->required(),
        ];
    }
}
