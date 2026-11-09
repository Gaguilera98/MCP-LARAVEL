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

#[Name('ver-imagen')]
#[Description(
    'Muestra una foto o un arte de cerca. Recibe un preview_url de plan-produccion o de listar-piezas y devuelve la imagen. '.
    'El enlace ya viene armado, con el subdominio de ese cliente, y vence a las 24 h: si venció, vuelve a pedir plan-produccion o listar-piezas. Solo lee.'
)]
class VerImagen extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $url = $request->get('preview_url');

        if (! is_string($url) || trim($url) === '') {
            return Response::error('Indicá preview_url, el de una casilla de plan-produccion o de un arte de listar-piezas.');
        }

        $image = HeraldoApi::fetchSignedImage($url);

        if (is_string($image)) {
            return Response::error($image);
        }

        return Response::image($image['bytes'], $image['mime']);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'preview_url' => $schema->string()
                ->description('El preview_url de una casilla de plan-produccion o de un arte de listar-piezas.')
                ->required(),
        ];
    }
}
