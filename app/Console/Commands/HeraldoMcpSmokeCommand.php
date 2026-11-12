<?php

namespace App\Console\Commands;

use App\Mcp\Servers\Heraldo;
use App\Mcp\Tools\Heraldo\ConsolaMoverPaso;
use App\Mcp\Tools\Heraldo\PedirAdaptaciones;
use App\Support\HeraldoApi;
use Illuminate\Console\Command;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use ReflectionClass;
use Throwable;

class HeraldoMcpSmokeCommand extends Command
{
    protected $signature = 'heraldo:mcp-smoke
                            {--live : Llama a Heraldo (solo lectura) con los datos fijos de god}
                            {--save-shapes : Guarda la forma de las respuestas live para la próxima comparación}';

    protected $description = 'Prueba automática del MCP de Heraldo: esquemas, tú y, con --live, lecturas en god';

    private const string TENANT = 'b750cf20-cea4-4a2d-96fc-89fe128ac62d';

    private const int CAMPAIGN_PLAN = 10;

    private const int CAMPAIGN_PIECES = 18;

    private const int BRAND = 1;

    private const int ORDER = 4;

    private const string VOSEO = '/(?<!\p{L})(volvé|usá|usás|mandá|mandás|indicás|escribí|seguí|llamal[ao]|armá|corregí|mostrale|pasale|pasaselo|esperá|podés|respetá|dividilos|empezá|mirala|confirmá|preferí|generá|acá)(?!\p{L})/iu';

    /** @var list<string> */
    private array $failures = [];

    public function handle(): int
    {
        $this->checkToolCatalog();
        $this->checkVoseo();
        $this->checkEnums();
        $this->checkPhrases();

        if ($this->option('live')) {
            $this->checkLiveReads();
        } else {
            $this->line('Sin --live: solo revisó catálogo, tú, enums y frases.');
        }

        if ($this->failures === []) {
            $this->info('OK: smoke del MCP de Heraldo.');

            return self::SUCCESS;
        }

        $this->error(count($this->failures).' fallo(s):');
        foreach ($this->failures as $failure) {
            $this->line(' - '.$failure);
        }

        return self::FAILURE;
    }

    private function checkToolCatalog(): void
    {
        $server = new ReflectionClass(Heraldo::class);
        /** @var list<class-string> $tools */
        $tools = $server->getDefaultProperties()['tools'];
        $this->ok('tools', count($tools) === 68, 'hay '.count($tools).' tools (se esperaban 68)');

        foreach ($tools as $class) {
            $name = (new ReflectionClass($class))->getAttributes(Name::class)[0]->newInstance()->value;
            $description = (new ReflectionClass($class))->getAttributes(Description::class)[0]->newInstance()->value;
            $this->ok($name, is_string($description) && trim($description) !== '', 'sin descripción');
        }
    }

    private function checkVoseo(): void
    {
        $server = new ReflectionClass(Heraldo::class);
        $instructions = $server->getAttributes(Instructions::class)[0]->newInstance()->value;
        $this->ok('instructions', preg_match(self::VOSEO, $instructions) === 0, 'voseo en instructions');

        /** @var list<class-string> $tools */
        $tools = $server->getDefaultProperties()['tools'];
        foreach ($tools as $class) {
            $name = (new ReflectionClass($class))->getAttributes(Name::class)[0]->newInstance()->value;
            $description = (new ReflectionClass($class))->getAttributes(Description::class)[0]->newInstance()->value;
            $this->ok($name.' tú', preg_match(self::VOSEO, $description) === 0, 'voseo en descripción');
        }
    }

    private function checkEnums(): void
    {
        $schema = new JsonSchemaTypeFactory;
        /** @var list<class-string> $tools */
        $tools = (new ReflectionClass(Heraldo::class))->getDefaultProperties()['tools'];

        foreach ($tools as $class) {
            $tool = new $class;
            if (! method_exists($tool, 'schema')) {
                continue;
            }

            /** @var array<string, mixed> $args */
            $args = $tool->schema($schema);
            $name = (new ReflectionClass($class))->getAttributes(Name::class)[0]->newInstance()->value;

            if (isset($args['review'])) {
                $this->ok(
                    $name.' review enum',
                    ($args['review']->toArray()['enum'] ?? null) === ['all', 'in_review', 'approved', 'rejected', 'reviewed', 'ai_flagged'],
                    'review sin enum'
                );
            }
        }

        $stepEnum = (new ConsolaMoverPaso)->schema($schema)['step']->toArray()['enum'] ?? [];
        $this->ok(
            'consola-mover-paso step',
            $stepEnum === ['order', 'campaigns', 'design', 'content', 'templates', 'review', 'delivery'],
            'step incluye plugin o valores raros'
        );
    }

    private function checkPhrases(): void
    {
        $mover = (new ReflectionClass(ConsolaMoverPaso::class))->getAttributes(Description::class)[0]->newInstance()->value;
        $adapt = (new ReflectionClass(PedirAdaptaciones::class))->getAttributes(Description::class)[0]->newInstance()->value;

        $this->ok('frase mover-paso', str_contains($mover, 'se marca skipped en cuanto se sabe'), 'falta frase skipped');
        $this->ok('frase pedir-adaptaciones', str_contains($adapt, 'cuéntalas en plan-produccion'), 'falta frase adaptaciones');
    }

    private function checkLiveReads(): void
    {
        $tenant = self::TENANT;
        $shapes = [];
        $previousPath = storage_path('app/heraldo-mcp-smoke-shapes.json');
        /** @var array<string, mixed> $previous */
        $previous = File::exists($previousPath)
            ? (json_decode((string) File::get($previousPath), true) ?: [])
            : [];

        $calls = [
            'listar-tenants' => fn (): array|Response => HeraldoApi::getJson('tenants'),
            'listar-campanas' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/campaigns"),
            'listar-presets' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/presets"),
            'obtener-campana' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/campaigns/".self::CAMPAIGN_PLAN),
            'ver-campana' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/campaigns/".self::CAMPAIGN_PLAN.'/setup'),
            'estado-produccion' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/campaigns/".self::CAMPAIGN_PIECES.'/production/status'),
            'plan-produccion' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/campaigns/".self::CAMPAIGN_PLAN.'/production/plan'),
            'listar-copys' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/campaigns/".self::CAMPAIGN_PLAN.'/copies'),
            'estado-copys-ia' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/campaigns/".self::CAMPAIGN_PLAN.'/copies/generate'),
            'listar-piezas' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/campaigns/".self::CAMPAIGN_PIECES.'/pieces'),
            'listar-recetas' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/animation-recipes"),
            'listar-exportaciones' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/campaigns/".self::CAMPAIGN_PIECES.'/exports'),
            'uso-campana' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/campaigns/".self::CAMPAIGN_PIECES.'/usage'),
            'uso-tenant' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/usage"),
            'ver-direcciones' => fn (): array|Response => HeraldoApi::getJson("tenants/{$tenant}/campaigns/".self::CAMPAIGN_PLAN.'/production/adaptation-directions'),
            'consola-pendientes' => fn (): array|Response => HeraldoApi::consoleGetJson('pending'),
            'consola-listar-pedidos' => fn (): array|Response => HeraldoApi::consoleGetJson('orders'),
            'consola-ver-pedido' => fn (): array|Response => HeraldoApi::consoleGetJson('orders/'.self::ORDER, ['refresh' => false]),
            'consola-listar-preguntas' => fn (): array|Response => HeraldoApi::consoleGetJson('orders/'.self::ORDER.'/questions'),
            'consola-listar-marcas' => fn (): array|Response => HeraldoApi::consoleGetJson('brands', ['tenant_id' => $tenant]),
            'consola-ver-marca' => fn (): array|Response => HeraldoApi::consoleGetJson('brands/'.self::BRAND),
            'consola-listar-presets' => fn (): array|Response => HeraldoApi::consoleGetJson('presets', ['tenant_id' => $tenant]),
            'consola-senales' => fn (): array|Response => HeraldoApi::consoleGetJson('orders/'.self::ORDER.'/signals'),
        ];

        foreach ($calls as $label => $call) {
            try {
                $json = $call();
                if ($json instanceof Response) {
                    $this->failLine($label, $this->responseText($json));

                    continue;
                }
                $this->line("ok {$label}");
                $shapes[$label] = $this->shapeOf($json);
                if (isset($previous[$label]) && is_array($previous[$label])) {
                    $diff = $this->shapeDiff($previous[$label], $shapes[$label]);
                    if ($diff !== []) {
                        $this->failLine($label.' forma', implode('; ', $diff));
                    }
                }
            } catch (Throwable $e) {
                $this->failLine($label, $e->getMessage());
            }
        }

        $recipes = HeraldoApi::getJson("tenants/{$tenant}/animation-recipes");
        if (is_array($recipes)) {
            $list = $recipes['data']['recipes'] ?? $recipes['data'] ?? [];
            $count = is_array($list) ? count($list) : 0;
            $styles = $recipes['data']['styles'] ?? [];
            $this->ok('listar-recetas count', $count === 10, "recetas={$count}");
            $this->ok(
                'listar-recetas styles',
                array_values((array) $styles) === ['sobrio', 'dinamico', 'impacto'],
                'styles distintos'
            );
        } else {
            $this->failLine('listar-recetas fijo', $this->responseText($recipes));
        }

        $order = HeraldoApi::consoleGetJson('orders/'.self::ORDER, ['refresh' => false]);
        if (is_array($order)) {
            $steps = $order['data']['steps'] ?? [];
            $this->ok('pedido 4 pasos', is_array($steps) && count($steps) === 8, 'pasos='.(is_array($steps) ? count($steps) : 0));
        } else {
            $this->failLine('pedido 4', $this->responseText($order));
        }

        $brand = HeraldoApi::consoleGetJson('brands/'.self::BRAND);
        if (is_array($brand)) {
            $tone = $brand['data']['tone'] ?? null;
            $this->ok('marca tone', $tone === 'tu', 'tone='.(string) $tone);
        } else {
            $this->failLine('marca 1', $this->responseText($brand));
        }

        $status = HeraldoApi::getJson("tenants/{$tenant}/campaigns/".self::CAMPAIGN_PIECES.'/production/status');
        if (is_array($status)) {
            $data = $status['data'] ?? [];
            foreach (['photos', 'copies', 'pieces', 'headlines', 'animations', 'in_progress', 'cost'] as $key) {
                $this->ok("estado-produccion.{$key}", is_array($data) && array_key_exists($key, $data), "falta {$key}");
            }
        } else {
            $this->failLine('estado-produccion fijo', $this->responseText($status));
        }

        $response = HeraldoApi::client()->get(
            HeraldoApi::url("tenants/{$tenant}/campaigns/".self::CAMPAIGN_PLAN.'/usage'),
            ['date_from' => '2026-11-10', 'date_to' => '2026-11-01'],
        );
        $body = (string) $response->body();
        $this->ok(
            'uso-campana 422',
            $response->status() === 422 && str_contains($body, 'date_from'),
            'status='.$response->status().' body='.substr($body, 0, 200)
        );

        // Optional follow-ups from list responses
        if (is_array($order)) {
            $questions = HeraldoApi::consoleGetJson('orders/'.self::ORDER.'/questions');
            if (is_array($questions)) {
                $firstQuestion = $questions['data'][0]['id'] ?? null;
                if (is_numeric($firstQuestion)) {
                    $q = HeraldoApi::consoleGetJson('orders/'.self::ORDER.'/questions/'.$firstQuestion);
                    $this->ok('consola-ver-pregunta', is_array($q), is_array($q) ? 'ok' : $this->responseText($q));
                }
            }
            $fileId = $order['data']['files'][0]['id'] ?? null;
            if (is_numeric($fileId)) {
                $file = HeraldoApi::consoleGetJson('files/'.$fileId);
                $this->ok('consola-ver-archivo', is_array($file), is_array($file) ? 'ok' : $this->responseText($file));
            }
        }

        if ($this->option('save-shapes') && $shapes !== []) {
            File::ensureDirectoryExists(dirname($previousPath));
            File::put($previousPath, (string) json_encode($shapes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info('Formas guardadas en '.$previousPath);
        }
    }

    private function responseText(Response $response): string
    {
        try {
            $content = $response->content();
            $text = method_exists($content, '__toString')
                ? (string) $content
                : (string) json_encode($content, JSON_UNESCAPED_UNICODE);

            return strlen($text) > 300 ? substr($text, 0, 300).'…' : $text;
        } catch (Throwable $e) {
            return 'error Response: '.$e->getMessage();
        }
    }

    /**
     * @param  array<string, mixed>  $value
     * @return array<string, mixed>
     */
    private function shapeOf(array $value, int $depth = 0): array
    {
        $shape = [];
        foreach ($value as $key => $item) {
            if ($depth >= 2) {
                $shape[(string) $key] = get_debug_type($item);

                continue;
            }

            if (is_array($item)) {
                $shape[(string) $key] = array_is_list($item)
                    ? ['list' => isset($item[0]) && is_array($item[0]) ? $this->shapeOf($item[0], $depth + 1) : get_debug_type($item[0] ?? null)]
                    : $this->shapeOf($item, $depth + 1);
            } else {
                $shape[(string) $key] = get_debug_type($item);
            }
        }

        return $shape;
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return list<string>
     */
    private function shapeDiff(array $old, array $new, string $prefix = ''): array
    {
        $diff = [];
        foreach ($old as $key => $oldType) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (! array_key_exists($key, $new)) {
                $diff[] = "faltó {$path}";

                continue;
            }
            if (is_array($oldType) && is_array($new[$key])) {
                $diff = [...$diff, ...$this->shapeDiff($oldType, $new[$key], $path)];
            } elseif ($oldType !== $new[$key] && ! (is_array($oldType) || is_array($new[$key]))) {
                $diff[] = "{$path}: {$oldType} → {$new[$key]}";
            }
        }
        foreach ($new as $key => $_) {
            if (! array_key_exists($key, $old)) {
                $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
                $diff[] = "apareció {$path}";
            }
        }

        return $diff;
    }

    private function ok(string $label, bool $pass, string $detail): void
    {
        if ($pass) {
            $this->line("ok {$label}");

            return;
        }

        $this->failLine($label, $detail);
    }

    private function failLine(string $label, string $detail): void
    {
        $this->failures[] = "{$label}: {$detail}";
        $this->error("FAIL {$label}: {$detail}");
    }
}
