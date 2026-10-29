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

#[Name('generar-titulares')]
#[Description(
    'Escribe con IA los textos de publicación de cada arte: el texto que acompaña al post. Hace falta tener artes importados de Figma. '.
    'concept es obligatorio la primera vez; después queda guardado y podés omitirlo. '.
    'Gasta dinero. Heraldo exige max_usd: si no lo mandás o lo estimado lo supera, no hace nada y el error trae estimate_usd. '.
    'Usá ese estimado para confirmar con la persona y volvé a llamarla con su tope. '.
    'Queda en cola: seguí headlines en estado-produccion. Cuando termine, excel-titulares da el enlace del Excel.'
)]
class GenerarTitulares extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [];

        foreach (['concept', 'notes'] as $key) {
            $value = $request->get($key);

            if (is_string($value) && $value !== '') {
                $body[$key] = $value;
            }
        }

        $options = $request->get('options');

        if ($options !== null && $options !== '') {
            $body['options'] = (int) $options;
        }

        $onlyMissing = $this->optionalBool($request, 'only_missing');

        if ($onlyMissing !== null) {
            $body['only_missing'] = $onlyMissing;
        }

        $maxUsd = $this->optionalNumber($request, 'max_usd');

        if ($maxUsd !== null) {
            $body['max_usd'] = $maxUsd;
        }

        return HeraldoApi::post($this->campaignPath($request, 'headlines'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'concept' => $schema->string()
                ->description('El concepto de la campaña: la idea central que tienen que transmitir los textos.'),
            'notes' => $schema->string()
                ->description('Indicaciones extra para la IA: tono, palabras a evitar, etc.'),
            'options' => $schema->integer()
                ->description('Cuántas opciones de texto por arte, de 1 a 5. Si no lo indicás, son 3.'),
            'only_missing' => $schema->boolean()
                ->description('true escribe solo los textos que faltan y no toca los que ya están.'),
            'max_usd' => $schema->number()
                ->description('Tope de gasto en dólares que aprobó la persona. Sin este dato no se hace nada.'),
        ];
    }
}
