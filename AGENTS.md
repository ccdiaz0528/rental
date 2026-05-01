# AGENTS.md

## Stack And Versions
- Laravel 13.6.0 + Filament 5.6.1
- UI: Filament panel at `/admin`; `/` is welcome page
- Filament wired in `app/Providers/Filament/AdminPanelProvider.php`. Auto-discovers from `app/Filament`.
- No API routes. `bootstrap/app.php` wires `routes/web.php`, `routes/console.php`, `/up` health check.

## Domain Structure
- **Personas** - Conductores y clientes
- **Vehiculos** - Flota de vehículos
- **Contratos** - Contratos de alquiler
- **ControlDiario** - Control semanal de ingresos/gastos (reemplaza PagoDiarios y Gastos)

Filament split layout: `app/Filament/Resources/<Domain>/Pages`, `Schemas`, `Tables`.
Models use Spanish vocabulary. Preserve when adding fields/labels.

## Commands
- Initial setup: `composer setup`
- Dev server (Windows): `php artisan serve` (pcntl not available)
- Tests: `composer test` (clears config first)
- Single test: `php artisan test tests/Feature/ExampleTest.php`
- Format: `vendor/bin/pint`
- Assets: `npm run dev` / `npm run build`
- Clear views after asset changes: `php artisan view:clear`

## Database
- Default: SQLite (`database/database.sqlite`)
- Production: MySQL (rental_manager)
- Migrations required before app works normally
- Seeded user: `test@example.com` / `password`

## Frontend
- Vite + Tailwind CSS 4
- Entry: `resources/css/app.css`, `resources/js/app.js` (currently empty)
- **Old assets cause issues**: Delete `public/css`, `public/js`, `public/hot` if styles break
  - Windows: `rm public/css -Recurse -Force`

## Verification
- Test: `composer test` + `vendor/bin/pint`
- For Filament changes, verify `/admin/...` screens

## Composer Lifecycle
- `composer install` triggers `php artisan filament:upgrade`
- `composer update` force-publishes Laravel assets