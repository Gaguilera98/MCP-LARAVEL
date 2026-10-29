<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Heraldo\AsignarReceta;
use App\Mcp\Tools\Heraldo\CrearCampana;
use App\Mcp\Tools\Heraldo\EnlacePluginFigma;
use App\Mcp\Tools\Heraldo\EstadoProduccion;
use App\Mcp\Tools\Heraldo\ExcelTitulares;
use App\Mcp\Tools\Heraldo\ExtraerCapas;
use App\Mcp\Tools\Heraldo\GenerarTitulares;
use App\Mcp\Tools\Heraldo\GenerarVideos;
use App\Mcp\Tools\Heraldo\GuardarCopys;
use App\Mcp\Tools\Heraldo\ImportarFigma;
use App\Mcp\Tools\Heraldo\ListarCampanas;
use App\Mcp\Tools\Heraldo\ListarCopys;
use App\Mcp\Tools\Heraldo\ListarExportaciones;
use App\Mcp\Tools\Heraldo\ListarPiezas;
use App\Mcp\Tools\Heraldo\ListarPresets;
use App\Mcp\Tools\Heraldo\ListarTenants;
use App\Mcp\Tools\Heraldo\ObtenerCampana;
use App\Mcp\Tools\Heraldo\PedirAdaptaciones;
use App\Mcp\Tools\Heraldo\PedirExportacion;
use App\Mcp\Tools\Heraldo\PlanProduccion;
use App\Mcp\Tools\Heraldo\RevisarPiezas;
use App\Mcp\Tools\Heraldo\SubirFoto;
use App\Mcp\Tools\Heraldo\UsarColumnaBase;
use App\Mcp\Tools\Heraldo\UsoCampana;
use App\Mcp\Tools\Heraldo\UsoTenant;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Heraldo')]
#[Version('0.1.0')]
#[Instructions(
    'Heraldo produce las piezas de una campaña de publicidad: arma la grilla de fotos por producto y proporción, '.
    'escribe los copys, recibe los artes diseñados en Figma, escribe los textos de publicación, anima los artes y los entrega en ZIP. '.
    'Cada herramienta devuelve la respuesta de Heraldo tal cual. Si aparece un campo que no está descrito, usalo igual. '.

    'QUÉ ES CADA COSA. '.
    'Cliente (tenant): la marca; todo pertenece a un cliente. '.
    'Preset: la plantilla de campaña del cliente, con sus mensajes, formatos y la hoja de productos de ejemplo. '.
    'Grilla de fotos: filas (producto × ángulo × foto) por columnas (proporciones como 1:1 o 9:16). Cada casilla lleva una foto. '.
    'Columna base: la proporción con todas sus fotos, desde la que la IA completa las demás. '.
    'Copys: los textos que van dentro del arte. Artes o piezas: las imágenes finales diseñadas en Figma. '.
    'Textos de publicación (titulares): el texto que acompaña al arte cuando se publica. '.
    'Receta: cómo se anima un arte para convertirlo en video. '.

    'ORDEN. '.
    '1. listar-tenants, después listar-presets. '.
    '2. crear-campana con dry_run true; si la hoja está bien y la persona lo aprueba, con dry_run false. '.
    '3. plan-produccion para ver la grilla. subir-foto en las casillas de una columna, usar-columna-base con esa columna y pedir-adaptaciones para completar el resto. '.
    '4. listar-copys y guardar-copys hasta completarlos. '.
    '5. enlace-plugin-figma para diseñar los artes en Figma; después importar-figma (primero confirm false, después true). '.
    '6. listar-piezas y revisar-piezas. '.
    '7. generar-titulares y, cuando termine, excel-titulares. '.
    '8. Para video: extraer-capas, asignar-receta y generar-videos. '.
    '9. pedir-exportacion (pieces o videos) y listar-exportaciones para el enlace. '.
    'En cualquier momento, estado-produccion dice en qué paso está la campaña y qué sigue en cola. '.

    'EN COLA. '.
    'adaptaciones, importar Figma, titulares, capas, videos y ZIP no terminan en la misma llamada: responden queued o un estimado. '.
    'No digas que está listo: volvé a consultar estado-produccion (in_progress) o listar-exportaciones. '.

    'GASTO. '.
    'pedir-adaptaciones, generar-titulares y generar-videos cuestan dinero y exigen max_usd. '.
    'Si no sabés cuánto cuesta, llamala sin max_usd: no hace nada y el error trae estimate_usd. '.
    'Mostrale ese número a la persona y llamala de nuevo con el tope que apruebe. '.
    'Confirmá también antes de crear la campaña de verdad, de aprobar o rechazar artes en bloque y de pedir un ZIP. '.

    'ERRORES. '.
    'Un error trae el código, el mensaje de Heraldo y, si hay, el detalle (por ejemplo la lista de errores de la hoja o los valores válidos). '.
    'Leelo y corregí antes de reintentar. Un 409 quiere decir que falta un paso anterior: el mensaje dice cuál. '.

    'LLAVE. '.
    'La llave ya está configurada: no la pidas ni la cambies. '.
    'Si Heraldo responde que es de solo lectura, o que tu usuario no tiene permiso en ese cliente, decilo y no reintentes. '.

    'ENLACES. '.
    'subir-foto recibe foto_url o foto_base64, uno solo. Preferí el enlace. '.
    'excel-titulares y listar-exportaciones devuelven enlaces de descarga: vencen a las 24 horas y sirven para una sola descarga. Pasaselos a la persona. '.

    'ARGUMENTOS JSON. '.
    'messages_json: ["Post","Story"]. format_ids_json: [1,2]. texts_json: {"<key del cuadro>":"Texto"}. slot_ids_json: [10,11]. '.
    'Filtros review, platform, format y product: opcionales en listar-piezas, extraer-capas, asignar-receta, generar-videos y pedir-exportacion. '.
    'Para platform, format y product escribí el nombre tal como viene en listar-piezas.'
)]
class Heraldo extends Server
{
    public int $defaultPaginationLength = 50;

    protected array $tools = [
        ListarTenants::class,
        ListarCampanas::class,
        ObtenerCampana::class,
        UsoTenant::class,
        UsoCampana::class,
        ListarPresets::class,
        CrearCampana::class,
        EstadoProduccion::class,
        PlanProduccion::class,
        SubirFoto::class,
        UsarColumnaBase::class,
        PedirAdaptaciones::class,
        ListarCopys::class,
        GuardarCopys::class,
        EnlacePluginFigma::class,
        ImportarFigma::class,
        ListarPiezas::class,
        RevisarPiezas::class,
        GenerarTitulares::class,
        ExcelTitulares::class,
        ExtraerCapas::class,
        AsignarReceta::class,
        GenerarVideos::class,
        PedirExportacion::class,
        ListarExportaciones::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
