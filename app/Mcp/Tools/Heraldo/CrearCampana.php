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

#[Name('crear-campana')]
#[Description(
    'Crea una campaña a partir de un preset y una hoja de productos en CSV. '.
    'Arma la hoja desde sheet_template del preset (listar-presets): mismas columnas, una fila por producto. '.
    'Llámala primero con dry_run true: revisa la hoja y no crea nada. La respuesta trae preview con products, errors, warnings, missing_columns, unknown_columns, rows y pieces. '.
    'Si hay errores, corrige la hoja y vuelve a probar. Si está bien, muéstrale el preview a la persona y, con su visto bueno, llámala con dry_run false. '.
    'Al crearla responde created true y campaign.id: ese es el campaign_id nuevo. Si ya hay otra campaña con el mismo nombre, viene como warning, no como error.'
)]
class CrearCampana extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [
            'preset_id' => (int) $request->get('preset_id'),
            'name' => $request->get('name'),
            'sheet' => $request->get('sheet'),
        ];

        $dryRun = $this->optionalBool($request, 'dry_run');

        if ($dryRun !== null) {
            $body['dry_run'] = $dryRun;
        }

        $body = $this->withJsonFields($request, $body, [
            'messages_json' => 'messages',
            'format_ids_json' => 'format_ids',
        ]);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::post($this->tenantPath($request, 'campaigns'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->tenantArguments($schema),
            'preset_id' => $schema->integer()
                ->description('id del preset, de listar-presets.')
                ->required(),
            'name' => $schema->string()
                ->max(255)
                ->description('Nombre de la campaña.')
                ->required(),
            'sheet' => $schema->string()
                ->description('La hoja de productos completa, en texto CSV, con las columnas de sheet_template del preset.')
                ->required(),
            'messages_json' => $schema->string()
                ->description('Qué mensajes del preset usar, en JSON con los nombres de messages de listar-presets: ["Post","Story"]. Si no lo indicas, van todos.'),
            'format_ids_json' => $schema->string()
                ->description('Qué formatos del preset usar, en JSON con los id de formats de listar-presets: [1,2]. Si no lo indicas, van todos.'),
            'dry_run' => $schema->boolean()
                ->description('true revisa la hoja y no crea nada. false crea la campaña. Si no lo indicas, la crea: manda true la primera vez.'),
        ];
    }
}
