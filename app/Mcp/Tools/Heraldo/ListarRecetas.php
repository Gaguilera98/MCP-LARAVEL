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

#[Name('listar-recetas')]
#[Description(
    'Las recetas de animación y qué hace cada una: key, nombre, objetivo (reconocimiento, consideración, conversión o bumper), estilo (sobrio, dinamico o impacto), descripción, duraciones (seconds y default_seconds), example_video_url y example_image_url (mirala con ver-imagen). '.
    'styles trae sobrio, dinamico e impacto. by_objective dice qué receta toma cada pieza al usar style en asignar-receta. Llamala antes de asignar-receta. Solo lee.'
)]
class ListarRecetas extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        return HeraldoApi::get($this->tenantPath($request, 'animation-recipes'));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->tenantArguments($schema);
    }
}
