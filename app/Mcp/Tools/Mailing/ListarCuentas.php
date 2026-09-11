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

#[Name('listar-cuentas')]
#[Description(
    'Lista las cuentas disponibles. Empezá siempre por acá: el account_id que devuelve se usa en todas las demás herramientas. '.
    'Indica también si la cuenta está lista para enviar correo.'
)]
class ListarCuentas extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        return MailingApi::get('accounts');
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}