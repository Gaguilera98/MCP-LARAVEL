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

#[Name('asignar-receta')]
#[Description(
    'Elige cómo se anima cada arte. Manda recipe o style, uno solo. '.
    'style elige la receta según el objetivo de cada arte: sobrio, dinamico o impacto. Es la opción más simple. '.
    'recipe pone la misma receta a todos los artes del alcance. '.
    'Para reconocimiento: revelado-sereno, escena-viva, golpe-de-marca. Para consideración: producto-protagonista, beneficio-en-foco, comparativo. '.
    'Para conversión: cta-claro, cta-con-pulso, urgencia. Para YouTube de 6 segundos: bumper-de-marca. '.
    'El filtro (review, platform, format, product) elige el grupo. piece_ids_json elige piezas concretas: los id de listar-piezas, hasta 500. '.
    'Las que no tienen capas vuelven en without_layers. Una pieza de otra campaña da 422 y no se toca nada. '.
    'Solo toca artes con capas (extraer-capas). No gasta. Si el arte ya tenía video, queda desactualizado: genera ese video de nuevo. '.
    'Responde updated: cuántos artes quedaron con la receta. Para ver qué hace cada una, listar-recetas.'
)]
class AsignarReceta extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->withFilter($request);

        foreach (['recipe', 'style'] as $key) {
            $value = $request->get($key);

            if (is_string($value) && $value !== '') {
                $body[$key] = $value;
            }
        }

        $seconds = $request->get('seconds');

        if ($seconds !== null && $seconds !== '') {
            $body['seconds'] = (int) $seconds;
        }

        $body = $this->withJsonFields($request, $body, [
            'piece_ids_json' => 'piece_ids',
        ]);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::post($this->campaignPath($request, 'animations/recipe'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'style' => $schema->string()
                ->enum(['sobrio', 'dinamico', 'impacto'])
                ->description('sobrio, dinamico o impacto. Elige la receta según el objetivo de cada arte. No lo combines con recipe.'),
            'recipe' => $schema->string()
                ->description('Una receta para todos: revelado-sereno, escena-viva, golpe-de-marca, producto-protagonista, beneficio-en-foco, comparativo, cta-claro, cta-con-pulso, urgencia o bumper-de-marca. No lo combines con style.'),
            'seconds' => $schema->integer()
                ->description('Duración del video: 6, 10 o 15 segundos. Si no lo indicas, o la receta no admite esa duración, son 6.'),
            'piece_ids_json' => $schema->string()
                ->description('Piezas concretas (id de listar-piezas), en JSON de hasta 500: [10,11]. Si no lo mandas, la receta va a las piezas del filtro.'),
            ...$this->filterArguments($schema),
        ];
    }
}
