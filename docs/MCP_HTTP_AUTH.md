# Auth de entrada (HTTP MCP)

Las rutas `POST /mcp/zalo-tenants` y `POST /mcp/santox-tenants` exigen un **Bearer token de entrada** emitido en Filament (`/admin` → **API Keys**).

## Cómo emitir una key

1. Entrá a `/admin` con un usuario del panel.
2. **MCP → API Keys → Nueva**.
3. Elegí el **Server MCP** (`Zalo Tenants` o `Santox Tenants`).
4. Copiá el secreto **una sola vez** del modal.

Cada key tiene una sola ability (= id del server). No sirve para el otro endpoint.

## Cliente HTTP (Cursor / Claude)

```json
{
  "mcpServers": {
    "zalo-tenants": {
      "url": "http://localhost:8000/mcp/zalo-tenants",
      "headers": {
        "Authorization": "Bearer 1|TU_KEY_DE_ENTRADA"
      }
    }
  }
}
```

Sin header → `401`. Key de Zalo contra Santox (o al revés) → `403`.

## Stdio local

`php artisan mcp:start zalo-tenants` (y santox) **no** usa Bearer de entrada: es proceso local de confianza.

## Tokens de salida (`.env`)

`ZALO_API_TOKEN` y `SANTOX_API_TOKEN` **no** son estas keys. Siguen siendo el Bearer hacia las APIs remotas Zalo/Santox. Son capas distintas:

| Capa | Quién | Dónde |
|------|--------|--------|
| Entrada | Cliente → MCP HTTP | Filament API Keys |
| Salida | MCP → API Zalo/Santox | `.env` |
