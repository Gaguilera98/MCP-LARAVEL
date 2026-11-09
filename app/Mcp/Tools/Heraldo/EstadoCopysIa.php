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

#[Name('estado-copys-ia')]
#[Description(
    'En qué va la generación de copys con IA y las opciones de cada cuadro. '.
    'status queued o processing: todavía trabaja, volvé a consultar. '.
    'completed: las opciones están listas. '.
    'failed: el error dice por qué. '.
    'vacío (null): no hay generación en curso; no esperes. Si hay options, son de una generación anterior. '.
    'Cada texto trae key (para elegir-copys), producto, ángulo, mensaje, el cuadro (slot), max_length, options (desde 0), selected y text (lo que ya está guardado). Solo lee.'
)]
class EstadoCopysIa extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->campaignPath($request, 'copies/generate'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->campaignArguments($schema);
    }
}
