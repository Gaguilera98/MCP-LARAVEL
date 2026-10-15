<?php

namespace App\Mcp\Tools\Lms;

use App\Support\LmsApi;
use App\Support\LmsPresent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('ver-estudiante')]
#[Description('Muestra la ficha de un estudiante, incluido el usuario que se le generó para entrar al aula.')]
class VerEstudiante extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        return LmsApi::get('students/'.(int) $request->get('estudiante'), present: [LmsPresent::class, 'estudiante']);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'estudiante' => $schema->integer()
                ->description('Número del estudiante. Lo obtenés con listar-estudiantes.')
                ->required(),
        ];
    }
}
