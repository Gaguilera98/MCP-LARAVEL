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

#[Name('listar-envios')]
#[Description('Envíos de la cuenta con su estado. Podés filtrar por estado o por campaña.')]
class ListarEnvios extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $id = (int) $request->get('account_id');

        return MailingApi::get('accounts/'.$id.'/sends', [
            'status' => $request->get('status'),
            'campaign_id' => $request->get('campaign_id'),
            'page' => $request->get('page'),
            'per_page' => $request->get('per_page'),
        ]);
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
            'status' => $schema->string()
                ->description('Filtrar por estado: draft (borrador), queued (en cola), sending (enviando), paused (pausado), completed (terminado) o failed (con fallos).'),
            'campaign_id' => $schema->integer()
                ->description('Mostrar solo los envíos de esta campaña.'),
            'page' => $schema->integer()
                ->description('Número de página; empieza en 1.'),
            'per_page' => $schema->integer()
                ->description('Cuántos resultados por página; máximo 200.'),
        ];
    }
}
