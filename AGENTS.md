# AGENTS.md

## Stack
- Laravel 13 + Filament 5.6.1, PHP 8.3
- Vite + Tailwind CSS 4
- SQLite (dev) / MySQL (prod)
- No API routes

## Project Setup
- `composer setup` - installs deps, generates key, migrates, builds assets
- Dev server: `php artisan serve`
- Tests: `composer test` (clears config first)
- Format: `vendor/bin/pint`
- Assets: `npm run dev` / `npm run build`
- Old assets break styling: `rm public/css -Recurse -Force`
- Seeded user: `test@example.com` / `password`

## Architecture

### Filament Panel
- Panel at `/admin`, login required
- Custom theme: `resources/css/filament/admin/theme.css`
- Brand logo: `resources/views/filament/custom-logo.blade.php`
- Auto-discovers resources/pages/widgets from `app/Filament/`

### Resource Structure (split layout)
Each resource follows `app/Filament/Resources/<Domain>/`:
- `Pages/`: List, Create, View, Edit page classes
- `Schemas/`: Form (`.php`), Infolist (`.php`) — `form($schema)` and `infolist($schema)` methods on the resource delegate to these
- `Tables/`: Table class

Filament 5 pattern:
```php
public static function form(Schema $schema): Schema { return MyForm::configure($schema); }
public static function infolist(Schema $schema): Schema { return MyInfolist::configure($schema); }
public static function table(Table $table): Table { return MyTable::configure($table); }
```

### Models
| Model | Table | Notes |
|---|---|---|
| `Persona` | `personas` | tipo: cliente/conductor; has vehiculos (conductor), contratos |
| `Vehiculo` | `vehiculos` | placa, cuota_diaria, estado; has contratos, controlDiarios; SOAT/tecnomecánico dates |
| `Contrato` | `contratos` | vehiculo_id, persona_id; fecha_inicio, fecha_fin, valor_diario, estado, documento |
| `ControlDiario` | `control_diarios` | vehiculo_id, fecha, trabajo(bool), valor_generado, gasto; defaults: trabajo=true, valor=cuota_diaria |
| `Configuracion` | `configuraciones` | clave/valor KV store; `Configuracion::get($key, $default)`, `Configuracion::set($key, $value)` |

### Pages & Widgets
- `app/Filament/Pages/ControlSemanal.php` — custom weekly spreadsheet page (domingo-sábado), cell-level editing via modal, week history sidebar, admin costo configurable via `configuraciones.administracion_semanal`
- `app/Filament/Widgets/StatsOverview.php` — 14 stats: daily/weekly/monthly income, fleet counts, SOAT/tecnomecánico alerts
- `app/Filament/Widgets/PagosRecientesWidget.php` — last 10 control diario modifications

### Routes
- `/` → `welcome.blade.php`
- `/admin` → Filament panel
- `/documento/contratos/{path}` → protected file viewer for contrato documents (requires auth, local disk)

## Important Behaviors

- **ControlDiario defaults**: if no record exists for a vehicle/day, system assumes trabajo=true and uses vehicle's cuota_diaria. Deleting a registro reverts to defaults.
- **Vehiculo deletion**: blocked if has contratos or controlDiarios. Check `canBeDeleted()` / `deletionBlockers()`.
- **Contrato documento**: stored as file path, served via `/documento/contratos/` route
- **Spanish labels**: all field labels, navigation, and model attributes use Spanish. Preserve this.

## Composer Lifecycle
- `post-autoload-dump`: `php artisan package:discover && php artisan filament:upgrade`
- `post-update-cmd`: `php artisan vendor:publish --tag=laravel-assets --force`

## Verification
- `composer test` + `vendor/bin/pint`
- Test Filament changes at `/admin/...` screens
