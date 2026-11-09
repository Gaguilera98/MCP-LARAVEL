# Las herramientas, una por una

Todas las rutas están en producción desde la versión 21 de Heraldo (7-nov-2026). Cuelgan de:

`https://heraldo-x.co/api/v1/tenants/{tenant_id}/campaigns/{campaign_id}/`

Las definiciones listas para cargar, con sus esquemas, están en `herramientas.json`. Aquí está lo que hace falta para entenderlas: qué responden, sus errores y en qué orden se usan.

## Lo más importante: que el agente vea las imágenes

Hay dos clases de enlaces a imágenes:
- Las hojas de contactos devuelven `download_url`: una sola imagen JPEG con hasta 24 fotos o piezas, numeradas.
- `plan-produccion` y `listar-piezas` traen `preview_url`: una foto o una pieza sola.

Los dos son enlaces firmados. Se abren con un `GET` sin llave ni sesión y vencen a las 24 h. El agente no siempre puede abrir un enlace por su cuenta. Por eso:
- **`hoja-fotos` y `hoja-piezas`** llaman a la API, abren `download_url` y devuelven dos cosas: el JSON (con `tiles`) y la imagen como contenido de imagen del MCP (`type: image`, `mimeType: image/jpeg`, en base64).
- **`ver-imagen`** recibe un `preview_url` y devuelve la imagen del mismo modo. Sus fotos y piezas salen en PNG.

**Tamaños medidos:**
- una hoja de contactos con 24 piezas reales, unos 400 KB, medida en local;
- en producción, una foto pesa unos 140 KB y una pieza alrededor de 1,2 MB.

**Para que `ver-imagen` no abra cualquier cosa,** que solo acepte enlaces `https://` de un subdominio de `heraldo-x.co` cuya ruta tenga `/signed-preview` o `/contact-sheet/`.

**Si el enlace falla:**
- un enlace vencido o alterado da 403;
- uno que no existe da 404.

En los dos casos, el agente vuelve a pedir `plan-produccion`, `listar-piezas` o la hoja, que traen enlaces nuevos.

## El orden de uso en una campaña

1. `crear-campana`, desde la hoja (ya existe).
2. `ver-campana` y después `configurar-campana`: concepto, intención, cuadros con IA, «Foto que usa» y «La foto llega en».
3. Las fotos, por uno de dos caminos:
   - si ya existen en otra campaña: `traer-fotos`;
   - si no: `subir-referencia-santo`, después `configurar-santo` y `generar-fotos-base`, consultando `estado-produccion` hasta que terminen.
4. `hoja-fotos`, para revisarlas.
5. `usar-columna-base` y `pedir-adaptaciones` (ya existen), y otra vez `hoja-fotos`.
6. `generar-copys`, consultando `estado-copys-ia`, y después `elegir-copys`.
7. Figma: `enlace-plugin-figma`. La persona corre el plugin y después va `importar-figma` (ya existen).
8. `hoja-piezas`, y aprobar o rechazar con `revisar-piezas` usando los `id` de `tiles`.
9. Los textos, los videos y el ZIP, como hoy.

**La campaña siguiente:** `duplicar-campana`, después `traer-fotos` desde la anterior, y solo se cambia lo que cambia.

---

## 1. `traer-fotos` · `POST production/photos/copy-from`

Copia a esta campaña las fotos de otra campaña del mismo cliente. Cada fila toma la foto de la fila con el mismo producto, variación, ángulo y número de foto. No gasta.

| Parámetro | Tipo | Obligatorio | Qué es |
|---|---|---|---|
| `source_campaign_id` | entero | Sí | La campaña de donde salen las fotos |
| `source_ratio` | texto | Sí | La columna de origen, como sale en `plan-produccion` de esa campaña («9:16») |
| `target_ratio` | texto | Sí | La columna de destino en esta campaña |
| `replace` | booleano | No (false) | Pisar las casillas que ya tienen foto |
| `dry_run` | booleano | No (false) | Solo mostrar qué haría |

- **Respuesta:** 201 si copió alguna foto; 200 si no copió ninguna o fue `dry_run`.
  - Los totales: `matched`, `unmatched`, `already_filled`, `busy`, `to_copy`, `copied` y `failed`.
  - `warning`, un aviso para el agente.
  - `items`: por fila, `strategy_id`, `image_index`, producto, variación, ángulo, `source_strategy_id`, `status` y `note`.
- **Errores:**
  - 404 si la campaña de origen no es de ese cliente;
  - 422 si alguna proporción no es una columna del plan.

## 2. `subir-referencia-santo` · `POST production/santo/reference`

Sube una vez la imagen de referencia de Santo, por ejemplo el producto o la pantalla del teléfono. La ruta que devuelve se usa en `configurar-santo`.

- **Entrada para la API:** multipart, campo `photo`, JPG, PNG o WebP de hasta 10 MB.
- **En el MCP:** como `subir-foto`, con `foto_url` o `foto_base64`.
- **Respuesta:** 201 `{ "path": "campaigns/{id}/source/…" }`.

## 3. `configurar-santo` · `PUT production/santo`

Es el «Guardar configuración» del modal de Santo. No genera nada.

| Parámetro | Tipo | Qué es |
|---|---|---|
| `base_ratio` | texto, opcional | La columna base («9:16») |
| `all` | objeto, opcional | Para todas las filas y ángulos: `mode`, `model`, `prompt`, `reference` y `talent_id` |
| `rows` | lista, opcional | Por fila: `strategy_id` e `image_index` (de `plan-produccion`), con los mismos campos. Manda sobre `all` en esa fila |

- **`mode`:**
  - `disenador_grafico` (Diseñador) pide `reference` y `prompt`;
  - `director_de_arte` (Director) pide `talent_id` y `prompt`.

  La API no lista los talentos, así que en la práctica el agente usa Diseñador.
- **`model`, los 8 de Santo:**
  - `google/nano-banana-2.1`, `google/nano-banana-2` y `google/nano-banana-pro`;
  - `openai/gpt-image-2.5-sunburst` y `openai/gpt-image-2.5-flare`;
  - `bytedance/seedream-5-pro`, solo con Director;
  - `black-forest-labs/flux-2-pro`, solo con Diseñador;
  - `black-forest-labs/flux-2-max`.
- **`reference`:** el `path` de `subir-referencia-santo`. Si no es de esta campaña, 422.
- Lo que no se manda queda como estaba.

**Respuesta 200:**
- `configured` y `base_ratio`;
- `columns`: `ratio`, `santo_ratio` e `is_base`;
- `rows`: por fila, `mode`, `model`, `prompt`, `reference`, `talent_id`, `aspect_requested` y `aspect_sent`;
- `warnings`. Por ejemplo: «El modelo openai/gpt-image-2.5-sunburst no acepta 4:5: Santo generará esas fotos base en 1:1 (2 filas)». **Que el MCP los muestre tal cual.**

**Errores:** 422 con los mismos mensajes del modal (falta el prompt, falta la imagen, etc.).

## 4. `generar-fotos-base` · `POST production/santo/generate` · gasta

Pide a Santo las fotos base de la columna base.

| Parámetro | Tipo | Qué es |
|---|---|---|
| `max_usd` | número | Tope de gasto |
| `only_pending` | booleano, opcional | Solo las casillas sin foto |

**Respuesta 202:** `{ queued, mode, images, estimate_usd }`. El avance se sigue en `estado-produccion`.

**Errores (en ninguno se pide nada):**
- **422** sin `max_usd`, con `estimate_usd` y `santo_balance_usd`.
- **409** si:
  - lo estimado supera `max_usd`;
  - el saldo de Santo no alcanza: el mensaje trae el saldo y lo estimado;
  - falta configurar Santo;
  - ya hay un pedido en cola;
  - ya hay fotos base y no se mandó `only_pending`.

## 5. `generar-copys` · `POST copies/generate` · gasta muy poco

La IA escribe opciones para los cuadros marcados con IA. Una campaña chica cuesta menos de US$ 0,01.

| Parámetro | Tipo | Qué es |
|---|---|---|
| `max_usd` | número | Tope de gasto |
| `concept` | texto, opcional | El concepto. Si no viene, usa el guardado en «Configurar concepto» |
| `notes` | texto, opcional | Notas adicionales: trato, largos, reglas. Hasta 2.000 caracteres |

Si se mandan `concept` o `notes`, quedan guardados como en «Configurar concepto».

- **Respuesta 202:** `{ queued, texts, estimate_usd }`.
- **Errores:**
  - 422 sin `max_usd` (con `estimate_usd`) o sin concepto;
  - 409 si:
    - ningún cuadro está marcado con IA (se marcan con `configurar-campana`);
    - ya hay una generación en curso;
    - la anterior quedó atascada (se marca como fallida y hay que volver a pedirla);
    - lo estimado supera el tope.

## 6. `estado-copys-ia` · `GET copies/generate`

Muestra el avance y las opciones. Se consulta hasta que `status` sea `completed`.

**Respuesta 200:**
- `status`, `error`, `stale`, `has_concept`, `requested_at` y `generated_at`;
- `texts`: por cuadro, `key`, `product`, `variation`, `angle`, `message`, `slot`, `max_length`, `options` (desde 0), `selected` y `text` (lo ya guardado).

## 7. `elegir-copys` · `POST copies/generate/apply`

Guarda las opciones elegidas. Después, `listar-copys` ya trae los textos.

| Parámetro | Tipo | Qué es |
|---|---|---|
| `choices` | objeto, opcional | `{ key: índice }`, desde 0. Sin `choices`, queda la primera opción de cada cuadro |

- **Respuesta 200:** `{ saved, over_max_length, completion }`. `over_max_length` lista las opciones guardadas que se pasan del largo. **Que el MCP lo muestre:** esas se acortan con `guardar-copys`.
- **Errores:**
  - 409 si no hay copys generados o si todavía se generan;
  - 422 si una clave o un índice no existen.

## 8. `ver-campana` · `GET setup`

Muestra lo que se ajusta en el Constructor.

**Respuesta 200:**
- `name`, `description` (el concepto) e `intention`.
- `copy_slots`: `[{ key, name, max_length, generate_with_ai }]`.
- `formats`, uno por formato, con:
  - `format_id`, `name`, `platform`, `width_px`, `height_px`, `size` y `ratio`;
  - `frames` (los nombres de marco en Figma, como `c1_f4`) e `image_layer`;
  - `photo_source`, `photo_source_name` y `photo_source_options` (`format_id`, `name`, `ratio`);
  - `santo_ratio`, `catalog_santo_ratio`, `santo_ratio_editable` y `santo_ratio_options`.
- `warnings`: los avisos del paso 4.

## 9. `configurar-campana` · `PUT setup`

Es el «Guardar campaña» del Constructor, con un cambio parcial: lo que no se manda queda igual. Guarda con las mismas validaciones que la página.

| Parámetro | Tipo | Qué es |
|---|---|---|
| `description` | texto | El concepto de la campaña, hasta 20.000 caracteres |
| `intention` | texto | La intención, hasta 255 caracteres |
| `copy_slots` | lista | `[{ key, max_length?, generate_with_ai? }]` |
| `photo_sources` | objeto | `{ format_id: id del formato dueño de la foto, o null para que use la suya }` |
| `santo_ratios` | objeto | `{ format_id: «4:5», o "" para volver al catálogo }` |

**Lo que hay que saber:**
- `copy_slots` solo cambia cuadros que ya existen: no agrega, quita ni renombra.
- `santo_ratios` solo vale en un formato que usa su propia foto (`santo_ratio_editable` en true).
- **`null` en `description` o `intention` los borra.** Para no tocarlos, no se mandan.

**Respuesta 200:** lo mismo que `ver-campana`.

**Errores:**
- 422 con los mismos mensajes de la página (largo, intención), o si se elige un formato o una proporción que no están entre las opciones;
- 409 si la campaña guardada no pasa las reglas de la página.

## 10. `hoja-fotos` · `GET production/plan/contact-sheet`

Una sola imagen con hasta 24 fotos de una columna del plan, numeradas desde 1 en cada página.

| Parámetro | Tipo | Qué es |
|---|---|---|
| `ratio` | texto, opcional | La columna. Sin él, la base |
| `page` | entero, opcional | Desde 1, de a 24 |

- **Respuesta 200:** `{ download_url, expires_at, page, pages, total, tiles }`. Cada elemento de `tiles` es `{ n, strategy_id, image_index, product, variation, angle, ratio }`.
- **Errores:**
  - 409 si esa columna no tiene fotos;
  - 422 si la proporción no es una columna (trae `ratios`) o la página no existe (trae `pages`).
- **En el MCP:** devolver también la imagen de `download_url`.

## 11. `hoja-piezas` · `GET pieces/contact-sheet`

Es lo mismo para las piezas importadas de Figma, en el mismo orden que `listar-piezas`.

- **Entrada:** acepta los filtros de `listar-piezas` (`filter[review|platform|format|product]`) y `page`.
- **Respuesta:** cada elemento de `tiles` es `{ n, id, name, platform, format, product, review_status }`.
- **Errores:** 409 si no hay piezas con imagen en ese filtro.
- **Uso:** el agente mira la hoja y aprueba o rechaza con `revisar-piezas` usando los `id`.

## 12. `duplicar-campana` · `POST duplicate`

Es el «Duplicar» de Mis campañas, con el mismo servicio y el mismo permiso.
- **Copia la estructura:** canales, formatos, filas, mensajes, cuadros, «Foto que usa» y «La foto llega en».
- **Copia los textos**, salvo que se pida lo contrario.
- **Nunca copia fotos, piezas ni videos.**
- La copia queda en borrador.

| Parámetro | Tipo | Qué es |
|---|---|---|
| `name` | texto, opcional | Sin nombre, «… (copia)» |
| `copy_texts` | booleano, opcional (true) | Copiar los textos |

- **Respuesta 201:** `{ campaign: { id, name, status }, source_campaign_id, copy_texts, texts_copied }`.
- **Errores:**
  - 403 si el usuario no tiene permiso para duplicar;
  - 404 si la campaña no existe.

## 13. `ver-imagen` · sin ruta de la API

Recibe un `preview_url` de `plan-produccion` o de `listar-piezas` y devuelve la imagen como contenido de imagen. No usa la llave. Cómo validar el enlace y qué hacer si falla está arriba, en «Lo más importante».

---

## Las 4 que ya existen: solo cambia la descripción

La API ya devuelve estos campos y el MCP ya los pasa. Basta con agregar al final de cada descripción la frase de `herramientas.json` (`agregar_a_la_descripcion`), para que el agente sepa que están.

| Herramienta | Lo nuevo en la respuesta |
|---|---|
| `plan-produccion` | En cada casilla, `preview_url`. Es null si la casilla no tiene foto |
| `listar-piezas` | En cada pieza, `preview_url` |
| `estado-produccion` | En `cost`, `santo_balance_usd`: el «Saldo disponible» de la página. Es null si Santo no lo informa |
| `pedir-adaptaciones` | El 422 y el 409 traen `santo_balance_usd`. Hay un 409 nuevo: si el saldo de Santo no alcanza para lo estimado, no se pide nada, y así no queda un lote a medias |

Ningún campo que ya existía cambió de nombre ni de valor.
