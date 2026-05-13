# AGENTS.md

## Stack
- Laravel 13 + Filament 5.6.1, PHP 8.3
- Vite + Tailwind CSS 4 (usando `@tailwindcss/vite`)
- MySQL (Laragon local) / SQLite (tests)
- Moneda: COP (pesos colombianos)

## Commands
- `composer setup` - installs deps, generates key, migrates, builds assets
- `composer test` - clears config + runs phpunit (uses SQLite in-memory)
- `composer dev` - runs php artisan serve + queue + logs + vite concurrently
- `npm run dev` - assets con hot reload (Vite)
- `npm run build` - assets para producción
- `vendor/bin/pint` - format code
- `php artisan tinker` - REPL para debugging
- Dev server: http://rental-manager.test (Laragon) o `php artisan serve`
- Old assets break styling (Windows): `Remove-Item -Recurse -Force public/css`
- Clear caches: `php artisan config:clear && php artisan view:clear`
- Seeded user: `test@example.com` / `password`

## Architecture

### Panel Structure
- Panel at `/admin`, requires login
- Theme: `resources/css/filament/admin/theme.css`
- Brand logo: `resources/views/filament/custom-logo.blade.php` (uses `favicon.png`)
- Auto-discovers resources/pages/widgets from `app/Filament/`

### Resource Structure (Filament 5 split layout)
Pattern:
```php
public static function form(Schema $schema): Schema { return MyForm::configure($schema); }
public static function table(Table $table): Table { return MyTable::configure($table); }
```
Files live under `app/Filament/Resources/<Domain>/` — Resource.php, Pages/, Schemas/, Tables/

### Models & Key Fields
| Model | Table | Key Fields |
|---|---|---|
| `Persona` | `personas` | nombre, cedula, telefono, tipo (conductor/propietario/otro) |
| `Vehiculo` | `vehiculos` | placa, marca, modelo, anio, color, persona_id, cuota_diaria, estado, fecha_vencimiento_soat, fecha_vencimiento_tecnomecanico |
| `Contrato` | `contratos` | vehiculo_id, persona_id, tipo (alquiler/opcion_compra), fecha_inicio, fecha_fin, valor_diario, estado, documento |
| `ControlDiario` | `control_diarios` | vehiculo_id, fecha, trabajo, valor_generado, gasto, observaciones |
| `Configuracion` | `configuraciones` | clave/valor KV store |

### Relationships
- Persona hasMany Contratos, hasMany Vehiculos (as conductor)
- Vehiculo belongsTo Persona, hasMany Contratos, hasMany ControlDiarios
- Contrato belongsTo Vehiculo, belongsTo Persona
- ControlDiario belongsTo Vehiculo

### Pages & Widgets
- `ControlSemanal.php` - Weekly spreadsheet (domingo–sábado), cell editing via modal, week history sidebar, admin costo via `configuraciones.administracion_semanal`
- `StatsOverview.php` - 14 stats: daily/weekly/monthly income, fleet counts, SOAT/tecnomecánico alerts (≤30 days warning)
- `PagosRecientesWidget.php` - Last 10 control diario modifications

### Routes
- `/` → `welcome.blade.php`
- `/admin` → Filament panel
- `/documento/contratos/{path}` → protected file viewer (requires auth, local disk)

## Important Behaviors

- **ControlDiario defaults**: no record = trabajo=true, valor=cuota_diaria. Deleting reverts to defaults.
- **Vehiculo deletion**: blocked if has contratos or controlDiarios. Uses `canBeDeleted()` / `deletionBlockers()`.
- **Contrato documento**: stored as file path, served via `/documento/contratos/` route. Supports pdf, doc/docx, images.
- **Vehiculo SOAT/Tecnomecanica**: color-coded badges (success/warning/danger) in tables and StatsOverview.
- **Spanish only**: all labels, navigation, and attributes use Spanish.

## Composer Lifecycle
- `post-autoload-dump`: `php artisan package:discover && php artisan filament:upgrade`
- `post-update-cmd`: `php artisan vendor:publish --tag=laravel-assets --force`

## Testing
- Uses SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)
- Tests clear config first: `php artisan config:clear --ansi`
- No integration tests currently exist (tests/Unit and tests/Feature are empty)

## Verification
1. `composer test`
2. `vendor/bin/pint`
3. Test Filament changes at `/admin/...` screens