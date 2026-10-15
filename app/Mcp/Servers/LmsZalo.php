<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Lms\ActualizarCapitulo;
use App\Mcp\Tools\Lms\ActualizarCurso;
use App\Mcp\Tools\Lms\ActualizarEstudiante;
use App\Mcp\Tools\Lms\ActualizarPaso;
use App\Mcp\Tools\Lms\AgregarCapitulo;
use App\Mcp\Tools\Lms\AgregarPaso;
use App\Mcp\Tools\Lms\AsignarTutores;
use App\Mcp\Tools\Lms\CargarCalificaciones;
use App\Mcp\Tools\Lms\CrearCurso;
use App\Mcp\Tools\Lms\CrearEstudiante;
use App\Mcp\Tools\Lms\Inscribir;
use App\Mcp\Tools\Lms\ListarCursos;
use App\Mcp\Tools\Lms\ListarEstudiantes;
use App\Mcp\Tools\Lms\ListarInscripciones;
use App\Mcp\Tools\Lms\ListarTutores;
use App\Mcp\Tools\Lms\QuitarCapitulo;
use App\Mcp\Tools\Lms\QuitarInscripciones;
use App\Mcp\Tools\Lms\QuitarPaso;
use App\Mcp\Tools\Lms\QuitarTutores;
use App\Mcp\Tools\Lms\SubirArchivo;
use App\Mcp\Tools\Lms\SubirVideo;
use App\Mcp\Tools\Lms\VerCurso;
use App\Mcp\Tools\Lms\VerEstudiante;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('LMS Zalo')]
#[Version('0.1.0')]
#[Instructions(
    'Este server es el aula de Zalo: cursos, estudiantes, inscripciones, notas y tutores. '.
    'Orden para armar un curso: primero subí la portada, las imágenes, los documentos y los videos; cada subida devuelve un número. '.
    'Después creá el curso usando esos números. Luego creá los estudiantes si no existen, inscribilos, y cuando corresponda cargá la nota y el comentario del diploma. '.
    'Si el capítulo tiene tutores, asigná los que ya existen. '.
    'Un capítulo es una parte del curso, como una semana o un módulo. Un paso es una pieza dentro del capítulo: un texto, un video, un documento, una imagen o un enlace. '.
    'Los videos de YouTube, Zoom u otra página no se suben: se escriben como enlace. El video propio del equipo se sube con subir-video. '.
    'Los listados vienen de a 15, del más nuevo al más viejo. Si hay_mas es verdadero, pedí la página siguiente. '.
    'Si una persona o un tutor queda en omitidos, leé el motivo y no vuelvas a intentar esa misma fila. '.
    'No podés borrar un curso, borrar la ficha de un estudiante, crear un tutor ni generar el pdf del diploma. '.
    'La nota y el comentario sí se guardan, y el diploma del aula los muestra cuando la persona terminó el curso y tiene nota. '.
    'No hay formularios ni cuestionarios acá.'
)]
class LmsZalo extends Server
{
    protected array $tools = [
        SubirArchivo::class,
        SubirVideo::class,
        ListarCursos::class,
        VerCurso::class,
        CrearCurso::class,
        ActualizarCurso::class,
        AgregarCapitulo::class,
        ActualizarCapitulo::class,
        QuitarCapitulo::class,
        AgregarPaso::class,
        ActualizarPaso::class,
        QuitarPaso::class,
        ListarEstudiantes::class,
        VerEstudiante::class,
        CrearEstudiante::class,
        ActualizarEstudiante::class,
        ListarInscripciones::class,
        Inscribir::class,
        QuitarInscripciones::class,
        CargarCalificaciones::class,
        ListarTutores::class,
        AsignarTutores::class,
        QuitarTutores::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
