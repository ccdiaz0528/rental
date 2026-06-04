# Arquitectura Técnica

## Stack Tecnológico

| Componente | Versión/Tecnología |
|------------|-------------------|
| Framework | Laravel 13 |
| PHP | ^8.4 |
| Panel Admin | Filament 5.6.1 |
| Frontend | Vite + Tailwind CSS 4 |
| Base de datos | MySQL (local) / SQLite (tests) |
| RBAC | spatie/laravel-permission 7.x |
| Auditoría | spatie/laravel-activitylog |
| Moneda | COP (pesos colombianos) |

### Dependencias clave (composer.json)

```json
{
    "require": {
        "php": "^8.4",
        "filament/filament": "^5.0",
        "laravel/framework": "^13.0",
        "spatie/laravel-activitylog": "*",
        "spatie/laravel-permission": "^7.4"
    }
}
```

## Estructura del Proyecto

```
app/
├── Concerns/
│   └── HasUserContext.php          # Trait: contexto multi-usuario para admin
├── Filament/
│   ├── Pages/
│   │   ├── ControlSemanal.php      # Página: control semanal (Livewire)
│   │   └── Reportes.php            # Página: reportes (Livewire)
│   ├── Resources/
│   │   ├── ActivityLogResource.php  # Recurso: trazabilidad (solo admin)
│   │   ├── Contratos/              # CRUD Contratos (Resource + Pages + Schemas + Tables)
│   │   ├── Deudas/                 # CRUD Deudas (solo admin)
│   │   ├── Personas/               # CRUD Personas
│   │   ├── User/                   # CRUD Usuarios (solo admin)
│   │   └── Vehiculos/              # CRUD Vehículos
│   └── Widgets/
│       ├── Concerns/
│       │   └── HasDashboardStats.php  # Trait: formateo money + gastos por categoría
│       ├── AlertasVencimientos.php    # Widget: SOAT/tecnomecánica
│       ├── IndicadoresFlota.php       # Widget: indicadores de flota
│       ├── PagosRecientesWidget.php   # Widget: últimos movimientos (tabla)
│       ├── ResumenDiario.php          # Widget: resumen del día
│       ├── ResumenMensual.php         # Widget: resumen del mes
│       ├── ResumenSemanal.php         # Widget: resumen de la semana
│       └── SelectorUsuarioWidget.php  # Widget: selector de usuario (admin)
├── Http/
│   └── Controllers/
│       └── Controller.php         # Base controller (vacío)
├── Models/
│   ├── Activity.php               # Modelo activity log (extends spatie)
│   ├── Configuracion.php          # Modelo configuración KV
│   ├── Contrato.php               # Contrato
│   ├── ControlDiario.php          # Control diario
│   ├── Deuda.php                  # Deuda/Cartera
│   ├── Persona.php                # Persona
│   ├── User.php                   # Usuario (autenticación + roles)
│   └── Vehiculo.php               # Vehículo
├── Providers/
│   ├── AppServiceProvider.php
│   └── Filament/
│       └── AdminPanelProvider.php # Configuración del panel Filament
└── Traits/
    └── BelongsToUser.php          # Trait: auto-asignación user_id

database/
├── factories/                     # 5 factories
├── migrations/                    # 24 migraciones
└── seeders/
    ├── DatabaseSeeder.php
    └── UserSeeder.php             # Crea roles admin/user + usuarios demo

resources/
└── views/
    └── filament/
        ├── custom-logo.blade.php
        ├── pages/
        │   ├── control-semanal.blade.php
        │   └── reportes.blade.php
        └── widgets/
            └── selector-usuario.blade.php

routes/
├── web.php                       # Ruta welcome + documento contratos
└── console.php                   # Comando inspire

tests/
├── Feature/                      # 9 tests de feature
│   ├── AuthTest.php
│   ├── ContratoResourceTest.php
│   ├── ControlSemanalTest.php
│   ├── DocumentRouteTest.php
│   ├── PersonaResourceTest.php
│   ├── SelectorPersistenceTest.php
│   ├── UserResourceTest.php
│   └── VehiculoResourceTest.php
└── Unit/                         # 6 tests unitarios
    ├── BelongsToUserTraitTest.php
    ├── ConfiguracionTest.php
    ├── GlobalScopeIsolationTest.php
    ├── StatsOverviewCalculationTest.php
    └── UserSeederTest.php
```

## Base de Datos

### Esquema completo (24 migraciones)

| Tabla | Columnas principales |
|-------|---------------------|
| `users` | id, name, email, password, remember_token |
| `personas` | id, user_id, nombre, cedula (unique), telefono, direccion, tipo (enum: conductor/propietario/otro), estado (activo/inactivo), observaciones |
| `vehiculos` | id, user_id, administrador_vehiculo, placa (unique), marca, modelo, anio, color, persona_id (FK→personas), cuota_diaria, administracion, estado (activo/inactivo/mantenimiento), fecha_vencimiento_soat, fecha_vencimiento_tecnomecanico, observaciones |
| `contratos` | id, user_id, vehiculo_id (FK→vehiculos), persona_id (FK→personas), tipo (alquiler/opcion_compra), fecha_inicio, fecha_fin, valor_diario, estado (activo/finalizado/cancelado), documento, observaciones |
| `control_diarios` | id, user_id, vehiculo_id (FK→vehiculos), fecha, trabajo (bool), valor_generado, gasto, categoria_gasto (daño/mantenimiento/multa/otro), administracion, observaciones — UNIQUE(vehiculo_id, fecha) |
| `deudas` | id, user_id (FK→users), persona_id (FK→personas), valor |
| `configuraciones` | id, clave (unique), valor |
| `activity_log` | id, log_name, description, subject_type+id (morph), event, causer_type+id (morph), attribute_changes (json), properties (json) |
| `roles` / `permissions` | spatie/laravel-permission tables |

### Índices importantes
- `vehiculos`: persona_id, estado, fecha_vencimiento_soat, fecha_vencimiento_tecnomecanico
- `contratos`: vehiculo_id, persona_id, (estado, tipo)
- `control_diarios`: (vehiculo_id, fecha) unique, fecha, categoria_gasto, updated_at

### Relaciones
```
User ──hasMany──> Persona, Vehiculo, Contrato, ControlDiario, Deuda
Persona ──hasMany──> Contrato, Vehiculo (como conductor)
Vehiculo ──belongsTo──> Persona (conductor)
Vehiculo ──hasMany──> Contrato, ControlDiario
Contrato ──belongsTo──> Vehiculo, Persona
ControlDiario ──belongsTo──> Vehiculo
Deuda ──belongsTo──> Persona
```

## Panel Filament

### Configuración (`AdminPanelProvider.php`)

- Path: `/admin`
- Login requerido
- Tema: Vite theme (`resources/css/filament/admin/theme.css`)
- Colores:
  - Primary: Blue
  - Gray: Slate
- Brand: logo personalizado + favicon
- Auto-descubrimiento de resources/pages/widgets

### Navegación

| # | Label | Icon | Recurso/Página |
|---|-------|------|----------------|
| 1 | Vehículos | `heroicon-o-truck` | VehiculoResource |
| 2 | Personas | `heroicon-o-users` | PersonaResource |
| 3 | Contratos | `heroicon-o-document-text` | ContratoResource |
| 4 | Control Semanal | `heroicon-o-table-cells` | ControlSemanal (page) |
| 5 | Cartera | `heroicon-o-credit-card` | DeudaResource (admin only) |
| 6 | Reportes | `heroicon-o-chart-bar` | Reportes (page) |
| 7 | Usuarios | `heroicon-o-user-circle` | UserResource (admin only) |
| 8 | Trazabilidad | `heroicon-o-clipboard-document-list` | ActivityLogResource (admin only) |

### Patrón de diseño (Split Layout)

Cada Resource delega la configuración de formularios y tablas a clases separadas:

```
Resource.php
├── Schemas/
│   ├── {Entity}Form.php
│   └── {Entity}Infolist.php
├── Tables/
│   └── {Entity}Table.php
└── Pages/
    ├── Create{Entity}.php
    ├── Edit{Entity}.php
    ├── List{Entity}.php
    └── View{Entity}.php
```

## Livewire + Blade

### Páginas personalizadas
- `ControlSemanal.php` + `control-semanal.blade.php`: cuadrícula semanal interactiva con modal de edición
- `Reportes.php` + `reportes.blade.php`: selector de período + múltiples tablas de reportes

### Widget personalizado
- `SelectorUsuarioWidget.php` + `selector-usuario.blade.php`: selector de contexto de usuario para admin

### Componentes wire
- `wire:model.live` para binding en tiempo real
- `wire:click` para acciones
- `wire:poll` para polling periódico (refreshIfContextChanged cada 5s)

## Rutas

### web.php

| Método | URI | Propósito |
|--------|-----|-----------|
| GET | `/` | Página de bienvenida |
| GET | `/admin` | Panel Filament |
| GET | `/admin/*` | Recursos/páginas Filament |
| GET | `/documento/contratos/{path}` | Visualización de documentos de contratos |

### Ruta de documentos
- Autenticación requerida
- Admin: acceso a cualquier documento
- User: solo documentos de sus contratos
- Soporta: PDF, DOC/DOCX, JPG/JPEG, PNG
- Stream con Content-Type correcto y Content-Disposition inline

## Seguridad

### Autenticación
- Login estándar Filament (Laravel auth)
- Sesiones con `AuthenticateSession` middleware

### RBAC
- Roles: admin, user
- Control a nivel de vista (Global Scope) y a nivel de recurso (`canAccess`, `canViewAny`, etc.)

### Protecciones
- `preventLazyLoading` en desarrollo
- `preventSilentlyDiscardingAttributes` en desarrollo
- `prohibitDestructiveCommands` en producción
- CSRF protection
- Documentos protegidos por ruta con verificación de propiedad

## Tests (15 tests)

### Unit Tests (6)
- `BelongsToUserTraitTest`: auto-asignación de user_id
- `ConfiguracionTest`: KV store y cache
- `GlobalScopeIsolationTest`: aislamiento de datos entre usuarios
- `StatsOverviewCalculationTest`: cálculos de SOAT/tecnomecánica, money, neto, defaults
- `UserSeederTest`: seed correcto de roles y usuarios

### Feature Tests (9)
- `AuthTest`: login, redirección, welcome page
- `ContratoResourceTest`: CRUD contratos
- `ControlSemanalTest`: defaults, creación, semana domingo-sábado, RBAC, categorías
- `DocumentRouteTest`: acceso a documentos
- `PersonaResourceTest`: CRUD personas
- `SelectorPersistenceTest`: persistencia del selector de usuario
- `UserResourceTest`: CRUD usuarios (admin only)
- `VehiculoResourceTest`: RBAC, unicidad placa, validación año, eliminación

### Configuración de tests
- SQLite in-memory
- `RefreshDatabase` trait
- `config:clear` antes de ejecutar

## Comandos Útiles

```bash
# Setup completo
composer setup

# Tests
composer test

# Desarrollo
composer dev

# Formato de código
vendor/bin/pint

# Limpiar assets
Remove-Item -Recurse -Force public/css
```
