# Auth de entrada (HTTP MCP)

Las rutas `POST /mcp/santox-tenants` y `POST /mcp/godai-mailing` exigen un **Bearer token de entrada** emitido en Filament (`/admin` → **API Keys**).

**Temporal:** `POST /mcp/zalo-tenants` está **sin** `auth:sanctum` / `mcp.server` (solo `ForceJsonAccept` + throttle) para probar Claude Desktop/Connectors. Restaurar en `routes/ai.php` cuando definamos OAuth o headers.

## Cómo emitir una key

1. Entrá a `/admin` con un usuario del panel.
2. **MCP → API Keys → Nueva**.
3. Elegí el **Server MCP** (`Zalo Tenants`, `Santox Tenants` o `Godai Mailing`).
4. Copiá el secreto **una sola vez** del modal.

Cada key tiene una sola ability (= id del server). No sirve para otro endpoint.

## Cliente HTTP (Cursor / Claude)

```json
{
  "mcpServers": {
    "godai-mailing": {
      "url": "http://localhost:8000/mcp/godai-mailing",
      "headers": {
        "Authorization": "Bearer 1|TU_KEY_DE_ENTRADA"
      }
    }
  }
}
```

Sin header → `401`. Key de un server contra otro → `403`.

## Stdio local

`php artisan mcp:start zalo-tenants` (santox / godai-mailing) **no** usa Bearer de entrada: es proceso local de confianza.

## Tokens de salida (`.env`)

`ZALO_API_TOKEN`, `SANTOX_API_TOKEN` y `MAILING_API_TOKEN` **no** son estas keys. Siguen siendo el Bearer hacia las APIs remotas. Son capas distintas:

| Capa | Quién | Dónde |
|------|--------|--------|
| Entrada | Cliente → MCP HTTP | Filament API Keys |
| Salida | MCP → API Zalo/Santox/Mailing | `.env` |
