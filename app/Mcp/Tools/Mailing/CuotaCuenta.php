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

#[Name('cuota-cuenta')]
#[Description('Cuota Brevo real de la cuenta (sendLimit vía GET api.brevo.com/v3/account).')]
class CuotaCuenta extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $id = (int) $request->get('account_id');

        return MailingApi::get('accounts/'.$id.'/quota');
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'account_id' => $schema->integer()
                ->description('ID de la cuenta Mailing.')
                ->required(),
        ];
    }
}
