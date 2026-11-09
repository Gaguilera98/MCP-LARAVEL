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

#[Name('ver-campana')]
#[Description(
    'Lo que se ajusta en el Constructor de la campaña: concepto (description), intención, cuadros de texto (largo y si los escribe la IA) y, por formato, sus marcos, la capa de foto, qué foto usa (photo_source) y en qué proporción la entrega Santo (santo_ratio), con las opciones de cada uno y los avisos del paso 4. '.
    'Úsala antes de configurar-campana y de configurar-santo. Solo lee.'
)]
class VerCampana extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->campaignPath($request, 'setup'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->campaignArguments($schema);
    }
}
