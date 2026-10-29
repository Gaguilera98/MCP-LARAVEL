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

#[Name('usar-columna-base')]
#[Description(
    'Marca una columna de la grilla como base: desde sus fotos, pedir-adaptaciones completa las demás proporciones. '.
    'Todas las filas de esa columna tienen que tener foto. No gasta.'
)]
class UsarColumnaBase extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::post($this->campaignPath($request, 'production/base-column'), [
            'ratio' => $request->get('ratio'),
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'ratio' => $schema->string()
                ->description('La proporción de la columna, escrita igual que en columns de plan-produccion, por ejemplo 1:1.')
                ->required(),
        ];
    }
}
