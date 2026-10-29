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

#[Name('listar-exportaciones')]
#[Description(
    'El último ZIP de piezas (pieces) y el último de videos (videos) que pediste en esta campaña, con su estado. Los que pidió otra persona no aparecen. '.
    'Cuando está listo trae download_url: pasaselo a la persona; vence a las 24 horas y sirve para una sola descarga. '.
    'Si sigue en cola o procesando, volvé a consultar en un rato. Si viene vacío, no hay ninguno pedido: usá pedir-exportacion.'
)]
class ListarExportaciones extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->campaignPath($request, 'exports'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->campaignArguments($schema);
    }
}
