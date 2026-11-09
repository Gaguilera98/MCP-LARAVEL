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

#[Name('generar-videos')]
#[Description(
    'Genera los videos MP4 de los artes. Cada arte necesita capas y receta (extraer-capas, asignar-receta). '.
    'El filtro (review, platform, format, product) genera los que faltan. '.
    'piece_ids_json genera el video de esas piezas (id de listar-piezas, hasta 500), listas o pendientes. Las que no se pueden animar vuelven en skipped. '.
    'Gasta dinero. Heraldo exige max_usd: si no lo mandás o lo estimado lo supera, no hace nada y el error trae estimate_usd. '.
    'Usá ese estimado para confirmar con la persona y volvé a llamarla con su tope. '.
    'Si son demasiados artes para una tanda, Heraldo lo dice: dividilos con el filtro o con piece_ids_json. '.
    'Queda en cola: seguí in_progress.videos y animations en estado-produccion.'
)]
class GenerarVideos extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->withFilter($request);
        $maxUsd = $this->optionalNumber($request, 'max_usd');

        if ($maxUsd !== null) {
            $body['max_usd'] = $maxUsd;
        }

        $body = $this->withJsonFields($request, $body, [
            'piece_ids_json' => 'piece_ids',
        ]);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::post($this->campaignPath($request, 'animations/videos'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'max_usd' => $schema->number()
                ->description('Tope de gasto en dólares que aprobó la persona. Sin este dato no se hace nada.'),
            'piece_ids_json' => $schema->string()
                ->description('Piezas concretas (id de listar-piezas), en JSON de hasta 500: [10,11]. Genera el video de cada una. Si no lo mandás, se generan los pendientes del filtro.'),
            ...$this->filterArguments($schema),
        ];
    }
}
