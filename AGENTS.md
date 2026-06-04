# AGENTS.md

## Stack
- Laravel 13 + Filament 5.6.1, PHP ^8.4
- Vite + Tailwind CSS 4 (`@tailwindcss/vite`)
- MySQL (Laragon local) / SQLite in-memory (tests)
- spatie/laravel-permission 7.x, spatie/laravel-activitylog
- COP (pesos colombianos)

## Commands
- `composer setup` — full install: deps, .env, key, migrate, npm install, build
- `composer test` — runs `php artisan config:clear && php artisan test` (SQLite :memory:)
- `composer dev` — Laravel serve + queue + logs + Vite concurrently (may fail on Windows)
- `npm run dev` — Vite hot reload
- `npm run build` — production Vite assets
- `vendor/bin/pint` — PSR-12 format (run after every PHP change)
- `php artisan tinker` — REPL
- `php artisan config:clear; php artisan view:clear` — clear caches
- `Remove-Item -Recurse -Force public/build` — clear stale Vite assets (Windows)
- `php artisan filament:upgrade` — runs automatically on `composer update`

## Architecture

### Panel (`app/Providers/Filament/AdminPanelProvider.php`)
- `/admin`, login required, global search
- Colors: primary=Blue, gray=Slate — `primary-*` Tailwind classes render as blue
- Vite theme: `resources/css/filament/admin/theme.css`
- Brand: `resources/views/filament/custom-logo.blade.php` + `public/favicon.png`
- Auto-discovers resources/pages/widgets from `app/Filament/`

### RBAC
- Roles: `admin` (unrestricted), `user` (scoped to own `user_id`)
- Seeded: `admin@example.com` / `password`, `test@example.com` / `password`
- Global scopes on Persona, Vehiculo, Contrato, ControlDiario filter by `user_id` for non-admin
- `BelongsToUser` trait auto-assigns `user_id = auth()->id()` on create
- `HasUserContext` trait lets admin switch user context (persisted via cache, syncs via Livewire events)
- ActivityLogResource + UserResource + DeudaResource = admin-only

### Filament Pattern (Split Layout)
```
Resources/{Domain}/
├── Resource.php          # delegating to Schemas/ and Tables/
├── Pages/{List,Create,Edit,View}{Domain}.php
├── Schemas/{Domain}Form.php, {Domain}Infolist.php
└── Tables/{Domain}Table.php
```

### Dashboard (7 widgets)
| Sort | Widget | Type | Content |
|------|--------|------|---------|
| 0 | SelectorUsuarioWidget | blade | Admin user-context selector (sort=0, full width) |
| 1 | ResumenDiario | stats | Neto, esperado, gastos by category (daño/mantenimiento/multa/otro), admin |
| 2 | ResumenSemanal | stats | Same metrics, week (dom–sáb) |
| 3 | ResumenMensual | stats | Same metrics, month to date |
| 4 | IndicadoresFlota | stats | Vehículos activos, con conductor, ajustes semana, contratos alquiler/compra |
| 5 | AlertasVencimientos | stats | SOAT/tecnomecánica por vencer ≤30d + vencidos |
| 6 | PagosRecientesWidget | table | Last 10 control diario by updated_at |

Stats widgets use `HasDashboardStats` trait (`money()`, `gastosPorCategoria()`). All widgets use `HasUserContext` for admin scope.

### Models
| Model | Table | Key Fields |
|-------|-------|------------|
| Persona | personas | nombre, cedula (unique), telefono, tipo (conductor/propietario/otro), estado (activo/inactivo), user_id |
| Vehiculo | vehiculos | placa (unique), marca, modelo, anio, color, persona_id (conductor), cuota_diaria, **administracion**, estado (activo/inactivo/mantenimiento), fecha_vencimiento_soat, fecha_vencimiento_tecnomecanico, **administrador_vehiculo**, user_id |
| Contrato | contratos | vehiculo_id, persona_id, tipo (alquiler/opcion_compra), fecha_inicio, fecha_fin, valor_diario, estado, documento (file path), user_id |
| ControlDiario | control_diarios | vehiculo_id+fecha (unique), trabajo (bool), valor_generado, gasto, **categoria_gasto** (daño/mantenimiento/multa/otro), **administracion** (per-day override), user_id |
| Deuda | deudas | persona_id, valor, user_id (admin-only resource "Cartera") |
| Configuracion | configuraciones | clave (unique, KV store) |

### Key Behaviors
- **ControlDiario defaults**: No DB record = assumes `trabajo=true, valor=cuota_diaria, admin=vehiculo.administracion`. Saving default values deletes the record (returns to implicit state).
- **Vehiculo deletion**: Blocked if has contratos or controlDiarios (`canBeDeleted()` / `deletionBlockers()`).
- **Persona deletion**: Blocked if has active contratos.
- **ControlSemanal page** (`app/Filament/Pages/ControlSemanal.php` + `resources/views/filament/pages/control-semanal.blade.php`): Weekly grid (dom–sáb), cell editing via modal, 12-week history sidebar. Cell colors are inline Tailwind strings (danger=no trabajo, warning=has gasto, gray=default/changed).
- **Contrato documentos**: Stored on `local` disk, served via `/documento/contratos/{path}` route (streamed, MIME detection, ownership check for non-admin).
- **Cache**: Dashboard widgets cache 60-300s. Configuracion KV cached 1h. Cache keys include user_id + admin context.

### Routes
- `/` → `welcome.blade.php`
- `/admin/*` → Filament panel
- `/documento/contratos/{path}` → document viewer (auth required, ownership enforced)

## Testing
- SQLite in-memory, `RefreshDatabase`, `config:clear` required before run
- 15 test files: 6 Unit + 9 Feature
- Key areas: RBAC isolation, stats calculations, CRUD operations, control semanal defaults, document auth

## Verification
1. `composer test`
2. `vendor/bin/pint`
3. `npm run build`
