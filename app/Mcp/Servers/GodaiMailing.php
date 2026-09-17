<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\Mailing\ActualizarCampana;
use App\Mcp\Tools\Mailing\ActualizarCc;
use App\Mcp\Tools\Mailing\ActualizarCliente;
use App\Mcp\Tools\Mailing\ActualizarEnvio;
use App\Mcp\Tools\Mailing\ActualizarParticipante;
use App\Mcp\Tools\Mailing\ActualizarPlantilla;
use App\Mcp\Tools\Mailing\AudienciaEnvio;
use App\Mcp\Tools\Mailing\CargarParticipantes;
use App\Mcp\Tools\Mailing\CompatibilidadPlantilla;
use App\Mcp\Tools\Mailing\CrearCampana;
use App\Mcp\Tools\Mailing\CrearCc;
use App\Mcp\Tools\Mailing\CrearCliente;
use App\Mcp\Tools\Mailing\CrearEnvio;
use App\Mcp\Tools\Mailing\CrearPlantilla;
use App\Mcp\Tools\Mailing\CuotaCuenta;
use App\Mcp\Tools\Mailing\FormatoPlantilla;
use App\Mcp\Tools\Mailing\LanzarEnvio;
use App\Mcp\Tools\Mailing\ListarBuzones;
use App\Mcp\Tools\Mailing\ListarCampanas;
use App\Mcp\Tools\Mailing\ListarCc;
use App\Mcp\Tools\Mailing\ListarClientes;
use App\Mcp\Tools\Mailing\ListarContactosBuzon;
use App\Mcp\Tools\Mailing\ListarCuentas;
use App\Mcp\Tools\Mailing\ListarDestinatariosEnvio;
use App\Mcp\Tools\Mailing\ListarDestinatariosPrueba;
use App\Mcp\Tools\Mailing\ListarEnvios;
use App\Mcp\Tools\Mailing\ListarMensajesBuzon;
use App\Mcp\Tools\Mailing\ListarParticipantes;
use App\Mcp\Tools\Mailing\ListarPlantillas;
use App\Mcp\Tools\Mailing\ListarRemitentes;
use App\Mcp\Tools\Mailing\ObtenerBuzon;
use App\Mcp\Tools\Mailing\ObtenerCampana;
use App\Mcp\Tools\Mailing\ObtenerCuenta;
use App\Mcp\Tools\Mailing\ObtenerEnvio;
use App\Mcp\Tools\Mailing\ObtenerMensajeBuzon;
use App\Mcp\Tools\Mailing\ObtenerPlantilla;
use App\Mcp\Tools\Mailing\PausarEnvio;
use App\Mcp\Tools\Mailing\PrevisualizarEnvio;
use App\Mcp\Tools\Mailing\ProbarEnvio;
use App\Mcp\Tools\Mailing\ReanudarEnvio;
use App\Mcp\Tools\Mailing\ReintentarFallidosEnvio;
use App\Mcp\Tools\Mailing\VariablesCampana;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Godai Mailing')]
#[Version('0.3.0')]
#[Instructions(
    'Godai Mailing sirve para enviar correos personalizados a una lista de personas. '.

    'QUÉ ES CADA COSA. '.
    'Cuenta: el espacio de trabajo; todo lo demás pertenece a una cuenta. '.
    'Cliente: la marca o empresa para la que trabajás; agrupa campañas y sus correos en copia. '.
    'Campaña: la lista de personas a las que vas a escribir, más los campos extra que querés personalizar. '.
    'Participante: cada persona de esa lista (correo, nombre, apellidos y sus campos extra). '.
    'Plantilla: el diseño y el asunto del correo, con variables como {{nombre}}. '.
    'Remitente: la dirección desde la que sale el correo; ya tiene que existir en la cuenta. '.
    'Envío: junta campaña, plantilla y remitente; nace como borrador y solo sale cuando lo lanzás. '.
    'Destinatario de prueba: buzón interno para recibir correos de prueba; ya tiene que existir en la cuenta. '.
    'Buzón IMAP (bandeja): casilla real sincronizada (ej. Eva Zalo); los mensajes y adjuntos se leen acá, siempre acotados a un buzón. '.

    'REGLAS BÁSICAS. '.
    'Empezá siempre por listar-cuentas y usá ese account_id en todas las demás herramientas. '.
    'Los campos extra se definen en la campaña. Si un dato no está definido ahí, se descarta al cargar participantes. '.
    'Variables disponibles en asunto y diseño: {{nombre}}, {{apellidos}}, {{email}} y una por cada campo extra, por ejemplo {{enlace}}. '.
    'Los correos en copia se dan de alta en el cliente y después se eligen en la campaña o en el envío; si mandás uno que no está dado de alta para ese cliente, se descarta y te avisa en warnings. '.
    'Al modificar algo, lo que mandás reemplaza a lo que había: si cargás de nuevo a una persona o cambiás la lista de campos extra de una campaña, todo lo que omitas se borra. Consultá el estado actual antes de editar. '.
    'Un envío solo se puede modificar mientras está en borrador. '.
    'Si no indicás asunto al crear el envío, se usa el de la plantilla. '.
    'Nada se puede borrar desde acá. '.
    'Los remitentes y los destinatarios de prueba no se crean acá: si faltan, pedí que los creen en el panel de Godai Mailing. '.

    'BANDEJA / EVALUADOR. '.
    'listar-buzones → mailbox (id o email). '.
    'Para evaluar personas una por una: listar-mensajes-buzon con mailbox + contact_email (o contact_id) + from/to (Y-m-d) + include_body=true; orden default desc (últimos primero). '.
    'Opcional: listar-contactos-buzon para descubrir quién escribió en el rango; obtener-mensaje-buzon para un mensaje puntual y sus adjuntos (url pública). '.
    'folder: inbox, sent o all (default all). No hay sync ni respuesta desde estas tools: solo lectura. '.

    'ARGUMENTOS QUE VAN COMO TEXTO JSON. '.
    'participants_json: [{"email":"ana@ejemplo.com","first_name":"Ana","last_name":"Paz","attributes":{"enlace":"https://ejemplo.com"}}]. '.
    'field_schema_json: [{"label":"Enlace","type":"url"}]; type puede ser text, url o bool. '.
    'attributes_json: {"enlace":"https://ejemplo.com"}. '.
    'cc_emails_json: ["copia@cliente.com"]. '.
    'audience_filter_json: {"logic":"all","rules":[{"field":"email","operator":"equals","value":"ana@ejemplo.com"}]}; logic all exige todas las condiciones y any al menos una; los campos posibles son email, first_name, last_name y los campos extra de la campaña; los operadores más usados son equals, not_equals, contains, is_empty y is_not_empty. '.
    'attachments_json: [{"mode":"fixed_url","name":"Guia.pdf","url":"https://ejemplo.com/guia.pdf"}]. '.
    'template_json: la plantilla completa; pedí primero el formato con formato-plantilla. '.
    'test_recipient_ids_json y recipient_ids_json: [1,2]. '.

    'ADJUNTOS. '.
    'Hay dos modos: fixed_url manda el mismo archivo a todos, y field toma una URL distinta por persona desde un campo extra de la campaña. '.
    'Cada archivo debe pesar 10 MB o menos. '.
    'El enlace tiene que descargar el archivo directamente: sirve una URL pública https, un archivo de Google Drive compartido como «cualquiera con el enlace», o un documento de Google que se pueda exportar. '.
    'No sirven las carpetas de Drive ni los enlaces que piden iniciar sesión. '.
    'Formatos aconsejados: pdf, imágenes, documentos de Office, txt, csv y zip; evitá archivos ejecutables. '.
    'En bandeja, los adjuntos de mensajes IMAP ya vienen con url pública en listar-mensajes-buzon / obtener-mensaje-buzon. '.

    'ANTES DE ENVIAR. '.
    'compatibilidad-plantilla avisa si la plantilla usa variables que la campaña no tiene; no envía nada. '.
    'previsualizar-envio muestra cómo queda el correo para una persona; no envía nada. '.
    'probar-envio manda un correo real, pero solo a los buzones de prueba. '.
    'lanzar-envio sí envía a toda la audiencia: consultá antes audiencia-envio y pasá ese mismo número en confirm_recipient_count; si no coincide, no se envía nada. Confirmá con la persona que te pidió el trabajo antes de lanzar. '.

    'ORDEN RECOMENDADO. '.
    'Envíos: listar-cuentas, crear-cliente, crear-cc si hace falta alguna copia, crear-campana con sus campos extra, cargar-participantes, '.
    'formato-plantilla y crear-plantilla, listar-remitentes, compatibilidad-plantilla, crear-envio, audiencia-envio, previsualizar-envio, probar-envio y por último lanzar-envio. '.
    'Si algunos correos fallan, revisalos con listar-destinatarios-envio usando status=failed y reintentá con reintentar-fallidos-envio. '.
    'Evaluar bandeja: listar-cuentas → listar-buzones → por cada persona listar-mensajes-buzon (contact_email + from/to + include_body).'
)]
class GodaiMailing extends Server
{
    public int $defaultPaginationLength = 50;

    protected array $tools = [
        ListarCuentas::class,
        ObtenerCuenta::class,
        CuotaCuenta::class,
        ListarRemitentes::class,
        ListarClientes::class,
        CrearCliente::class,
        ActualizarCliente::class,
        ListarCc::class,
        CrearCc::class,
        ActualizarCc::class,
        ListarDestinatariosPrueba::class,
        ListarBuzones::class,
        ObtenerBuzon::class,
        ListarContactosBuzon::class,
        ListarMensajesBuzon::class,
        ObtenerMensajeBuzon::class,
        ListarCampanas::class,
        CrearCampana::class,
        ObtenerCampana::class,
        ActualizarCampana::class,
        VariablesCampana::class,
        ListarParticipantes::class,
        CargarParticipantes::class,
        ActualizarParticipante::class,
        ListarPlantillas::class,
        FormatoPlantilla::class,
        CrearPlantilla::class,
        ObtenerPlantilla::class,
        ActualizarPlantilla::class,
        CompatibilidadPlantilla::class,
        ListarEnvios::class,
        CrearEnvio::class,
        ObtenerEnvio::class,
        ActualizarEnvio::class,
        ListarDestinatariosEnvio::class,
        AudienciaEnvio::class,
        PrevisualizarEnvio::class,
        ProbarEnvio::class,
        LanzarEnvio::class,
        PausarEnvio::class,
        ReanudarEnvio::class,
        ReintentarFallidosEnvio::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
