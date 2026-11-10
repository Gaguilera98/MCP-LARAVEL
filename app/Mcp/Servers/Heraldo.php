<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Heraldo\AsignarReceta;
use App\Mcp\Tools\Heraldo\ConfigurarCampana;
use App\Mcp\Tools\Heraldo\ConfigurarSanto;
use App\Mcp\Tools\Heraldo\ConsolaAnotar;
use App\Mcp\Tools\Heraldo\ConsolaCrearMarca;
use App\Mcp\Tools\Heraldo\ConsolaCrearPedido;
use App\Mcp\Tools\Heraldo\ConsolaCrearPresetBorrador;
use App\Mcp\Tools\Heraldo\ConsolaEditarMarca;
use App\Mcp\Tools\Heraldo\ConsolaEditarPedido;
use App\Mcp\Tools\Heraldo\ConsolaEstimar;
use App\Mcp\Tools\Heraldo\ConsolaListarMarcas;
use App\Mcp\Tools\Heraldo\ConsolaListarPedidos;
use App\Mcp\Tools\Heraldo\ConsolaListarPreguntas;
use App\Mcp\Tools\Heraldo\ConsolaListarPresets;
use App\Mcp\Tools\Heraldo\ConsolaMarcarLeida;
use App\Mcp\Tools\Heraldo\ConsolaMoverPaso;
use App\Mcp\Tools\Heraldo\ConsolaPendientes;
use App\Mcp\Tools\Heraldo\ConsolaPreguntar;
use App\Mcp\Tools\Heraldo\ConsolaQuitarCampana;
use App\Mcp\Tools\Heraldo\ConsolaRegistrarCorrida;
use App\Mcp\Tools\Heraldo\ConsolaRetirarPregunta;
use App\Mcp\Tools\Heraldo\ConsolaSenales;
use App\Mcp\Tools\Heraldo\ConsolaSubirArchivo;
use App\Mcp\Tools\Heraldo\ConsolaVerArchivo;
use App\Mcp\Tools\Heraldo\ConsolaVerMarca;
use App\Mcp\Tools\Heraldo\ConsolaVerPedido;
use App\Mcp\Tools\Heraldo\ConsolaVerPregunta;
use App\Mcp\Tools\Heraldo\ConsolaVincularCampanas;
use App\Mcp\Tools\Heraldo\CrearCampana;
use App\Mcp\Tools\Heraldo\DuplicarCampana;
use App\Mcp\Tools\Heraldo\ElegirCopys;
use App\Mcp\Tools\Heraldo\ElegirDireccion;
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
use App\Mcp\Tools\Heraldo\ListarRecetas;
use App\Mcp\Tools\Heraldo\ListarTenants;
use App\Mcp\Tools\Heraldo\ObtenerCampana;
use App\Mcp\Tools\Heraldo\PedirAdaptaciones;
use App\Mcp\Tools\Heraldo\PedirExportacion;
use App\Mcp\Tools\Heraldo\PlanProduccion;
use App\Mcp\Tools\Heraldo\QuitarFoto;
use App\Mcp\Tools\Heraldo\RegenerarCasilla;
use App\Mcp\Tools\Heraldo\RevisarPiezas;
use App\Mcp\Tools\Heraldo\SubirFoto;
use App\Mcp\Tools\Heraldo\SubirReferenciaSanto;
use App\Mcp\Tools\Heraldo\TraerFotos;
use App\Mcp\Tools\Heraldo\UsarColumnaBase;
use App\Mcp\Tools\Heraldo\UsoCampana;
use App\Mcp\Tools\Heraldo\UsoTenant;
use App\Mcp\Tools\Heraldo\VerCampana;
use App\Mcp\Tools\Heraldo\VerDirecciones;
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
    'Cada herramienta devuelve la respuesta de Heraldo tal cual. Si aparece un campo que no está descrito, úsalo igual. '.

    'QUÉ ES CADA COSA. '.
    'Cliente (tenant): la marca; todo pertenece a un cliente. '.
    'Preset: la plantilla de campaña del cliente, con sus mensajes, formatos y la hoja de productos de ejemplo. '.
    'Grilla de fotos: filas (producto × ángulo × foto) por columnas (proporciones como 1:1 o 9:16). Cada casilla lleva una foto. '.
    'Columna base: la proporción con todas sus fotos, desde la que la IA completa las demás. '.
    'Copys: los textos que van dentro del arte. Artes o piezas: las imágenes finales diseñadas en Figma. '.
    'Textos de publicación (titulares): el texto que acompaña al arte cuando se publica. '.
    'Receta: cómo se anima un arte para convertirlo en video. '.
    'Pedido de la consola: junta un trabajo del cliente con sus campañas y lo sigue en 8 pasos. '.
    'La persona entra solo cuando el pedido la necesita, y le llega un correo. '.

    'ORDEN. '.
    '1. listar-tenants, después listar-presets. '.
    '2. crear-campana con dry_run true; si la hoja está bien y la persona lo aprueba, con dry_run false. '.
    '3. ver-campana y, si hay que ajustar concepto, intención, cuadros con IA, «Foto que usa» o «La foto llega en», configurar-campana. '.
    '4. Las fotos: si ya están en otra campaña, traer-fotos (primero con dry_run true). Si no, subir-referencia-santo, configurar-santo y generar-fotos-base, y consulta estado-produccion hasta que terminen. También puedes subir-foto a mano. '.
    '5. hoja-fotos para revisarlas. Para ver una de cerca, ver-imagen con el preview_url de plan-produccion. '.
    'Para rehacer una sola casilla: plan-produccion, ver-direcciones y elegir-direccion si hace falta, regenerar-casilla con max_usd, y estado-produccion. Para borrarla, quitar-foto. '.
    '6. usar-columna-base y pedir-adaptaciones, y otra vez hoja-fotos. '.
    '7. Copys: generar-copys, consulta estado-copys-ia mientras status sea queued o processing; con completed, elige con elegir-copys. Si status viene vacío, no hay generación en curso: no esperes. Si over_max_length trae cuadros, acórtalos con guardar-copys. Para escribir los textos a mano, usa listar-copys y guardar-copys. '.
    '8. enlace-plugin-figma para diseñar los artes en Figma; después importar-figma (primero confirm false, después true). '.
    '9. hoja-piezas, y aprobar o rechazar con revisar-piezas usando los id de tiles. Para ver un arte de cerca, ver-imagen con el preview_url de listar-piezas. '.
    '10. generar-titulares y, cuando termine, excel-titulares. '.
    '11. Para video: extraer-capas; listar-recetas para ver qué hace cada una (y ver-imagen con example_image_url si hace falta); asignar-receta y generar-videos. '.
    'En asignar-receta y generar-videos, el filtro elige el grupo y piece_ids_json elige piezas concretas. '.
    '12. pedir-exportacion (pieces o videos) y listar-exportaciones para el enlace. '.
    'La campaña siguiente: duplicar-campana y después traer-fotos desde la anterior. '.
    'En cualquier momento, estado-produccion dice en qué paso está la campaña y qué sigue en cola. '.

    'CONSOLA. '.
    'consola-pendientes muestra qué paso se puede trabajar y las respuestas que todavía no leíste. '.
    'consola-ver-pedido trae el pedido entero: respeta siempre guidelines. '.
    'Si necesitas a una persona, consola-preguntar; cuando responde, aparece en consola-pendientes (answers) y la marcas con consola-marcar-leida. '.
    'consola-mover-paso marca el avance. Al entregar, consola-senales y propone aprendizajes en una aprobación de consola-preguntar. '.
    'En las tools de consola, manda agente con el nombre que debe verse en la bitácora de Heraldo, por ejemplo «Claude Code · lanzador» o «Agente de diseño». '.
    'Si no lo mandas, queda el nombre de la llave de Heraldo. consola-subir-archivo y consola-ver-archivo mueven archivos del pedido. '.

    'EN COLA. '.
    'generar-fotos-base, generar-copys, adaptaciones, importar Figma, titulares, capas, videos y ZIP no terminan en la misma llamada: responden queued o un estimado. '.
    'No digas que está listo: las fotos base se siguen en estado-produccion; los copys, en estado-copys-ia mientras estén queued o processing (si status viene vacío, no esperes); el resto, en estado-produccion (in_progress) o listar-exportaciones. '.

    'GASTO. '.
    'pedir-adaptaciones, generar-fotos-base, generar-copys, generar-titulares, generar-videos y regenerar-casilla cuestan dinero y exigen max_usd. '.
    'Si no sabes cuánto cuesta, llámala sin max_usd: no hace nada y el error trae estimate_usd. '.
    'Si el saldo de Santo no alcanza para generar-fotos-base, pedir-adaptaciones o regenerar-casilla, no se pide nada; el error trae santo_balance_usd. '.
    'Muéstrale ese número a la persona y llámala de nuevo con el tope que apruebe. '.
    'Confirma también antes de crear la campaña de verdad, de aprobar o rechazar artes en bloque y de pedir un ZIP. '.

    'ERRORES. '.
    'Un error trae el código, el mensaje de Heraldo y, si hay, el detalle (por ejemplo la lista de errores de la hoja o los valores válidos). '.
    'Léelo y corrige antes de reintentar. Un 409 quiere decir que falta un paso anterior: el mensaje dice cuál. '.

    'LLAVE. '.
    'La llave ya está configurada: no la pidas ni la cambies. '.
    'Si Heraldo responde que es de solo lectura, o que tu usuario no tiene permiso en ese cliente, dilo y no reintentes. '.

    'ENLACES. '.
    'subir-foto recibe foto_url, foto_base64 o console_file_id, uno solo; si la foto es de un pedido, usa console_file_id. '.
    'subir-referencia-santo recibe foto_url o foto_base64, uno solo. Mejor usa el enlace. '.
    'excel-titulares y listar-exportaciones devuelven enlaces de descarga: vencen a las 24 horas y sirven para una sola descarga. Pásaselos a la persona. '.
    'hoja-fotos y hoja-piezas muestran la hoja. ver-imagen muestra una foto, un arte o el ejemplo de una receta: pásale el preview_url de plan-produccion o de listar-piezas, o el example_image_url de listar-recetas. No armes el enlace. Los preview_url vencen a las 24 horas: si fallan, pide de nuevo el plan, las piezas o la hoja. '.

    'ARGUMENTOS JSON. '.
    'messages_json: ["Post","Story"]. format_ids_json: [1,2]. texts_json: {"<key del cuadro>":"Texto"}. slot_ids_json: [10,11]. piece_ids_json: [10,11]. '.
    'all_json y rows_json en configurar-santo. choices_json en elegir-copys: {"<key>":0}. '.
    'copy_slots_json, photo_sources_json y santo_targets_json en configurar-campana. '.
    'En la consola: campaign_ids_json, figma_links_json, notify_emails_json, skip_steps_json, options_json, file_ids_json, guidelines_json y payload_json. '.
    'Filtros review, platform, format y product: opcionales en listar-piezas, hoja-piezas, extraer-capas, asignar-receta, generar-videos y pedir-exportacion. '.
    'Para platform, format y product escribe el nombre tal como viene en listar-piezas.'
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
        RegenerarCasilla::class,
        QuitarFoto::class,
        VerDirecciones::class,
        ElegirDireccion::class,
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
        ListarRecetas::class,
        AsignarReceta::class,
        GenerarVideos::class,
        PedirExportacion::class,
        ListarExportaciones::class,
        ConsolaPendientes::class,
        ConsolaListarPedidos::class,
        ConsolaVerPedido::class,
        ConsolaListarPreguntas::class,
        ConsolaVerPregunta::class,
        ConsolaVerArchivo::class,
        ConsolaListarMarcas::class,
        ConsolaVerMarca::class,
        ConsolaListarPresets::class,
        ConsolaCrearPedido::class,
        ConsolaEditarPedido::class,
        ConsolaVincularCampanas::class,
        ConsolaQuitarCampana::class,
        ConsolaMoverPaso::class,
        ConsolaAnotar::class,
        ConsolaPreguntar::class,
        ConsolaSubirArchivo::class,
        ConsolaEstimar::class,
        ConsolaRegistrarCorrida::class,
        ConsolaSenales::class,
        ConsolaMarcarLeida::class,
        ConsolaRetirarPregunta::class,
        ConsolaCrearMarca::class,
        ConsolaEditarMarca::class,
        ConsolaCrearPresetBorrador::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
