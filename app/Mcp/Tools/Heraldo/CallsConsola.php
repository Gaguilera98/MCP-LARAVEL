<?php

namespace App\Mcp\Tools\Heraldo;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

trait CallsConsola
{
    protected function agentFromRequest(Request $request): ?string
    {
        $agent = $request->get('agente');

        if (! is_string($agent) || trim($agent) === '') {
            return null;
        }

        return trim($agent);
    }

    /**
     * @return array<string, JsonSchema>
     */
    protected function agentArgument(JsonSchema $schema): array
    {
        return [
            'agente' => $schema->string()
                ->description('Quién actúa, como lo verá la persona en la bitácora. Por ejemplo «Agente de diseño». Si no viene, queda el nombre de la llave.'),
        ];
    }

    protected function consolePath(string $template, Request $request): string
    {
        return (string) preg_replace_callback('/\{([a-z_]+)\}/', function (array $matches) use ($request): string {
            $value = $request->get($matches[1]);

            return rawurlencode(trim((string) $value));
        }, $template);
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, string>  $jsonFields
     * @return array<string, mixed>|Response|ResponseFactory
     */
    protected function consoleBody(Request $request, array $keys = [], array $jsonFields = []): array|Response|ResponseFactory
    {
        $body = [];

        foreach ($keys as $key) {
            if ($key === 'agente' || ! $this->hasArgument($request, $key)) {
                continue;
            }

            $value = $request->get($key);

            if ($value === null || $value === '') {
                continue;
            }

            $body[$key] = $value;
        }

        foreach (['order_id', 'brand_id', 'campaign_id', 'question_id', 'file_id', 'page', 'per_page', 'base_images', 'adaptations', 'copy_texts', 'pieces', 'videos', 'input_tokens', 'output_tokens'] as $intKey) {
            if (array_key_exists($intKey, $body) && is_numeric($body[$intKey])) {
                $body[$intKey] = (int) $body[$intKey];
            }
        }

        foreach (['usd'] as $floatKey) {
            if (array_key_exists($floatKey, $body) && is_numeric($body[$floatKey])) {
                $body[$floatKey] = (float) $body[$floatKey];
            }
        }

        foreach (['refresh', 'allows_text'] as $boolKey) {
            if (array_key_exists($boolKey, $body)) {
                $parsed = $this->optionalBool($request, $boolKey);

                if ($parsed !== null) {
                    $body[$boolKey] = $parsed;
                } else {
                    unset($body[$boolKey]);
                }
            }
        }

        return $this->withJsonFields($request, $body, $jsonFields);
    }
}
