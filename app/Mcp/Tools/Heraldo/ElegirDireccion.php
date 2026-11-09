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

#[Name('elegir-direccion')]
#[Description(
    'Elige hacia dónde crece la foto en las adaptaciones de una columna: top deja la foto abajo y agrega arriba, bottom la deja arriba, left la deja a la derecha, right la deja a la izquierda. Sin direction (o center), centrada. Solo vale un lado de choices (ver-direcciones); otro responde 422 con los que sirven. Vale para las adaptaciones que se pidan después: para cambiar una que ya está, después usa regenerar-casilla. La columna base no se adapta (422). No gasta.'
)]
class ElegirDireccion extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [
            'ratio' => $request->get('ratio'),
        ];
        $direction = $request->get('direction');

        if (is_string($direction) && $direction !== '') {
            $body['direction'] = $direction;
        }

        return HeraldoApi::put($this->campaignPath($request, 'production/adaptation-directions'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'ratio' => $schema->string()
                ->description('La columna de adaptación, como sale en ver-direcciones.')
                ->required(),
            'direction' => $schema->string()
                ->description('El lado hacia donde crece la foto: top, bottom, left, right o center. Sin él, centrada.'),
        ];
    }
}
