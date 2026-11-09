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

#[Name('traer-fotos')]
#[Description(
    'Trae a esta campaña las fotos de otra campaña del mismo cliente, sin gastar. Cada fila toma la foto de la fila con el mismo producto, variación, ángulo y número de foto. '.
    'Llámala primero con dry_run en true para ver qué se trae (matched, unmatched, to_copy). Sin replace, las casillas que ya tienen foto no se tocan. '.
    'Las proporciones se escriben como en columns de plan-produccion de cada campaña.'
)]
class TraerFotos extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [
            'source_campaign_id' => (int) $request->get('source_campaign_id'),
            'source_ratio' => $request->get('source_ratio'),
            'target_ratio' => $request->get('target_ratio'),
        ];

        foreach (['replace', 'dry_run'] as $key) {
            $value = $this->optionalBool($request, $key);

            if ($value !== null) {
                $body[$key] = $value;
            }
        }

        return HeraldoApi::post($this->campaignPath($request, 'production/photos/copy-from'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'source_campaign_id' => $schema->integer()
                ->description('La campaña de donde salen las fotos, del mismo cliente.')
                ->required(),
            'source_ratio' => $schema->string()
                ->description('La columna de origen, como en plan-produccion de esa campaña, por ejemplo 9:16.')
                ->required(),
            'target_ratio' => $schema->string()
                ->description('La columna de destino en esta campaña.')
                ->required(),
            'replace' => $schema->boolean()
                ->description('true pisa las casillas que ya tienen foto. Por defecto, false.'),
            'dry_run' => $schema->boolean()
                ->description('true solo muestra qué haría, sin copiar. Por defecto, false.'),
        ];
    }
}
