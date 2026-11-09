# MCP de Heraldo: 13 herramientas nuevas y 4 descripciones

**En qué mejora Heraldo:** hoy, el agente que opera Heraldo por el MCP necesita que una persona abra la página para cuatro cosas:
- configurar la campaña en el Constructor;
- generar las fotos base con Santo y los copys con IA;
- mirar las fotos y las piezas antes de aprobarlas;
- duplicar la campaña anterior para armar la siguiente.

Con estas herramientas, el agente hace todo eso solo, de la hoja al ZIP. La persona solo corre el plugin de Figma.

**Heraldo ya está listo.** Todas las rutas están en producción desde la versión 21, publicada el 7 de noviembre. Este paquete es solo del MCP: no hay que tocar nada en Heraldo.

## Qué hay que hacer en el MCP

1. **Sumar 12 herramientas** que llaman a rutas de la API que ya existen. Están en la tabla de abajo.
2. **Sumar `ver-imagen`**, que no llama a la API: abre un enlace de imagen de Heraldo y devuelve la imagen.
3. **Que `hoja-fotos`, `hoja-piezas` y `ver-imagen` devuelvan la imagen misma** como contenido de imagen del MCP, no solo el enlace. Es lo que más importa: así el agente ve lo que aprueba. Revisa 96 piezas en 4 imágenes.
4. **Agregar una frase a la descripción de 4 herramientas que ya existen:** `plan-produccion`, `listar-piezas`, `estado-produccion` y `pedir-adaptaciones`. Su código no cambia, porque la API ya devuelve los campos nuevos y el MCP los pasa tal cual. La frase le avisa al agente que esos campos existen.

| # | Herramienta | Ruta | ¿Gasta? |
|---|---|---|---|
| 1 | `traer-fotos` | `POST production/photos/copy-from` | No |
| 2 | `subir-referencia-santo` | `POST production/santo/reference` | No |
| 3 | `configurar-santo` | `PUT production/santo` | No |
| 4 | `generar-fotos-base` | `POST production/santo/generate` | **Sí** |
| 5 | `generar-copys` | `POST copies/generate` | **Sí**, muy poco |
| 6 | `estado-copys-ia` | `GET copies/generate` | No |
| 7 | `elegir-copys` | `POST copies/generate/apply` | No |
| 8 | `ver-campana` | `GET setup` | No |
| 9 | `configurar-campana` | `PUT setup` | No |
| 10 | `hoja-fotos` | `GET production/plan/contact-sheet` | No |
| 11 | `hoja-piezas` | `GET pieces/contact-sheet` | No |
| 12 | `duplicar-campana` | `POST duplicate` | No |
| 13 | `ver-imagen` | Ninguna: abre el enlace | No |

Las rutas cuelgan de `https://heraldo-x.co/api/v1/tenants/{tenant_id}/campaigns/{campaign_id}/`.

## Cómo se arman: igual que las 25 de hoy

- **La misma llave** que ya usa el MCP, con «Puede operar».
- **Los mismos parámetros de siempre:** `tenant_id` y `campaign_id`, con las mismas descripciones.
- **Las respuestas:** lo que la API devuelve en `data` se pasa al agente.
- **Los errores, tal cual:** el `message` y los campos que lo acompañan, como `estimate_usd`, `santo_balance_usd`, `warnings`, `errors` o `ratios`. Los mensajes ya están escritos para el agente y dicen qué hacer.
- **Lo que gasta exige `max_usd`.** Sin él, la API responde 422 con lo estimado y no hace nada. Así el agente sabe cuánto cuesta sin gastar.
- **Los filtros de `hoja-piezas`** son los mismos de `listar-piezas` (`review`, `platform`, `format` y `product`). Van como `filter[...]`, igual que en `listar-piezas`.
- **La imagen de `subir-referencia-santo`** se recibe como en `subir-foto`, con `foto_url` o `foto_base64`. Se manda a la API como multipart, en el campo `photo`.

## Los archivos

| Archivo | Qué trae |
|---|---|
| `herramientas.json` | Las 13 definiciones, listas para cargar: nombre, descripción, parámetros con su esquema, método, ruta, cómo se mandan los datos y si gasta. Al final, la frase para cada una de las 4 que ya existen |
| `HERRAMIENTAS-MCP.md` | El detalle de cada herramienta: qué hace, qué responde, sus errores y el orden de uso en una campaña |

## Cómo comprobarlas

**Solo en el cliente god** (`tenant_id` `b750cf20-cea4-4a2d-96fc-89fe128ac62d`), que es nuestra cuenta de prueba. En los clientes reales, nada.

Estas solo leen, y lo esperado sale de producción:

| Prueba | Qué tiene que dar |
|---|---|
| `ver-campana` de la campaña 43 | Un formato, «Instagram Feed Square», y sus cuadros de texto |
| `hoja-fotos` de la 43 | Una imagen con 2 fotos numeradas; `total` 2 y `pages` 1 |
| `hoja-piezas` de la 41 | Una imagen con 24 piezas; `total` 96 y `pages` 4. Con `page` 4, las últimas 24 |
| `ver-imagen` con un `preview_url` de `plan-produccion` de la 43 | La foto |
| `ver-imagen` con un `preview_url` de `listar-piezas` de la 41 | La pieza |
| `estado-copys-ia` de la 43 | Responde sin error |

Las que cambian datos o gastan las probamos nosotros en god cuando estén publicadas: `traer-fotos`, Santo, copys, configurar y duplicar.

## Lo que la API no hace, a propósito

- **No lista los talentos de Santo.** El modo Director pide `talent_id`, así que por ahora el agente usa el modo Diseñador: una imagen de referencia y un prompt. Es lo que usamos en las campañas de prueba.
- **No enciende «Los cuadros de texto forman una sola frase».** Ese interruptor se enciende en la página.
- **No regenera una casilla sola ni finaliza la campaña.** Finalizar queda como la firma de una persona.

No hay que suplir nada de esto en el MCP.
