# Prompt para agente: Crear proyecto MCP para métricas multi-tenant

## Contexto

Tengo un proyecto Laravel 12 multi-tenant (usando `stancl/tenancy`) llamado **Zalo** que ya tiene una API REST expuesta. Necesito crear un **proyecto Laravel nuevo y separado** que implemente un servidor MCP usando `laravel/mcp` (https://laravel.com/docs/13.x/mcp#main-content), el cual consumirá esa API para exponer métricas de uso a agentes de IA.

---

## Proyecto existente (Zalo API)

**Base URL local:** `http://localhost:8000`  
**Autenticación:** Bearer token con Laravel Sanctum (header `Authorization: Bearer {token}`)

### Endpoints disponibles

#### `GET /api/v1/tenants`
Lista todos los tenants del sistema.

**Respuesta:**
```json
{
  "data": [
    {
      "id": "uuid-del-tenant",
      "name": "Nombre del tenant",
      "email": "email@tenant.com",
      "domains": ["dominio.localhost"],
      "created_at": "2026-01-01T00:00:00-04:00"
    }
  ]
}
```

---

#### `GET /api/v1/tenants/{tenantId}/accounts`
Lista todas las cuentas dentro de un tenant específico.

**Respuesta:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Nombre cuenta",
      "status": "active",
      "created_at": "2026-01-01T00:00:00-04:00",
      "users_count": 3
    }
  ]
}
```

---

#### `GET /api/v1/tenants/{tenantId}/accounts/{accountId}/usage`
Retorna el resumen de uso (métricas) de una cuenta dentro de un tenant.

**Query params opcionales:**
- `date_from` (formato `Y-m-d`, ej: `2026-01-01`)
- `date_to` (formato `Y-m-d`, ej: `2026-03-31`)

Si no se envían fechas, retorna todo el histórico.

**Respuesta:**
```json
{
  "data": {
    "account_id": 1,
    "account_name": "Nombre cuenta",
    "date_from": "2026-01-01",
    "date_to": "2026-03-31",
    "total_records": 42,
    "total_cost_real_usd": 0.0234,
    "total_cost_final_usd": 0.0312,
    "total_revenue_usd": 0.0078,
    "by_tool": [
      {
        "tool": "Genesis",
        "count": 10,
        "total_cost_usd": 0.012
      },
      {
        "tool": "Chat",
        "count": 32,
        "total_cost_usd": 0.0192
      }
    ]
  }
}
```

---

## Lo que necesito que hagas

Crear un **proyecto Laravel nuevo** (vacío, `laravel new zalo-mcp`) con lo siguiente:

### 1. Instalar dependencias

```bash
composer require laravel/mcp
php artisan vendor:publish --tag=ai-routes
```

### 2. Variables de entorno (`.env`)

```env
ZALO_API_URL=http://localhost:8000
ZALO_API_TOKEN=TOKEN_SANCTUM_AQUI
```

### 3. Configuración en `config/services.php`

Agregar:
```php
'zalo_api' => [
    'url'   => env('ZALO_API_URL'),
    'token' => env('ZALO_API_TOKEN'),
],
```

### 4. Crear un servicio HTTP reutilizable

`app/Services/ZaloApiService.php`

Este servicio debe:
- Usar `Illuminate\Support\Facades\Http` para todas las llamadas
- Leer URL y token desde `config('services.zalo_api')`
- Tener métodos:
  - `getTenants(): array`
  - `getAccounts(string $tenantId): array`
  - `getAccountUsage(string $tenantId, string|int $accountId, ?string $dateFrom = null, ?string $dateTo = null): array`
- Lanzar una excepción o retornar error estructurado si el HTTP response falla

### 5. Crear el servidor MCP

`app/Mcp/Servers/ZaloMetricsServer.php`

Usando `php artisan make:mcp-server ZaloMetricsServer`

- Nombre: `Zalo Metrics Server`
- Versión: `1.0.0`
- Instructions: describir que este servidor permite a agentes de IA consultar métricas de uso de la plataforma Zalo, incluyendo tenants, cuentas y consumo por herramienta.

### 6. Crear los Tools

#### `app/Mcp/Tools/GetTenantsTool.php`
- Descripción: Lista todos los tenants del sistema Zalo.
- Sin parámetros de entrada.
- Retorna `Response::structured($data)` con la lista de tenants.
- Anotaciones: `#[IsReadOnly]`, `#[IsIdempotent]`

#### `app/Mcp/Tools/GetAccountsTool.php`
- Descripción: Lista todas las cuentas dentro de un tenant específico de Zalo.
- Parámetro de entrada:
  - `tenant_id` (string, required): UUID del tenant.
- Retorna `Response::structured($data)` con la lista de cuentas.
- Anotaciones: `#[IsReadOnly]`, `#[IsIdempotent]`

#### `app/Mcp/Tools/GetAccountUsageTool.php`
- Descripción: Retorna el resumen de métricas de uso de una cuenta en un tenant. Incluye total de registros, costo real, costo final, margen de ganancia y desglose por herramienta. Los filtros de fecha son opcionales.
- Parámetros de entrada:
  - `tenant_id` (string, required): UUID del tenant.
  - `account_id` (string, required): ID de la cuenta.
  - `date_from` (string, optional): Fecha inicio formato `Y-m-d`. Ej: `2026-01-01`.
  - `date_to` (string, optional): Fecha fin formato `Y-m-d`. Ej: `2026-03-31`.
- Retorna `Response::structured($data)` con las métricas.
- Anotaciones: `#[IsReadOnly]`, `#[IsIdempotent]`

### 7. Registrar los tools en el servidor

En `ZaloMetricsServer.php`, registrar los 3 tools en la propiedad `$tools`.

### 8. Registrar el servidor en `routes/ai.php`

**Modo local** (para desarrollo con Cursor/Claude Desktop):
```php
Mcp::local('zalo-metrics', ZaloMetricsServer::class);
```

**Modo web** (para producción, comentado por ahora):
```php
// Mcp::web('/mcp/metrics', ZaloMetricsServer::class)
//     ->middleware(['auth:sanctum', 'throttle:mcp']);
```

---

## Estructura final esperada del proyecto

```
zalo-mcp/
├── app/
│   ├── Mcp/
│   │   ├── Servers/
│   │   │   └── ZaloMetricsServer.php
│   │   └── Tools/
│   │       ├── GetTenantsTool.php
│   │       ├── GetAccountsTool.php
│   │       └── GetAccountUsageTool.php
│   └── Services/
│       └── ZaloApiService.php
├── config/
│   └── services.php  ← agregar sección zalo_api
├── routes/
│   └── ai.php        ← registrar el servidor local
└── .env              ← ZALO_API_URL y ZALO_API_TOKEN
```

---

## Cómo probarlo con MCP Inspector

```bash
php artisan mcp:inspector zalo-metrics
```

---

## Notas importantes

- Los Tools deben inyectar `ZaloApiService` vía constructor (dependency injection).
- Manejar errores HTTP: si la API devuelve 404 o 500, el tool debe retornar `Response::error('mensaje descriptivo')`.
- Los `Response::structured()` permiten que el agente de IA parsee el JSON directamente.
- No hay lógica de base de datos en este proyecto: todo es HTTP al proyecto Zalo.
- Usar `#[IsReadOnly]` y `#[IsIdempotent]` en todos los tools porque solo leen datos, no modifican nada.
