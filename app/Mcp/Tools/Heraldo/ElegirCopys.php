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

#[Name('elegir-copys')]
#[Description(
    'Guarda las opciones elegidas de la IA como los textos de la campaña. choices es { key: índice }, con el índice desde 0; sin choices, queda la primera opción de cada cuadro. '.
    'La respuesta trae over_max_length: las opciones guardadas que se pasan del largo. Muéstralo, porque esas hay que acortarlas con guardar-copys.'
)]
class ElegirCopys extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->withJsonFields($request, [], [
            'choices_json' => 'choices',
        ]);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::post($this->campaignPath($request, 'copies/generate/apply'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'choices_json' => $schema->string()
                ->description('JSON { key: índice }. key sale de estado-copys-ia; el índice, desde 0. Sin este dato, queda la primera opción de cada cuadro.'),
        ];
    }
}
