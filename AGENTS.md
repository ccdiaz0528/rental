# AGENTS.md

## Stack
- Laravel 13 + Filament 5.6.1, PHP ^8.4
- Vite + Tailwind CSS 4 (`@tailwindcss/vite`)
- MySQL (Laragon local) / SQLite :memory: (tests)
- spatie/laravel-permission 7.x, spatie/laravel-activitylog
- Locale: `es` / `es_CO`, timezone: `America/Bogota`
- Session, cache, queue: all `database` driver

## Commands
- `composer setup` — install, .env, key, migrate, npm install & build
- `composer test` — `php artisan config:clear && php artisan test`
- `composer dev` — concurrently runs server, queue, pail logs, Vite
- `npm run dev` — Vite hot reload; `npm run build` — production assets
- `vendor/bin/pint` — PSR-12 format after every PHP change
- `php artisan config:clear; php artisan view:clear` — clear caches
- `Remove-Item -Recurse -Force public/build` — clear stale Vite assets (Windows)

## Verification order
1. `composer test` (15 classes: 6 Unit + 9 Feature)
2. `vendor/bin/pint`
3. `npm run build`

## Architecture

### Panel (`app/Providers/Filament/AdminPanelProvider.php`)
- `/admin`, login required, global search enabled
- primary=Blue, gray=Slate; custom Vite theme at `resources/css/filament/admin/theme.css`
- Brand: `resources/views/filament/custom-logo.blade.php` + `public/favicon.png`

### RBAC
- Roles: `admin` (unrestricted), `user` (scoped to own `user_id`)
- Seeded: `admin@example.com` / `password`, `test@example.com` / `password`
- **`BelongsToUser` concern** (`app/Concerns/`): auto-assigns `user_id = auth()->id()` on create — used by all models including Deuda
- **`HasUserScope` concern** (`app/Concerns/`): global scope filtering `user_id` for non-admin on Persona, Vehiculo, Contrato, ControlDiario (DRY, extracted from duplicated `booted()`)
- **`HasUserContext` concern**: admin can switch user context (persisted via cache, Livewire events). Used by all dashboard widgets.
- Admin-only resources: ActivityLogResource (Trazabilidad), UserResource, DeudaResource (Cartera — has `canAccess()`)
- All models use `LogsActivity` from spatie; ActivityLogResource is read-only for admins
- No Policy classes — access control via Resource `canAccess()`/`can*()` methods

### Filament Split Layout
```
Resources/{Domain}/
├── Resource.php          → delegates to Schemas/ and Tables/
├── Pages/{List,Create,Edit,View}{Domain}.php
├── Schemas/{Domain}Form.php, {Domain}Infolist.php
└── Tables/{Domain}Table.php
```

### Custom Pages (Livewire + Blade)
- **ControlSemanal**: Weekly grid (dom–sáb), cell editing via modal, 12-week sidebar
- **Reportes**: Period selector + date range + summary tables

### Models
- All models use `#[Fillable]` PHP 8 attributes (not `$fillable` property) — follow this convention
- **ControlDiario**: constants `CATEGORIA_DAÑO`, `MANTENIMIENTO`, `MULTA`, `OTRO`; implicit-delete pattern when saving defaults
- **Configuracion**: KV store with static `get(clave, default)` / `set(clave, value)`, 1h cache
- **Persona**: has `estado` field; deletion blocked only if has **active** contratos
- **Vehiculo**: deletion blocked if has contratos or controlDiarios

### Dashboard (7 widgets, by sort order)
0. SelectorUsuarioWidget — admin user-context (full-width blade)
1–3. ResumenDiario/Semanal/Mensual — neto, esperado, gastos por categoría
4. IndicadoresFlota — vehicles, drivers, adjustments, contract counts
5. AlertasVencimientos — SOAT/tecnomecánica ≤30d or expired
6. PagosRecientesWidget — last 10 control_diarios by updated_at

Stats widgets use `HasDashboardStats` trait (`money()`, `gastosPorCategoria()`) + `HasUserContext`.

### Routes
- `/` → `welcome.blade.php`
- `/admin/*` → Filament panel
- `/documento/contratos/{path}` → document viewer (auth required, ownership enforced for non-admin)

## Testing quirks
- SQLite in-memory, `RefreshDatabase`, `config:clear` required before run (`composer test` handles it)
- Tests create roles manually (`Role::create`) in setUp — no seeder dependency
- Week computation uses `Carbon::SUNDAY` as startOfWeek

## Docs
`docs/`: `arquitectura-tecnica.md`, `logica-de-negocio.md`, `modelo-de-negocio.md`, `manual-de-usuario.md`. Consult for business rules, calculations, schema details.
