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

#[Name('excel-titulares')]
#[Description(
    'Enlace para bajar el Excel con los textos de publicación de la campaña. No devuelve el archivo: devuelve download_url, filename y expires_at. '.
    'Pásale el enlace a la persona: vence a las 24 horas y sirve para una sola descarga. Si hace falta otra, llámala de nuevo. '.
    'Si todavía no hay textos, responde que no hay textos de publicación: primero generar-titulares y espera a que termine.'
)]
class ExcelTitulares extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->campaignPath($request, 'headlines/excel'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->campaignArguments($schema);
    }
}
