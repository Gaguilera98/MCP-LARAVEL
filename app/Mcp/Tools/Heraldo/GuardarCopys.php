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

#[Name('guardar-copys')]
#[Description(
    'Escribe textos en los cuadros de copy de la campaña. Puedes mandar solo algunos: los que no mandas quedan como estaban. '.
    'Un texto vacío se ignora. Si alguna clave no es de esta campaña, no se guarda nada y el error dice cuáles. '.
    'Respeta max_length de cada cuadro: los que se pasan se guardan igual y vuelven en over_max_length. '.
    'Un cuadro que recibe texto escrito aquí deja de generarse con IA.'
)]
class GuardarCopys extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $texts = HeraldoApi::decodeJsonArg($request->get('texts_json'), 'texts_json');

        if ($texts instanceof Response || $texts instanceof ResponseFactory) {
            return $texts;
        }

        return HeraldoApi::put($this->campaignPath($request, 'copies'), [
            'texts' => $texts,
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'texts_json' => $schema->string()
                ->description('Objeto JSON: la key de cada cuadro (de listar-copys) con su texto. Ejemplo: {"<key del cuadro>":"Texto del copy"}.')
                ->required(),
        ];
    }
}
