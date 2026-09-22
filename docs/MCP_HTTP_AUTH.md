# Auth de entrada (HTTP MCP)

Las rutas `POST /mcp/zalo-tenants`, `POST /mcp/santox-tenants` y `POST /mcp/godai-mailing` aceptan **dos** formas de autenticación en el mismo endpoint:

| Cliente | Mecanismo | Cómo |
|---------|-----------|------|
| **Cursor** (y similares) | Sanctum API Key | Header `Authorization: Bearer …` emitido en Filament |
| **Claude** (Connectors / Desktop) | OAuth 2.1 (Passport) | Flujo Log in → Aprobar en el navegador |

Los tokens de salida hacia las APIs remotas (`ZALO_API_TOKEN`, `SANTOX_API_TOKEN`, `MAILING_API_TOKEN`) **no cambian**.

## Sanctum (Cursor)

1. Entrá a `/admin` con un usuario del panel.
2. **MCP → API Keys → Nueva**.
3. Elegí el **Server MCP** (`Zalo Tenants`, `Santox Tenants` o `Godai Mailing`).
4. Copiá el secreto **una sola vez** del modal.

Cada key tiene una sola ability (= id del server). No sirve para otro endpoint → `403`.

```json
{
  "mcpServers": {
    "godai-mailing": {
      "url": "https://mcp.god-ai.co/mcp/godai-mailing",
      "headers": {
        "Authorization": "Bearer 1|TU_KEY_DE_ENTRADA"
      }
    }
  }
}
```

Sin header → `401`. Key de un server contra otro → `403`.

## OAuth (Claude)

1. Asegurá `APP_URL` al host público del MCP (ej. `https://mcp.god-ai.co`).
2. Tenés al menos un usuario en `/admin` (login web + pantalla **Aprobar**).
3. En Claude, agregá el conector con la **misma URL** del server (ej. `https://mcp.god-ai.co/mcp/godai-mailing`).
4. Claude abre discovery (`.well-known/…`), registro dinámico de cliente, **Log in**, y **Authorize**.
5. No hace falta API Key Sanctum para Claude.

Discovery / OAuth viven en:

- `GET /.well-known/oauth-authorization-server`
- `GET /.well-known/oauth-protected-resource/{path?}`
- `GET/POST /oauth/authorize`, `POST /oauth/token`, `POST /oauth/register`

Con OAuth, un usuario autenticado puede usar **cualquier** server MCP (no se aplican abilities Sanctum). Los scopes OAuth (`mcp:use`) son solo puente al User.

## Stdio local

`php artisan mcp:start zalo-tenants` (santox / godai-mailing) **no** usa Bearer ni OAuth: es proceso local de confianza.

## Tokens de salida (`.env`)

| Capa | Quién | Dónde |
|------|--------|--------|
| Entrada Sanctum | Cliente → MCP HTTP | Filament API Keys |
| Entrada OAuth | Claude → MCP HTTP | Passport (login + aprobar) |
| Salida | MCP → API Zalo/Santox/Mailing | `.env` |

## Deploy

Tras desplegar: `php artisan migrate`, `php artisan passport:keys` (si aún no hay keys en `storage/`), y `APP_URL` correcto.
