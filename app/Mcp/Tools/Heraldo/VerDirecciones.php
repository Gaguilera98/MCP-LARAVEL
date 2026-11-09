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

#[Name('ver-direcciones')]
#[Description(
    'Hacia dónde crece la foto en las adaptaciones de cada columna (el menú del encabezado de la columna en el paso 2). Por columna que no es la base: axis (vertical si crece el alto, horizontal si crece el ancho, none si tiene la misma forma), direction (top, bottom, left o right; null es centrada) y choices (los lados que tienen efecto en esa columna). Solo lee.'
)]
class VerDirecciones extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->campaignPath($request, 'production/adaptation-directions'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->campaignArguments($schema);
    }
}
