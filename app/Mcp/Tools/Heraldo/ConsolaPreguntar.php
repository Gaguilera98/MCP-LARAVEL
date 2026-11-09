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

#[Name('consola-preguntar')]
#[Description(
    'Le pide algo a una persona y le avisa por correo. kind: approval (aprobar o pedir cambios; sin options trae Aprobar y Pedir cambios, y pedir cambios exige que la persona escriba qué), decision (elegir entre options, hasta 8), task (algo que la persona hace, como correr el plugin; no lleva opciones y se cierra con «Listo»), data (un dato que falta). En decision, marca con recommended la opción que propones: sale con «propuesto por el agente», pero la persona la elige; también puede escribir otra respuesta (allows_text). Una aprobación siempre deja escribir. file_ids muestra imágenes o archivos del pedido junto a la pregunta (súbelos antes con consola-subir-archivo). guidelines (solo en approval, hasta 3): aprendizajes que propones como lineamientos de la marca, por ejemplo al entregar, a partir de consola-senales; la persona marca cuáles quedan y solo esos se guardan en la ficha. Mientras esté abierta, el paso queda en «Te necesita» y no aparece en consola-pendientes. La respuesta llega en consola-pendientes (answers).'
)]
class ConsolaPreguntar extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->consoleBody($request, [0 => 'step',  1 => 'kind',  2 => 'title',  3 => 'body',  4 => 'allows_text'], ['options_json' => 'options',  'file_ids_json' => 'file_ids',  'guidelines_json' => 'guidelines']);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::consolePost($this->consolePath('orders/{order_id}/questions', $request), $body, $this->agentFromRequest($request));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()
                ->description('id del pedido, de consola-pendientes, consola-listar-pedidos o consola-crear-pedido.')
                ->required(),
            'step' => $schema->string()
                ->description('El paso: order (1 Pedido), campaigns (2 Campañas), design (3 Diseño madre), content (4 Fotos y textos), templates (5 Plantillas), plugin (6 Plugin), review (7 Revisión), delivery (8 Entrega).')
                ->required(),
            'kind' => $schema->string()
                ->description('Por defecto, decision.'),
            'title' => $schema->string()
                ->description('La pregunta, corta. Hasta 250 caracteres.')
                ->required(),
            'body' => $schema->string()
                ->description('El contexto que necesita la persona para responder.'),
            'options_json' => $schema->string()
                ->description('options'),
            'allows_text' => $schema->boolean()
                ->description('Si la persona puede escribir además de elegir. Por defecto, true.'),
            'file_ids_json' => $schema->string()
                ->description('file_ids'),
            'guidelines_json' => $schema->string()
                ->description('Solo en approval. Aprendizajes que propones como lineamientos de la marca: cortos (hasta 500 caracteres), concretos y que cambien el próximo pedido. La persona marca cuáles quedan.'),
            ...$this->agentArgument($schema),
        ];
    }
}
