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

#[Name('configurar-campana')]
#[Description(
    'Guarda cambios en el Constructor, como «Guardar campaña». Solo cambia lo que se manda: lo demás queda igual. '.
    'copy_slots ajusta el largo (max_length) y si lo escribe la IA (generate_with_ai) de cuadros que ya existen, por su key de ver-campana; no agrega, quita ni renombra cuadros. '.
    'photo_sources dice de qué formato sale la foto de cada formato ({ format_id: format_id dueño, o null para que use la suya }). '.
    'santo_ratios fija «La foto llega en» por formato ({ format_id: "4:5", o "" para volver al catálogo }), solo en formatos que usan su propia foto. '.
    'Ojo: null en description o intention los borra. Responde lo mismo que ver-campana.'
)]
class ConfigurarCampana extends Tool
{
    use CallsHeraldo;

    public function handle(Request $request): Response|ResponseFactory
    {
        $body = [];

        foreach (['description', 'intention'] as $field) {
            if ($this->hasArgument($request, $field)) {
                $body[$field] = $request->get($field);
            }
        }

        $body = $this->withJsonFields($request, $body, [
            'copy_slots_json' => 'copy_slots',
            'photo_sources_json' => 'photo_sources',
            'santo_ratios_json' => 'santo_ratios',
            'file_names_json' => 'file_names',
        ]);

        if ($body instanceof Response || $body instanceof ResponseFactory) {
            return $body;
        }

        return HeraldoApi::put($this->campaignPath($request, 'setup'), $body);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            ...$this->campaignArguments($schema),
            'description' => $schema->string()
                ->nullable()
                ->max(20000)
                ->description('El concepto de la campaña. Hasta 20.000 caracteres. null lo borra. Si no lo mandas, no se toca.'),
            'intention' => $schema->string()
                ->nullable()
                ->description('La intención, hasta 255 caracteres. null la borra. Si no la mandas, no se toca.'),
            'copy_slots_json' => $schema->string()
                ->description('JSON de cuadros que ya existen: [{"key":"<key de ver-campana>","max_length":80,"generate_with_ai":true}]. No agrega, quita ni renombra cuadros.'),
            'photo_sources_json' => $schema->string()
                ->description('JSON { format_id: format_id del formato dueño de la foto, o null }. Las opciones están en photo_source_options de ver-campana.'),
            'santo_ratios_json' => $schema->string()
                ->description('JSON { format_id: proporción de santo_ratio_options, o "" para el catálogo }. Solo en formatos que usan su propia foto.'),
            'file_names_json' => $schema->string()
                ->description('Cómo se llaman los archivos que se entregan (ZIP de artes y de videos, descarga y Excel de textos), como objeto JSON: {"template": "BPD_{codigo}_{angulo}_{medida}", "text": "Segmento A", "lowercase": false}. template lleva piezas entre llaves y texto fijo; las piezas son {campana}, {codigo}, {producto}, {variacion}, {angulo}, {mensaje}, {plataforma}, {objetivo}, {formato}, {medida}, {proporcion}, {foto}, {texto}, {fecha} y {pieza}. text es lo que va en {texto}. Lo que no mandas queda como estaba; template null vuelve a la plantilla del cliente. Si los nombres se pueden repetir, suma {formato}, {foto} o {pieza}.'),
        ];
    }
}
