# MCP de Heraldo: la consola y las casillas · 29 herramientas

**En qué mejora Heraldo:** el agente trabaja un pedido entero desde la consola y arregla una foto sola, sin una persona.
- **La consola (25):** el agente ve qué paso le toca y los lineamientos de la marca; le pregunta a una persona y lee su respuesta; deja avances, capturas, el previsto y lo que costó su corrida; marca los pasos; y al entregar propone lo que se aprendió. La persona entra solo cuando el pedido la necesita, y le llega un correo.
- **Las casillas (4):** rehacer o quitar la foto de una sola casilla del paso 2 y elegir hacia dónde crece la foto en las adaptaciones. Así se arregla una pieza rechazada sin entrar a la página.

**Cuándo:** cuando el dev de Heraldo publique `paquete-dev-consola` (la consola y `casillas.patch`). Antes, estas rutas no existen. Va después de los dos agregados anteriores: `paquete-mcp-herramientas-nuevas` y el de animaciones v2.

## Qué hay que hacer en el MCP

1. **Sumar las 25 de la consola,** de `herramientas-consola.json`. Su base es otra: `https://heraldo-x.co/api/console/v1/`. El detalle, en `HERRAMIENTAS-CONSOLA.md`.
2. **Sumar las 4 de las casillas,** de `herramientas-casillas.json`. Cuelgan de la base de siempre (`…/api/v1/tenants/{tenant_id}/campaigns/{campaign_id}/`). El detalle, en `HERRAMIENTAS-CASILLAS.md`.
3. **Una frase más en la descripción de `plan-produccion`,** que está en `herramientas-casillas.json` (`herramientas_que_cambian`).

Se arman como las de hoy:
- **La misma llave,** con «Puede operar» para lo que cambia algo.
- **`data` tal cual,** y los errores con su `message` y los campos que lo acompañan.
- **Lo que gasta exige `max_usd`** (solo `regenerar-casilla`).
- **El parámetro `agente`** de las de la consola va como cabecera `X-Heraldo-Agent`, no en el cuerpo.
- **`consola-subir-archivo` y `consola-ver-archivo`** reciben y devuelven archivos como `subir-foto` y `ver-imagen`.

## Los archivos

| Archivo | Qué trae |
|---|---|
| `herramientas-consola.json` | Las 25 de la consola, listas para cargar |
| `HERRAMIENTAS-CONSOLA.md` | Cómo se arman, la tabla con sus rutas y los 10 pasos para comprobarlas |
| `herramientas-casillas.json` | Las 4 de las casillas y la frase para `plan-produccion` |
| `HERRAMIENTAS-CASILLAS.md` | Qué hace cada una, sus errores, el orden de uso para arreglar una foto y cómo comprobarlas |

## Cómo comprobarlas

**Solo en god.** En los clientes reales, nada. Los pasos están al final de cada documento. Lo que gasta lo probamos nosotros.

Si algo no responde como dicen los documentos, mándanos la respuesta completa de la API.
