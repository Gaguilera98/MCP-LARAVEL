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

#[Name('generar-copys')]
#[Description(
    'La IA escribe varias opciones para cada cuadro de texto marcado con IA (se marcan con configurar-campana). Gasta muy poco y exige max_usd; sin él responde 422 con estimate_usd. '.
    'concept es el concepto de la campaña: si no viene, usa el guardado, y si no hay ninguno responde 422. notes son las reglas extra: trato, largos, lo que no va. '.
    'Si mandas concept o notes, quedan guardados como en «Configurar concepto». Responde 202 y sigue sola: consulta estado-copys-ia mientras status sea queued o processing; con completed, usa elegir-copys.'
)]
class GenerarCopys extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [];
        $maxUsd = $this->optionalNumber($request, 'max_usd');

        if ($maxUsd !== null) {
            $body['max_usd'] = $maxUsd;
        }

        foreach (['concept', 'notes'] as $key) {
            $value = $request->get($key);

            if (is_string($value) && $value !== '') {
                $body[$key] = $value;
            }
        }

        return HeraldoApi::post($this->campaignPath($request, 'copies/generate'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'max_usd' => $schema->number()
                ->description('Tope de gasto en dólares. Sin este dato no se hace nada y el error trae el estimado.'),
            'concept' => $schema->string()
                ->description('El concepto de la campaña. Hasta 20.000 caracteres. Si no viene, usa el guardado.'),
            'notes' => $schema->string()
                ->description('Notas adicionales: trato por región, largos, reglas. Hasta 2.000 caracteres.'),
        ];
    }
}
