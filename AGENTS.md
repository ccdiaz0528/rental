# AGENTS.md

## Stack
- Laravel 13.9.0 / PHP ^8.4, Filament 5.6.3 (`composer.lock`)
- Vite 8 + `@tailwindcss/vite` (Tailwind 4), no PostCSS
- MySQL (Laragon local) / SQLite `:memory:` (tests)
- `spatie/laravel-permission` 7.x, `spatie/laravel-activitylog`
- Locale `es` / `es_CO`, timezone `America/Bogota`
- Session, cache, queue: all `database` driver (`.env.example`)

## Commands
| Command | What it does |
|---|---|
| `composer setup` | install, `.env`, key, migrate, `npm install --ignore-scripts`, `npm run build` |
| `composer test` | `php artisan config:clear` then `php artisan test` |
| `composer dev` | runs server + `queue:listen --tries=1 --timeout=0` + pail + Vite concurrently, `--kill-others` |
| `npm run dev` | Vite hot reload |
| `npm run build` | Production assets |
| `vendor/bin/pint` | PSR-12 format (run after every PHP change) |
| `php artisan config:clear && php artisan view:clear` | Clear caches |

After `composer update`, `post-autoload-dump` runs `filament:upgrade`.

Vite inputs: `resources/css/app.css`, `resources/js/app.js`, `resources/css/filament/admin/theme.css`.

## Verification order
1. `composer test` — config:clear + phpunit (15 tests: 6 Unit + 9 Feature)
2. `vendor/bin/pint` — PSR-12 lint
3. `npm run build` — production build check

## Architecture

### Panel (`app/Providers/Filament/AdminPanelProvider.php`)
- `/admin`, login required, global search enabled
- primary=Blue, gray=Slate; custom Vite theme; brand via `resources/views/filament/custom-logo.blade.php` + `public/favicon.png`

### RBAC (no Policy classes — all via Resource `canAccess()`/`can*()`)
- Roles: `admin` (unrestricted), `user` (scoped to own `user_id`)
- Seeded: `admin@example.com` / `password`, `test@example.com` / `password`
- **`BelongsToUser`** concern: auto-assigns `user_id = auth()->id()` on `creating` — on Persona, Vehiculo, Contrato, ControlDiario, Deuda
- **`HasUserScope`** concern: global scope `where('user_id', auth()->id())` for non-admin — on Persona, Vehiculo, Contrato, ControlDiario (*not* Deuda — admin-only)
- **`HasUserContext`** concern: admin can switch user context (persisted via cache, Livewire events). Used by all dashboard widgets + ControlSemanal + Reportes.
- Admin-only resources: ActivityLogResource (Trazabilidad), UserResource (Usuarios), DeudaResource (Cartera)

### Filament Split Layout
```
Resources/{Domain}/
├── Resource.php          → delegates form/table/infolist to Schemas/ + Tables/
├── Pages/{List,Create,Edit,View}{Domain}.php
├── Schemas/{Domain}Form.php, {Domain}Infolist.php
└── Tables/{Domain}Table.php
```
Applied to: Personas, Vehiculos, Contratos, Deudas, User, ActivityLog.

### Custom Pages (Livewire + Blade)
- **ControlSemanal**: weekly grid (dom–sáb), cell editing via modal, 12-week sidebar history. Save resets to default values **delete** the record (implicit-delete pattern).
- **Reportes**: period selector + date range + summary tables.

### Models
- All models use `#[Fillable]` PHP 8 attributes (not `$fillable` property)
- User model `#[Fillable]` does NOT include `roles` (it's a relationship, not a column). CreateUser/EditUser strip it via `mutateFormDataBeforeSave/Create`
- All models use `LogsActivity` trait from spatie
- **ControlDiario**: constants `CATEGORIA_DAÑO`, `MANTENIMIENTO`, `MULTA`, `OTRO`; casts `fecha` as date, `trabajo` as boolean
- **Configuracion** (`configuraciones` table): KV store with static `get(clave, default)` / `set(clave, value)`, 1h cache
- **Persona**: deletion blocked only if has **active** contratos (`canBeDeleted()`)
- **Vehiculo**: deletion blocked if has contratos OR controlDiarios

### Dashboard (7 widgets by `$sort`)
| Sort | Widget | Description |
|---|---|---|
| 0 | SelectorUsuarioWidget | Admin user-context switcher (full-width blade) |
| 1–3 | ResumenDiario/Semanal/Mensual | Neto, esperado, gastos por categoría |
| 4 | IndicadoresFlota | Vehicles, drivers, adjustments, contract counts |
| 5 | AlertasVencimientos | SOAT/tecnomecánica ≤30d or expired |
| 6 | PagosRecientesWidget | Last 10 control_diarios by updated_at |

Stats widgets use `HasDashboardStats` trait (`money()`, `gastosPorCategoria()`) + `HasUserContext`.

### Routes
- `/` → `welcome.blade.php`; `/admin/*` → Filament panel
- `/documento/contratos/{path}` → DocumentController (auth required; non-admin ownership enforced via `user_id` match)

## Testing quirks
- SQLite in-memory, `RefreshDatabase` — `config:clear` required before run (`composer test` handles it)
- Tests create roles manually (`Role::create`) in `setUp` — no seeder dependency
- Week computation uses `Carbon::SUNDAY` as startOfWeek (ControlSemanal, ResumenSemanal, IndicadoresFlota, Reportes)

## Docs
`docs/`: `arquitectura-tecnica.md`, `logica-de-negocio.md`, `modelo-de-negocio.md`, `manual-de-usuario.md`. Consult for business rules and schema details.
