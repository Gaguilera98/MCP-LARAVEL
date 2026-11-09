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

#[Name('consola-ver-archivo')]
#[Description(
    'Un archivo del pedido y su enlace firmado (url, vence a las 24 h). Si es una imagen, el MCP abre el enlace y devuelve la imagen como contenido de imagen, para que el agente la vea.'
)]
class ConsolaVerArchivo extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $json = HeraldoApi::consoleGetJson($this->consolePath('files/{file_id}', $request));

        if ($json instanceof Response) {
            return $json;
        }

        $data = is_array($json['data'] ?? null) ? $json['data'] : $json;
        $mime = strtolower((string) ($data['mime'] ?? ''));
        $url = $data['url'] ?? null;
        $encoded = (string) json_encode($json, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $parts = [Response::text($encoded)];

        if (is_string($url) && $url !== '' && str_starts_with($mime, 'image/') && ! str_contains($mime, 'svg')) {
            $image = HeraldoApi::fetchConsoleImage($url);
            $parts[] = is_string($image)
                ? Response::text($image)
                : Response::image($image['bytes'], $image['mime']);
        }

        return Response::make($parts)->withStructuredContent($json);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'file_id' => $schema->integer()
                ->description('id del archivo, de consola-ver-pedido (files).')
                ->required(),
        ];
    }
}
