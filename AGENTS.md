# AGENTS.md

## Stack And Entry Points
- This is a Laravel 13 app with Filament 5. The real app UI is the Filament panel at `/admin`; `/` is still the default welcome page.
- Filament is wired in `app/Providers/Filament/AdminPanelProvider.php`. Resources, pages, and widgets are auto-discovered from `app/Filament`.
- There are no `api.php` routes. `bootstrap/app.php` only wires `routes/web.php`, `routes/console.php`, and the health check at `/up`.

## Domain Structure
- The main business areas are `Personas`, `Vehiculos`, `Contratos`, `PagoDiarios`, and `Gastos`.
- Filament resources follow the Filament 5 split layout: `app/Filament/Resources/<Domain>/Pages`, `Schemas`, and `Tables`. Prefer matching that structure instead of adding older inline resource definitions.
- Models and schema use Spanish names and enums. Preserve that vocabulary when adding fields, labels, states, or routes.

## Commands
- Initial bootstrap: `composer setup`
- Full local dev loop: `composer dev`
  - This runs the PHP server, a queue listener, `php artisan pail`, and Vite together.
- Tests: `composer test`
  - This clears config first, then runs `php artisan test`.
- Focused test runs: `php artisan test tests/Feature/ExampleTest.php` or `php artisan test --filter=SomeTestName`
- PHP formatting: `vendor/bin/pint`
- Frontend assets only: `npm run dev` or `npm run build`

## Database And Auth Gotchas
- Default local DB is SQLite (`config/database.php`, `.env.example`) and the repo already includes `database/database.sqlite`.
- `.env.example` uses database-backed sessions, cache, and queues, so migrations are required before the app behaves normally.
- Tests do not use the file DB; `phpunit.xml` forces an in-memory SQLite database.
- If you need an admin login quickly, run `php artisan db:seed`. The default seeded user is `test@example.com` with password `password`.

## Frontend Notes
- Frontend tooling is minimal: Vite + Tailwind CSS 4, with entrypoints `resources/css/app.css` and `resources/js/app.js`.
- Most UI work should happen in Filament resources/widgets, not in custom JS; `resources/js/app.js` is currently empty.

## Verification Priorities
- For Filament/resource changes, verify the affected `/admin/...` screen instead of only checking `/`.
- There is no repo ESLint, Prettier, or TypeScript config. The meaningful checks here are `vendor/bin/pint`, `composer test`, and asset builds when frontend files change.

## Composer Lifecycle
- `composer install` / `composer dump-autoload` triggers `php artisan filament:upgrade` via `post-autoload-dump`.
- `composer update` also force-publishes Laravel assets via `post-update-cmd`.
