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

#[Name('hoja-piezas')]
#[Description(
    'Una sola imagen con hasta 24 artes importados de Figma, numerados desde 1 en cada página, en el mismo orden que listar-piezas. tiles dice qué arte es cada número, con su id para revisar-piezas. '.
    'Acepta los mismos filtros que listar-piezas. 96 artes se revisan en 4 páginas. Solo lee.'
)]
class HojaPiezas extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $query = $this->withFilter($request);
        $page = $request->get('page');

        if ($page !== null && $page !== '') {
            $query['page'] = (int) $page;
        }

        $json = HeraldoApi::getJson($this->campaignPath($request, 'pieces/contact-sheet'), $query);

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
            ...$this->filterArguments($schema),
            'page' => $schema->integer()
                ->description('La página, desde 1, de a 24 artes.'),
        ];
    }
}
