# AGENTS.md

## Stack
- Laravel 13 + Filament 5.6.1, PHP 8.3
- Vite + Tailwind CSS 4 (`@tailwindcss/vite`)
- MySQL (Laragon local) / SQLite (tests)
- spatie/laravel-permission 7.x for RBAC
- Moneda: COP (pesos colombianos)

## Commands
- `composer setup` — installs deps, key, migrates, npm install, builds assets
- `composer test` — `php artisan config:clear` + `php artisan test` (SQLite in-memory)
- `composer dev` — Laravel serve + queue + logs + Vite concurrently (may fail on Windows)
- `npm run dev` — Vite hot reload
- `npm run build` — production assets
- `vendor/bin/pint` — format code (run after every PHP change)
- `php artisan tinker` — REPL
- Dev server: http://rental-manager.test (Laragon)
- Clear stale assets: `Remove-Item -Recurse -Force public/css` (Windows)
- Clear caches: `php artisan config:clear; php artisan view:clear`

## Architecture

### Panel (AdminPanelProvider)
- Panel `/admin`, login required, global search
- Primary color: `Color::Blue` — **all `primary-*` Tailwind classes render as blue**
- Gray color: `Color::Slate` — cool-toned gray, may appear slightly blue-ish
- Theme: `resources/css/filament/admin/theme.css` (registered via `->viteTheme()`)
- Brand: `resources/views/filament/custom-logo.blade.php` + `public/favicon.png`
- Auto-discovers resources/pages/widgets from `app/Filament/`

### RBAC (spatie/laravel-permission)
- Roles: `admin` (sees all data), `user` (scoped to own `user_id`)
- User model has `hasMany` to Persona, Vehiculo, Contrato, ControlDiario — all entities belong to a user
- Seeded users: `admin@example.com` / `password` (admin), `test@example.com` / `password` (user)
- Widgets/queries scope data: `->when(! $isAdmin, fn ($q) => $q->where('user_id', auth()->id()))`

### Filament Structure
- Resources: `app/Filament/Resources/{Domain}/` — Resource.php, Pages/, Schemas/, Tables/
- Pages: `app/Filament/Pages/ControlSemanal.php`
- Widgets: `app/Filament/Widgets/` — 6 focused stats widgets + Concern trait
- Split layout pattern:
```php
public static function form(Schema $schema): Schema { return MyForm::configure($schema); }
public static function table(Table $table): Table { return MyTable::configure($table); }
```

### Dashboard Widgets (6 focused widgets)
| Sort | Widget | Content |
|------|--------|---------|
| 1 | ResumenDiario | Neto, esperado, gastos by category (daño/mantenimiento/multa/otro) |
| 2 | ResumenSemanal | Esperado, neto, gastos by category |
| 3 | ResumenMensual | Esperado, neto, gastos by category |
| 4 | IndicadoresFlota | Vehículos activos, con conductor, ajustes semana, contratos alquiler/compra |
| 5 | AlertasVencimientos | SOAT/tecnomecánica por vencer (≤30d) y vencidos |
| 6 | PagosRecientesWidget | Last 10 control diario modifications |

All stats widgets use `HasDashboardStats` trait (`money()`, `gastosPorCategoria()`).

### Models & Key Fields
| Model | Table | Key Fields |
|---|---|---|
| Persona | personas | nombre, cedula, telefono, tipo (conductor/propietario/otro), user_id |
| Vehiculo | vehiculos | placa, marca, modelo, anio, color, persona_id, user_id, cuota_diaria, estado, fecha_vencimiento_soat, fecha_vencimiento_tecnomecanico |
| Contrato | contratos | vehiculo_id, persona_id, user_id, tipo (alquiler/opcion_compra), fecha_inicio, fecha_fin, valor_diario, estado, documento |
| ControlDiario | control_diarios | vehiculo_id, user_id, fecha, trabajo, valor_generado, gasto, categoria_gasto, observaciones |
| Configuracion | configuraciones | clave/valor KV store |

### Relationships
- Persona hasMany Contratos, hasMany Vehiculos (as conductor)
- Vehiculo belongsTo Persona, hasMany Contratos, hasMany ControlDiarios
- Contrato belongsTo Vehiculo, belongsTo Persona
- ControlDiario belongsTo Vehiculo
- User hasMany Persona, Vehiculo, Contrato, ControlDiario (data ownership)

### Routes
- `/` → `welcome.blade.php`
- `/admin` → Filament panel
- `/documento/contratos/{path}` → protected file viewer (requires auth, local disk, streams with correct MIME)

## Important Behaviors

### ControlDiario Defaults
- No record in DB = assumes `trabajo=true`, `valor=cuota_diaria`. Deleting reverts to defaults.
- `categoria_gasto` options: daño, mantenimiento, multa, otro

### Vehiculo Deletion
- Blocked if has contratos or controlDiarios. Uses `canBeDeleted()` / `deletionBlockers()`.

### Control Semanal Page (`ControlSemanal.php` + blade)
- Weekly spreadsheet (domingo–sábado), cell editing via modal, week history sidebar
- Admin-only field: `administracion` costo via `configuraciones.administracion_semanal`
- Cell color states: no-trabajo (danger/red), has-gasto (warning/amber), has-changes (was primary/blue, changed to gray), default (gray)
- **Important**: The blade directly inlines Tailwind class strings for cell colors. If you change `primary-*` to neutral colors (e.g. `gray-*`), update both light and dark variants.

### Contrato Documentos
- Stored as file path on `local` disk, served via `/documento/contratos/` route
- Supports pdf, doc/docx, jpg/jpeg, png

### SOAT/Tecnomecanica
- Color-coded badges: success (valid), warning (≤30 days), danger (expired)

### Language
- Spanish only — all labels, navigation, attributes

## Testing
- SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)
- `php artisan config:clear` must run before tests
- 12 test files: 5 Unit (`tests/Unit/`), 7 Feature (`tests/Feature/`)
- Key test areas: RBAC isolation, stats calculations, resource CRUD, auth, control semanal

## Verification Checklist
1. `composer test`
2. `vendor/bin/pint`
3. `npm run build`
4. Test at `/admin/...` screens (Filament panel)
