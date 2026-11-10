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

#[Name('listar-presets')]
#[Description(
    'Presets publicados del cliente: las plantillas para crear una campaña. Llámalo antes de crear-campana. '.
    'Cada preset trae id (es el preset_id), messages (los nombres de mensaje que se pueden marcar), formats (id y name de cada formato), '.
    'copy_slots (los cuadros de texto) y sheet_template: el CSV de ejemplo con las columnas que tiene que llevar la hoja de productos.'
)]
class ListarPresets extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->tenantPath($request, 'presets'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->tenantArguments($schema);
    }
}
