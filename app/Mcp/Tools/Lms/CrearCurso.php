<?php

namespace App\Mcp\Tools\Lms;

use App\Support\LmsApi;
use App\Support\LmsPayload;
use App\Support\LmsPresent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('crear-curso')]
#[Description(
    'Arma un curso de una sola vez: nombre, descripción, si es privado, fecha de cierre, categorías si las conocés, portada y los capítulos con sus pasos. '.
    'Antes subí la portada, las imágenes, los documentos y los videos, y usá los números que te devolvieron. '.
    'Un video de YouTube, Zoom u otra página no se sube: se escribe como enlace. '.
    'Si un archivo no existe o no corresponde a ese paso, el curso no se crea y te dice qué paso falló. El nombre no puede repetirse.'
)]
class CrearCurso extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $body = LmsPayload::course($request->all(), withChapters: true);

        if ($body instanceof Response) {
            return $body;
        }

        return LmsApi::post('courses', $body, [LmsPresent::class, 'curso']);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'nombre' => $schema->string()
                ->description('Nombre del curso. No puede repetirse.')
                ->required(),
            'descripcion' => $schema->string()
                ->description('De qué trata el curso. Podés omitirla.'),
            'privado' => $schema->boolean()
                ->description('Verdadero si solo lo ven quienes están inscriptos. Si no lo indicás, el curso es público.'),
            'cierra_el' => $schema->string()
                ->description('Fecha y hora en que el curso deja de verse, por ejemplo 2026-12-01 18:00.')
                ->required(),
            'categorias' => $schema->string()
                ->description('Números de categoría separados por coma, por ejemplo 1,3. Si no los conocés, omitilos.'),
            'portada' => $schema->integer()
                ->description('Número que devolvió subir-archivo cuando el uso era portada.'),
            'capitulos' => $schema->string()
                ->description(
                    'Lista de capítulos. Cada uno tiene nombre, visible (si lo omitís se ve) y pasos. '.
                    'Cada paso tiene nombre, tipo (texto, video, documento, imagen o enlace) y el contenido: '.
                    'texto para un texto, archivo con el número de subir-archivo o subir-video, o enlace con la dirección. '.
                    'Si un video trae archivo y enlace, se usa el archivo. Un paso se marca opcional con opcional en verdadero. '.
                    'Ejemplo: [{"nombre":"Semana 1","visible":true,"pasos":[{"nombre":"Introducción","tipo":"texto","texto":"Bienvenida"},{"nombre":"Clase","tipo":"video","archivo":8},{"nombre":"En vivo","tipo":"enlace","enlace":"https://zoom.example/clase"}]}]'
                ),
        ];
    }
}
