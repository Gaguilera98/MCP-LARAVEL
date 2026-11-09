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

#[Name('consola-pendientes')]
#[Description(
    'Lo que espera a un agente en todos los pedidos en curso. steps: pasos que ya se pueden trabajar (lo anterior está cerrado; las plantillas van en paralelo con fotos y textos), con doer para saber qué agente lo toma. answers: respuestas de personas que el agente todavía no leyó. Los pedidos en pausa no aparecen. El paso del plugin nunca aparece: lo hace una persona.'
)]
class ConsolaPendientes extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $query = $this->consoleBody($request, [], []);

        if ($query instanceof Response || $query instanceof ResponseFactory) {
            return $query;
        }

        return HeraldoApi::consoleGet($this->consolePath('pending', $request), $query, $this->agentFromRequest($request));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
