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

#[Name('listar-campanas')]
#[Description(
    'Campañas de un cliente, de la más nueva a la más vieja. Cada una trae id (es el campaign_id), name, status y pieces_count. '.
    'Viene por páginas: meta.pagination dice la página actual y la última.'
)]
class ListarCampanas extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->tenantPath($request, 'campaigns'), [
            'page' => $request->get('page'),
            'per_page' => $request->get('per_page'),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->tenantArguments($schema),
            'page' => $schema->integer()
                ->description('Número de página. Empieza en 1; si no lo indicás, es la 1.'),
            'per_page' => $schema->integer()
                ->description('Cuántas campañas por página, de 1 a 100. Si no lo indicás, son 25.'),
        ];
    }
}
