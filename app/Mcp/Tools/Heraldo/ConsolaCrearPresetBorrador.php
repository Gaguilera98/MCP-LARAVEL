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

#[Name('consola-crear-preset-borrador')]
#[Description(
    'Cuando falta un preset para armar las campañas, deja uno nuevo en borrador, con las mismas reglas de la pantalla de presets. Nunca publica: lo publica una persona en Heraldo, y hasta entonces no sirve para crear campañas. Con order_id queda anotado en la bitácora de ese pedido; después pregúntale a la persona con consola-preguntar (kind task) que lo revise y lo publique. Revisa antes con consola-listar-presets: una clave que ya existe crea la versión siguiente.'
)]
class ConsolaCrearPresetBorrador extends Tool
{
    use CallsConsola;
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = $this->consoleBody($request, [0 => 'name',  1 => 'key',  2 => 'recipe_key',  3 => 'order_id'], ['payload_json' => 'payload']);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::consolePost($this->consolePath('tenants/{tenant_id}/presets', $request), $body, $this->agentFromRequest($request));
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'tenant_id' => $schema->string()
                ->description('id del cliente, de listar-tenants.')
                ->required(),
            'name' => $schema->string()
                ->description('Por ejemplo «Meta · Tasa».')
                ->required(),
            'key' => $schema->string()
                ->description('Clave estable: letras, números, guion y guion bajo. Por ejemplo meta-tasa.')
                ->required(),
            'recipe_key' => $schema->string()
                ->description('La receta, por ejemplo productos_promociones. Una que no existe responde 422 con las válidas.')
                ->required(),
            'payload_json' => $schema->string()
                ->description('versions: [{name, type: variante|idioma}], al menos uno. copy_slots: [{name, key copy_..., max_length, generate_with_ai}], al menos uno; sin key se arma desde el nombre. format_ids: ids de formatos del cliente. channel_defaults: {platform_id, objective_id}. field_layer_map y figma, opcionales.')
                ->required(),
            'order_id' => $schema->integer()
                ->description('El pedido para el que falta el preset.'),
            ...$this->agentArgument($schema),
        ];
    }
}
