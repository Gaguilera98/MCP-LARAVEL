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

#[Name('hoja-fotos')]
#[Description(
    'Una sola imagen con hasta 24 fotos de una columna del plan, numeradas desde 1 en cada página. tiles dice qué fila es cada número (strategy_id, image_index, producto, variación y ángulo). '.
    'Sirve para revisar las fotos antes de adaptar o de pasar a Figma. Sin ratio, la columna base. Para ver una de cerca, ver-imagen con el preview_url de plan-produccion. Solo lee.'
)]
class HojaFotos extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $query = [];
        $ratio = $request->get('ratio');
        $page = $request->get('page');

        if (is_string($ratio) && trim($ratio) !== '') {
            $query['ratio'] = $ratio;
        }

        if ($page !== null && $page !== '') {
            $query['page'] = (int) $page;
        }

        $json = HeraldoApi::getJson($this->campaignPath($request, 'production/plan/contact-sheet'), $query);

        if ($json instanceof Response) {
            return $json;
        }

        $url = $json['data']['download_url'] ?? null;

        return HeraldoApi::jsonWithSignedImage($json, is_string($url) ? $url : null);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'ratio' => $schema->string()
                ->description('La columna, como en columns de plan-produccion. Sin él, la base.'),
            'page' => $schema->integer()
                ->description('La página, desde 1, de a 24 fotos.'),
        ];
    }
}
