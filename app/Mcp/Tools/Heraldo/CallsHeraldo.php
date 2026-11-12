<?php

namespace App\Mcp\Tools\Heraldo;

use App\Support\HeraldoApi;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

trait CallsHeraldo
{
    /**
     * @return array<string, JsonSchema>
     */
    protected function tenantArguments(JsonSchema $schema): array
    {
        return [
            'tenant_id' => $schema->string()
                ->description('id del cliente, de listar-tenants.')
                ->required(),
        ];
    }

    /**
     * @return array<string, JsonSchema>
     */
    protected function campaignArguments(JsonSchema $schema): array
    {
        return [
            ...$this->tenantArguments($schema),
            'campaign_id' => $schema->integer()
                ->description('id de la campaña, de listar-campanas o de crear-campana.')
                ->required(),
        ];
    }

    /**
     * @return array<string, JsonSchema>
     */
    protected function filterArguments(JsonSchema $schema): array
    {
        return [
            'review' => $schema->string()
                ->enum(['all', 'in_review', 'approved', 'rejected', 'reviewed', 'ai_flagged'])
                ->description('Qué artes entran según su revisión: all (todos), in_review, approved, rejected, reviewed (aprobados y rechazados) o ai_flagged (los que marcó la IA y siguen en revisión: mirar primero). Si no lo indicas, entran todos; en pedir-exportacion, solo approved.'),
            'platform' => $schema->string()
                ->description('Solo los artes de esa plataforma. Escribe el nombre tal como viene en platform de listar-piezas.'),
            'format' => $schema->string()
                ->description('Solo los artes de ese formato. Escribe el nombre tal como viene en format de listar-piezas.'),
            'product' => $schema->string()
                ->description('Solo los artes de ese producto. Escribe el nombre tal como viene en product de listar-piezas.'),
        ];
    }

    protected function tenantPath(Request $request, string $suffix = ''): string
    {
        $path = 'tenants/'.rawurlencode(trim((string) $request->get('tenant_id')));

        if ($suffix !== '') {
            $path .= '/'.ltrim($suffix, '/');
        }

        return $path;
    }

    protected function campaignPath(Request $request, string $suffix = ''): string
    {
        $path = 'campaigns/'.rawurlencode(trim((string) $request->get('campaign_id')));

        if ($suffix !== '') {
            $path .= '/'.ltrim($suffix, '/');
        }

        return $this->tenantPath($request, $path);
    }

    /**
     * @return array<string, string>
     */
    protected function filterPayload(Request $request): array
    {
        $filter = [];
        $review = $request->get('review');

        if (is_string($review) && trim($review) !== '') {
            $filter['review'] = trim($review);
        }

        foreach (['platform', 'format', 'product'] as $key) {
            $value = $request->get($key);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $value = trim($value);
            $filter[$key] = str_starts_with($value, 'id:') || str_starts_with($value, 'name:')
                ? $value
                : 'name:'.$value;
        }

        return $filter;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function withFilter(Request $request, array $body = []): array
    {
        $filter = $this->filterPayload($request);

        if ($filter !== []) {
            $body['filter'] = $filter;
        }

        return $body;
    }

    protected function hasArgument(Request $request, string $key): bool
    {
        return array_key_exists($key, $request->all());
    }

    protected function optionalNumber(Request $request, string $key): ?float
    {
        $value = $request->get($key);

        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    protected function optionalBool(Request $request, string $key): ?bool
    {
        $value = $request->get($key);

        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $jsonFields
     * @return array<string, mixed>|Response|ResponseFactory
     */
    protected function withJsonFields(Request $request, array $body, array $jsonFields): array|Response|ResponseFactory
    {
        foreach ($jsonFields as $arg => $key) {
            $decoded = HeraldoApi::decodeOptionalJsonArg($request->get($arg), $arg);

            if ($decoded instanceof Response || $decoded instanceof ResponseFactory) {
                return $decoded;
            }

            if ($decoded !== null) {
                $body[$key] = $decoded;
            }
        }

        return $body;
    }
}
