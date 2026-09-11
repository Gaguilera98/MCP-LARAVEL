<?php

namespace App\Mcp\Tools\Mailing;

use App\Support\MailingApi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('listar-participantes')]
#[Description('Personas cargadas en una campaña, con sus datos extra. Podés filtrar por correo o buscar por nombre.')]
class ListarParticipantes extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $accountId = (int) $request->get('account_id');
        $campaignId = (int) $request->get('campaign_id');

        return MailingApi::get(
            'accounts/'.$accountId.'/campaigns/'.$campaignId.'/participants',
            [
                'email' => $request->get('email'),
                'q' => $request->get('q'),
                'page' => $request->get('page'),
                'per_page' => $request->get('per_page'),
            ]
        );
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'account_id' => $schema->integer()
                ->description('Cuenta sobre la que trabajás; se obtiene con listar-cuentas.')
                ->required(),
            'campaign_id' => $schema->integer()
                ->description('Campaña sobre la que actuás.')
                ->required(),
            'email' => $schema->string()
                ->description('Filtrar por correo, total o parcial.'),
            'q' => $schema->string()
                ->description('Búsqueda libre por correo, nombre o apellidos.'),
            'page' => $schema->integer()
                ->description('Número de página; empieza en 1.'),
            'per_page' => $schema->integer()
                ->description('Cuántos resultados por página; máximo 200.'),
        ];
    }
}
