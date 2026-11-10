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

#[Name('configurar-santo')]
#[Description(
    'Guarda la configuración de Santo, como «Guardar configuración» del modal de Santo. No genera ni gasta. '.
    'all vale para todas las filas; rows, para una fila (strategy_id e image_index de plan-produccion) y manda sobre all. Lo que no se manda queda como estaba. '.
    'La respuesta trae warnings, por ejemplo cuando el modelo no acepta una proporción y Santo la entrega en otra: muéstralos tal cual. '.
    'mode disenador_grafico (Diseñador) pide reference y prompt. director_de_arte (Director) pide talent_id y prompt. '.
    'En la práctica usa Diseñador: la lista de talentos no está disponible.'
)]
class ConfigurarSanto extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [];
        $baseRatio = $request->get('base_ratio');

        if (is_string($baseRatio) && trim($baseRatio) !== '') {
            $body['base_ratio'] = $baseRatio;
        }

        $body = $this->withJsonFields($request, $body, [
            'all_json' => 'all',
            'rows_json' => 'rows',
        ]);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::put($this->campaignPath($request, 'production/santo'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'base_ratio' => $schema->string()
                ->description('La columna base, por ejemplo 9:16. Sin él, queda la que está.'),
            'all_json' => $schema->string()
                ->description('JSON para todas las filas y ángulos: {"mode":"disenador_grafico","model":"google/nano-banana-2","prompt":"...","reference":"<path>"}. mode: disenador_grafico o director_de_arte. model: google/nano-banana-2.1, google/nano-banana-2, google/nano-banana-pro, openai/gpt-image-2.5-sunburst, openai/gpt-image-2.5-flare, bytedance/seedream-5-pro (solo director_de_arte), black-forest-labs/flux-2-pro (solo disenador_grafico) o black-forest-labs/flux-2-max. reference es el path de subir-referencia-santo. talent_id solo para director_de_arte.'),
            'rows_json' => $schema->string()
                ->description('JSON de filas que pisan all. Cada una lleva strategy_id e image_index de plan-produccion, más los campos que cambian: [{"strategy_id":1,"image_index":1,"prompt":"..."}].'),
        ];
    }
}
