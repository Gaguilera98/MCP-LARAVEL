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

#[Name('revisar-piezas')]
#[Description(
    'Aprueba o rechaza artes de la campaña. Cada cambio queda en el historial del arte. '.
    'Para rechazar hace falta note: qué hay que cambiar. '.
    'Con slot_ids_json cambia solo esos artes. Sin slot_ids_json cambia todos los que están en revisión, o todos los que tienen imagen si only_in_review es false. '.
    'Confirmá con la persona antes de cambiar varios a la vez. Responde updated (cuántos cambió) y pieces (el nuevo resumen).'
)]
class RevisarPiezas extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [
            'status' => $request->get('status'),
        ];

        $note = $request->get('note');

        if (is_string($note) && $note !== '') {
            $body['note'] = $note;
        }

        $onlyInReview = $this->optionalBool($request, 'only_in_review');

        if ($onlyInReview !== null) {
            $body['only_in_review'] = $onlyInReview;
        }

        $body = $this->withJsonFields($request, $body, [
            'slot_ids_json' => 'slot_ids',
        ]);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::post($this->campaignPath($request, 'pieces/review'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'status' => $schema->string()
                ->description('approved (aprobar), rejected (rechazar) o in_review (volver a revisión).')
                ->required(),
            'note' => $schema->string()
                ->description('Comentario de la revisión. Obligatorio para rechazar: qué hay que cambiar.'),
            'slot_ids_json' => $schema->string()
                ->description('Los id de los artes, de listar-piezas, en JSON: [10,11]. Si no lo indicás, aplica a varios según only_in_review.'),
            'only_in_review' => $schema->boolean()
                ->description('Sin slot_ids_json: true cambia solo los que están en revisión; false, todos los que tienen imagen. Si no lo indicás, es true.'),
        ];
    }
}
