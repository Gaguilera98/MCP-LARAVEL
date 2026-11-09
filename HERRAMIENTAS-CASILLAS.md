# MCP de Heraldo: 4 herramientas para el paso 2, casilla por casilla

**En qué mejora Heraldo:** el agente arregla una foto sola, sin una persona y sin rehacer todas. Cuando en la revisión se rechazan 2 piezas por la foto, hoy hay que entrar a la página y tocar «Regenerar» en esas casillas, o regenerar todo y pagarlo. Con estas herramientas, el agente:
- rehace la foto de una casilla (la base o la adaptación);
- quita una foto que no sirve;
- elige hacia dónde crece la foto en las adaptaciones de una columna, y rehace la que haga falta.

**Cuándo:** cuando el dev de Heraldo publique `casillas.patch` (va en `paquete-dev-consola`). Antes, estas rutas responden 404.

Cuelgan de la misma base que las de hoy: `https://heraldo-x.co/api/v1/tenants/{tenant_id}/campaigns/{campaign_id}/`. Se arman igual: la misma llave con «Puede operar», `tenant_id` y `campaign_id` como siempre, `data` tal cual y los errores con su `message` y los campos que lo acompañan.

| # | Herramienta | Ruta | ¿Gasta? |
|---|---|---|---|
| 1 | `regenerar-casilla` | `POST production/cells/regenerate` | **Sí** |
| 2 | `quitar-foto` | `POST production/cells/remove` | No |
| 3 | `ver-direcciones` | `GET production/adaptation-directions` | No |
| 4 | `elegir-direccion` | `PUT production/adaptation-directions` | No |

Y una frase más en la descripción de `plan-produccion` (está en `herramientas-casillas.json`, en `herramientas_que_cambian`).

## La casilla

Las tres primeras reciben la casilla como sale en `plan-produccion`: la fila con `strategy_id` e `image_index` (en `rows`) y la columna con `ratio` («4:5», «9:16»). Una columna o una fila que no existe responde 422; la de la columna trae `ratios` con las que sí.

## 1. `regenerar-casilla`

Hace lo mismo que «Regenerar» de una casilla en la página:
- **en la columna base,** pide a Santo la foto base de esa fila;
- **en otra columna,** pide la adaptación desde la foto base;
- **si la casilla está vacía,** la genera.

**No pisa lo que subió una persona:** si la foto tiene origen `manual`, responde 409 y dice cómo cambiarla (`subir-foto` o `quitar-foto`).

**Cuida lo mismo que la página para mostrar el botón,** con un 409 que dice qué hacer:
- la campaña tiene que tener un lote de Santo (si no, primero `generar-fotos-base` o `pedir-adaptaciones`);
- no puede haber nada generándose en la campaña, ni un pedido en cola;
- en una adaptación, la foto base de la fila tiene que estar lista;
- dos pedidos seguidos a la misma campaña: el segundo responde 409 y se reintenta en un momento.

**Gasta,** como `generar-fotos-base`:
- sin `max_usd`, responde 422 con `estimate_usd` y `santo_balance_usd`, sin pedir nada. La foto base se estima con el modelo más caro de la campaña, para que el tope nunca quede corto;
- responde 409 sin pedir nada si lo estimado supera `max_usd` o el saldo de Santo.

**Respuesta 202:** `kind` (`base` o `adaptation`), `action` (`regenerate` o `generate`), `estimate_usd` y el estado de la casilla. El avance se sigue con `estado-produccion`; la foto nueva se ve con `plan-produccion` y `ver-imagen`.

**Otros 409:** la casilla todavía no terminó, la producción está finalizada o lo que responda Santo.

## 2. `quitar-foto`

Borra la foto de la casilla, la haya subido una persona o la haya hecho Santo. No gasta. Responde `removed: true`, `removed_origin` (`manual` o `ai`) y `removed_stage` (`base` o `adaptation`). 404 si la casilla no tiene foto; 409 si se está generando o la producción está finalizada.

## 3. `ver-direcciones`

Por cada columna que no es la base:
- `axis`: `vertical` si al adaptar crece el alto, `horizontal` si crece el ancho, `none` si tiene la misma forma;
- `direction`: `top`, `bottom`, `left` o `right`; `null` es centrada;
- `choices`: los lados que tienen efecto en esa columna.

## 4. `elegir-direccion`

`ratio` y `direction` (`top`, `bottom`, `left`, `right` o `center`; sin `direction`, centrada).
- `top` agrega arriba y deja la foto abajo; `bottom`, al revés; `left` deja la foto a la derecha; `right`, a la izquierda.
- Solo sirve un lado de `choices`. Otro responde 422 con `choices`.
- La columna base no se adapta: 422.
- **Vale para las adaptaciones que se pidan después.** Para cambiar una que ya está, después va `regenerar-casilla` en esa casilla.

Responde lo mismo que `ver-direcciones`, con el cambio.

## El orden de uso para arreglar una foto rechazada

1. `hoja-piezas` o `listar-piezas` con `review: rejected`: qué piezas se rechazaron y su nota.
2. `plan-produccion`: la casilla de esa pieza (`strategy_id`, `image_index`, `ratio`).
3. Si el problema es hacia dónde creció la foto: `ver-direcciones` y `elegir-direccion`.
4. `regenerar-casilla` con `max_usd`. Seguir con `estado-produccion`.
5. `hoja-fotos` para mirarla. Después, la persona corre «Rehacer y enviar» en el plugin solo en esas piezas.

## Cómo comprobarlas

**Solo en god.** En los clientes reales, nada. Con el parche publicado, en una campaña de prueba de god con fotos:
1. `ver-direcciones`: responde las columnas que no son la base.
2. `elegir-direccion` con una columna y un lado de `choices`; otra vez `ver-direcciones` lo muestra. Después, `elegir-direccion` sin `direction` la deja centrada.
3. `regenerar-casilla` **sin** `max_usd`: 422 con `estimate_usd`. No gasta.
4. `quitar-foto` en una casilla de adaptación de prueba y después `plan-produccion`: la casilla queda vacía.

Lo que gasta (regenerar con `max_usd`) lo probamos nosotros.
