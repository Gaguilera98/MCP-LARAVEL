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
    'Elige cómo se anima cada arte. Mandá recipe o style, uno solo. '.
    'style elige la receta según el objetivo de cada arte: sobrio, dinamico o impacto. Es la opción más simple. '.
    'recipe pone la misma receta a todos los artes del filtro. '.
    'Para reconocimiento: revelado-sereno, escena-viva, golpe-de-marca. Para consideración: producto-protagonista, beneficio-en-foco, comparativo. '.
    'Para conversión: cta-claro, cta-con-pulso, urgencia. Para YouTube de 6 segundos: bumper-de-marca. '.
    'Solo toca artes con capas (extraer-capas). No gasta, pero si un arte ya tenía video, queda desactualizado y hay que generarlo de nuevo. '.
    'Responde updated: cuántos artes cambió.'
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
                ->description('sobrio, dinamico o impacto. Elige la receta según el objetivo de cada arte. No lo combines con recipe.'),
            'recipe' => $schema->string()
                ->description('Una receta para todos: revelado-sereno, escena-viva, golpe-de-marca, producto-protagonista, beneficio-en-foco, comparativo, cta-claro, cta-con-pulso, urgencia o bumper-de-marca. No lo combines con style.'),
            'seconds' => $schema->integer()
                ->description('Duración del video: 6, 10 o 15 segundos. Si no lo indicás, o la receta no admite esa duración, son 6.'),
            ...$this->filterArguments($schema),
        ];
    }
}
