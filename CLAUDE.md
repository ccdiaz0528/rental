# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Rental Manager: vehicle-rental management app (Laravel 13, PHP ^8.4, Filament 5 admin panel at `/admin`, Tailwind 4 via Vite). UI/domain language is Spanish (locale `es_CO`, timezone `America/Bogota`). `AGENTS.md` holds the full architecture notes; `docs/` has business rules and schema (`logica-de-negocio.md`, `arquitectura-tecnica.md`).

## Commands

```bash
composer setup          # install, .env, key, migrate, npm install, build
composer dev            # server + queue:listen + pail + Vite, concurrently
composer test           # config:clear + php artisan test
php artisan test --filter=ControlSemanalTest          # single test class/method
php artisan test tests/Feature/VehiculoSoftDeleteTest.php
vendor/bin/pint         # PSR-12 format — run after every PHP change
npm run build           # production assets (also verifies the build)
php artisan config:clear && php artisan view:clear
```

Verification order: `composer test` → `vendor/bin/pint` → `npm run build`. Tests use SQLite `:memory:` + `RefreshDatabase`; they create roles manually in `setUp` (no seeder dependency). Dev DB is MySQL (Laragon); session/cache/queue use the `database` driver.

Vite inputs: `resources/css/app.css`, `resources/js/app.js`, `resources/css/filament/admin/theme.css` (custom Filament theme).

## Architecture (big picture)

**Multi-tenant by `user_id`, no Policy classes.** Roles are `admin` (unrestricted) and `user` (own data only). Isolation comes from model concerns in `app/Concerns`, applied to Persona, Vehiculo, Contrato, ControlDiario, Deuda:
- `BelongsToUser` — sets `user_id = auth()->id()` on `creating`.
- `HasUserScope` — global scope filtering by `user_id` for non-admins.
- `HasUserContext` — lets an admin "act as" another user (persisted in cache, switched via Livewire events); used by all dashboard widgets, ControlSemanal and Reportes. New widgets/pages that show per-user data must use it.
- Only ActivityLogResource (`canAccess`) and UserResource (`can*`, served at `/admin/user/users`) are explicitly admin-gated; the domain resources rely solely on `HasUserScope`. Pages that bypass the scope (`withoutGlobalScope`, `withTrashed` + manual queries) must re-check ownership themselves.
- `User` implements `FilamentUser` (`canAccessPanel` returns true), so `/admin` works in any `APP_ENV`. `/documento/contratos/{path}` (DocumentController) enforces ownership by `user_id`.
- `ControlSemanal`'s `selectedVehiculoId`/`selectedFecha`/`cachedVehiculo` are `#[Locked]`; `saveRegistro()` re-fetches the vehicle through the user scope and recomputes defaults server-side. The "blocked cell" rule lives in `Vehiculo::estaBloqueadoEn()` — reuse it, don't copy it.
- `vehiculos.placa` has a global unique index (including soft-deleted rows); the form validation matches that.

**Filament split layout.** Each resource under `app/Filament/Resources/{Domain}/` delegates to `Schemas/{Domain}Form.php`, `Schemas/{Domain}Infolist.php`, `Tables/{Domain}Table.php`; the Resource class itself stays thin.

**Custom pages** (Livewire + Blade): `ControlSemanal` (weekly grid, weeks start on **Sunday** via `Carbon::SUNDAY`; saving a cell with default values *deletes* the ControlDiario row) and `Reportes`. Week math must stay consistent across ControlSemanal, ResumenSemanal, IndicadoresFlota and Reportes — there is a sync test (`ControlSemanalReportesSyncTest`) guarding this; beware Carbon mutation bugs (clone dates).

**Models:** use `#[Fillable]` attributes (not `$fillable`) and spatie `LogsActivity`. User's `#[Fillable]` excludes `roles` (relationship) — Create/EditUser strip it. `Vehiculo` uses `SoftDeletes` (history of contratos/controlDiarios preserved; `control_diarios.vehiculo_id` is `nullOnDelete`); `Persona` can't be deleted while it has active contratos. `Configuracion` is a cached (1h) key-value store via `Configuracion::get/set`. `ControlDiario` category constants: `CATEGORIA_DAÑO`, `MANTENIMIENTO`, `MULTA`, `OTRO`.

**Dashboard** widgets are ordered by `$sort` (selector, daily/weekly/monthly summaries, fleet indicators, expiry alerts, recent payments); stats widgets share the `HasDashboardStats` trait + `HasUserContext`.

Seeded logins: `admin@example.com` / `password`, `test@example.com` / `password`.
