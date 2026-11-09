# MCP de Heraldo: las 25 herramientas de la consola

**En qué mejora Heraldo:** la consola junta cada pedido de un cliente con sus campañas y lo sigue por 8 pasos. Con estas herramientas, el agente que opera Heraldo por el MCP trabaja dentro del pedido:
- ve qué paso le toca y los lineamientos de la marca que tiene que respetar;
- le pregunta a una persona y lee su respuesta;
- deja avances, capturas, el previsto y lo que costó su corrida;
- marca los pasos;
- al entregar, lee qué no funcionó y propone aprendizajes que la persona aprueba o descarta.

La persona entra a la consola solo cuando el pedido la necesita, y le llega un correo.

**Cuándo:** cuando el dev de Heraldo publique la consola (`paquete-dev-consola`). Antes, estas rutas no existen.

## Cómo se arman: igual que las de hoy

- **La misma llave.** Leer, con cualquier llave; cambiar algo, con «Puede operar».
- **Base:** `https://heraldo-x.co/api/console/v1/`.
- **Respuestas:** lo que viene en `data` se pasa al agente. Los errores traen `message`; se pasa tal cual:
  - 404: no existe;
  - 409: el pedido está en pausa o cerrado, o el previsto ya está congelado;
  - 422: datos mal enviados.
- **El parámetro `agente`** no va en el cuerpo: se manda como cabecera `X-Heraldo-Agent`. Es el nombre que la persona ve en la bitácora, por ejemplo «Agente de diseño». Si no viene, queda el nombre de la llave.
- **`consola-subir-archivo`** recibe el archivo como `subir-foto`, con `archivo_url` o `archivo_base64`:
  - con `archivo_url`, el MCP lo baja y lo manda como multipart, en el campo `file`;
  - con base64, lo manda como `content_base64`.

  Los demás campos van igual.
- **`consola-ver-archivo` devuelve la imagen misma,** como `ver-imagen`:
  - si `mime` empieza con `image/` (y no es SVG), el MCP abre `url` y la devuelve como contenido de imagen;
  - que solo abra enlaces `https://heraldo-x.co/consola/archivos/…`.

| # | Herramienta | Ruta | ¿Cambia datos? |
|---|---|---|---|
| 1 | `consola-pendientes` | `GET pending` | No |
| 2 | `consola-listar-pedidos` | `GET orders` | No |
| 3 | `consola-ver-pedido` | `GET orders/{order_id}` | No |
| 4 | `consola-listar-preguntas` | `GET orders/{order_id}/questions` | No |
| 5 | `consola-ver-pregunta` | `GET questions/{question_id}` | No |
| 6 | `consola-ver-archivo` | `GET files/{file_id}` y abre la imagen | No |
| 7 | `consola-listar-marcas` | `GET brands` | No |
| 8 | `consola-ver-marca` | `GET brands/{brand_id}` | No |
| 9 | `consola-listar-presets` | `GET tenants/{tenant_id}/presets` | No |
| 10 | `consola-crear-pedido` | `POST orders` | Sí |
| 11 | `consola-editar-pedido` | `PATCH orders/{order_id}` | Sí |
| 12 | `consola-vincular-campanas` | `POST orders/{order_id}/campaigns` | Sí |
| 13 | `consola-quitar-campana` | `DELETE orders/{order_id}/campaigns/{campaign_id}` | Sí |
| 14 | `consola-mover-paso` | `PUT orders/{order_id}/steps/{step}` | Sí |
| 15 | `consola-anotar` | `POST orders/{order_id}/events` | Sí |
| 16 | `consola-preguntar` | `POST orders/{order_id}/questions` | Sí. Manda un correo |
| 17 | `consola-subir-archivo` | `POST orders/{order_id}/files` | Sí |
| 18 | `consola-estimar` | `POST orders/{order_id}/estimate` | Sí |
| 19 | `consola-registrar-corrida` | `POST orders/{order_id}/agent-runs` | Sí |
| 20 | `consola-senales` | `GET orders/{order_id}/signals` | No |
| 21 | `consola-marcar-leida` | `POST questions/{question_id}/read` | Sí |
| 22 | `consola-retirar-pregunta` | `POST questions/{question_id}/cancel` | Sí |
| 23 | `consola-crear-marca` | `POST brands` | Sí |
| 24 | `consola-editar-marca` | `PATCH brands/{brand_id}` | Sí |
| 25 | `consola-crear-preset-borrador` | `POST tenants/{tenant_id}/presets` | Sí, en el cliente: un preset en **borrador** |

La 9 y la 25 actúan dentro del cliente, como el usuario con el mismo correo que el dueño de la llave, igual que `crear-campana`. Por eso piden «Puede operar» también para leer.

**Lo que no tiene herramienta, a propósito:** crear, apagar o borrar lineamientos de una marca, decidir cuáles deja fuera un pedido, cambiar la marca de un pedido y descongelar el previsto. Eso lo hace una persona en la consola. El agente solo propone aprendizajes, con `guidelines` en una aprobación de `consola-preguntar`, y no entrega mientras esa aprobación siga abierta (409).

Las definiciones listas para cargar están en `herramientas-consola.json`: nombre, descripción para el agente, parámetros con su esquema, método, ruta y cómo se mandan los datos.

## Cómo comprobarlas

**Solo en god.** En los clientes reales, nada. Con la consola publicada:
1. `consola-crear-pedido` con el `tenant_id` de god y `name` «Prueba MCP». Responde el pedido con sus 8 pasos en `pending`.
2. `consola-pendientes`: sale el paso `order` de ese pedido.
3. `consola-estimar` con `pieces: 10` y `videos: 2`. Responde las líneas con los precios de Heraldo y la tarifa de los agentes; en la consola, el paso 1 muestra «Previsto».
4. `consola-preguntar` con `step: order`, `kind: approval` y `title` «¿Apruebas la prueba?».
   - En la consola (`/consola`), el pedido sale en «Te necesitan».
   - Llega un correo.
5. En la consola, «Aprobar». Ahora `consola-pendientes` trae la respuesta en `answers`.
6. `consola-marcar-leida` y `consola-mover-paso` con `step: order` y `state: done`. Después, `consola-estimar` otra vez debe responder 409: el previsto quedó congelado.
7. `consola-registrar-corrida` con `step: order`, `usd: 1.5` y `source: launcher`. En la consola, la cabecera del pedido suma ese gasto.
8. `consola-senales`: responde las listas vacías y los lineamientos de la marca, si el pedido tiene una.
9. `consola-mover-paso` con `step: delivery` y `state: done`. Debe responder 409 y decir qué pasos faltan: un agente no entrega un pedido sin terminar.
10. En la consola, menú «Pedido» → «Cancelar el pedido».

Si algo no responde como dice aquí, mándanos la respuesta completa de la API.
