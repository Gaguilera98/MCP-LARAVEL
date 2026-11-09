<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Heraldo\AsignarReceta;
use App\Mcp\Tools\Heraldo\ConfigurarCampana;
use App\Mcp\Tools\Heraldo\ConfigurarSanto;
use App\Mcp\Tools\Heraldo\CrearCampana;
use App\Mcp\Tools\Heraldo\DuplicarCampana;
use App\Mcp\Tools\Heraldo\ElegirCopys;
use App\Mcp\Tools\Heraldo\EnlacePluginFigma;
use App\Mcp\Tools\Heraldo\EstadoCopysIa;
use App\Mcp\Tools\Heraldo\EstadoProduccion;
use App\Mcp\Tools\Heraldo\ExcelTitulares;
use App\Mcp\Tools\Heraldo\ExtraerCapas;
use App\Mcp\Tools\Heraldo\GenerarCopys;
use App\Mcp\Tools\Heraldo\GenerarFotosBase;
use App\Mcp\Tools\Heraldo\GenerarTitulares;
use App\Mcp\Tools\Heraldo\GenerarVideos;
use App\Mcp\Tools\Heraldo\GuardarCopys;
use App\Mcp\Tools\Heraldo\HojaFotos;
use App\Mcp\Tools\Heraldo\HojaPiezas;
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
use App\Mcp\Tools\Heraldo\SubirReferenciaSanto;
use App\Mcp\Tools\Heraldo\TraerFotos;
use App\Mcp\Tools\Heraldo\UsarColumnaBase;
use App\Mcp\Tools\Heraldo\UsoCampana;
use App\Mcp\Tools\Heraldo\UsoTenant;
use App\Mcp\Tools\Heraldo\VerCampana;
use App\Mcp\Tools\Heraldo\VerImagen;
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
    '3. ver-campana y, si hay que ajustar concepto, intención, cuadros con IA, «Foto que usa» o «La foto llega en», configurar-campana. '.
    '4. Las fotos: si ya están en otra campaña, traer-fotos (primero con dry_run true). Si no, subir-referencia-santo, configurar-santo y generar-fotos-base, y consultá estado-produccion hasta que terminen. También podés subir-foto a mano. '.
    '5. hoja-fotos para revisarlas. Para ver una de cerca, ver-imagen con el preview_url de plan-produccion. '.
    '6. usar-columna-base y pedir-adaptaciones, y otra vez hoja-fotos. '.
    '7. Copys: generar-copys, consultá estado-copys-ia mientras status sea queued o processing; con completed, elegí con elegir-copys. Si status viene vacío, no hay generación en curso: no esperes. Si over_max_length trae cuadros, acortalos con guardar-copys. Para escribir los textos a mano, usá listar-copys y guardar-copys. '.
    '8. enlace-plugin-figma para diseñar los artes en Figma; después importar-figma (primero confirm false, después true). '.
    '9. hoja-piezas, y aprobar o rechazar con revisar-piezas usando los id de tiles. Para ver un arte de cerca, ver-imagen con el preview_url de listar-piezas. '.
    '10. generar-titulares y, cuando termine, excel-titulares. '.
    '11. Para video: extraer-capas, asignar-receta y generar-videos. '.
    '12. pedir-exportacion (pieces o videos) y listar-exportaciones para el enlace. '.
    'La campaña siguiente: duplicar-campana y después traer-fotos desde la anterior. '.
    'En cualquier momento, estado-produccion dice en qué paso está la campaña y qué sigue en cola. '.

    'EN COLA. '.
    'generar-fotos-base, generar-copys, adaptaciones, importar Figma, titulares, capas, videos y ZIP no terminan en la misma llamada: responden queued o un estimado. '.
    'No digas que está listo: las fotos base se siguen en estado-produccion; los copys, en estado-copys-ia mientras estén queued o processing (si status viene vacío, no esperes); el resto, en estado-produccion (in_progress) o listar-exportaciones. '.

    'GASTO. '.
    'pedir-adaptaciones, generar-fotos-base, generar-copys, generar-titulares y generar-videos cuestan dinero y exigen max_usd. '.
    'Si no sabés cuánto cuesta, llamala sin max_usd: no hace nada y el error trae estimate_usd. '.
    'Si el saldo de Santo no alcanza para generar-fotos-base o pedir-adaptaciones, no se pide nada; el error trae santo_balance_usd. '.
    'Mostrale ese número a la persona y llamala de nuevo con el tope que apruebe. '.
    'Confirmá también antes de crear la campaña de verdad, de aprobar o rechazar artes en bloque y de pedir un ZIP. '.

    'ERRORES. '.
    'Un error trae el código, el mensaje de Heraldo y, si hay, el detalle (por ejemplo la lista de errores de la hoja o los valores válidos). '.
    'Leelo y corregí antes de reintentar. Un 409 quiere decir que falta un paso anterior: el mensaje dice cuál. '.

    'LLAVE. '.
    'La llave ya está configurada: no la pidas ni la cambies. '.
    'Si Heraldo responde que es de solo lectura, o que tu usuario no tiene permiso en ese cliente, decilo y no reintentes. '.

    'ENLACES. '.
    'subir-foto y subir-referencia-santo reciben foto_url o foto_base64, uno solo. Preferí el enlace. '.
    'excel-titulares y listar-exportaciones devuelven enlaces de descarga: vencen a las 24 horas y sirven para una sola descarga. Pasaselos a la persona. '.
    'hoja-fotos y hoja-piezas muestran la hoja. ver-imagen muestra una foto o un arte: pasale el preview_url de plan-produccion o de listar-piezas. No armes el enlace. Esos enlaces vencen a las 24 horas: si fallan, pedí de nuevo el plan, las piezas o la hoja. '.

    'ARGUMENTOS JSON. '.
    'messages_json: ["Post","Story"]. format_ids_json: [1,2]. texts_json: {"<key del cuadro>":"Texto"}. slot_ids_json: [10,11]. '.
    'all_json y rows_json en configurar-santo. choices_json en elegir-copys: {"<key>":0}. '.
    'copy_slots_json, photo_sources_json y santo_ratios_json en configurar-campana. '.
    'Filtros review, platform, format y product: opcionales en listar-piezas, hoja-piezas, extraer-capas, asignar-receta, generar-videos y pedir-exportacion. '.
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
        VerCampana::class,
        ConfigurarCampana::class,
        DuplicarCampana::class,
        EstadoProduccion::class,
        PlanProduccion::class,
        SubirFoto::class,
        TraerFotos::class,
        SubirReferenciaSanto::class,
        ConfigurarSanto::class,
        GenerarFotosBase::class,
        UsarColumnaBase::class,
        PedirAdaptaciones::class,
        HojaFotos::class,
        ListarCopys::class,
        GuardarCopys::class,
        GenerarCopys::class,
        EstadoCopysIa::class,
        ElegirCopys::class,
        EnlacePluginFigma::class,
        ImportarFigma::class,
        ListarPiezas::class,
        HojaPiezas::class,
        VerImagen::class,
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
