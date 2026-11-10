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

#[Name('listar-copys')]
#[Description(
    'Los cuadros de texto de la campaña (copys): uno por producto × ángulo × mensaje × cuadro. '.
    'Cada uno trae key, product, variation, angle, message, slot_name, max_length y text (lo que ya está escrito; vacío si falta). '.
    'completion dice cuántos faltan. La key es la que usas en guardar-copys.'
)]
class ListarCopys extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->campaignPath($request, 'copies'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->campaignArguments($schema);
    }
}
