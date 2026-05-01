# Rental Manager

Sistema de gestión de rentals de vehículos con Laravel 13 y Filament 5.

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- SQLite (desarrollo) o MySQL/PostgreSQL (producción)

## Instalación

```bash
composer setup
```

## Desarrollo

```bash
# Servidor Laravel
php artisan serve

# assets con hot reload
npm run dev

# assets para producción
npm run build

# pruebas
composer test
```

## Panel de Administración

Accede a `/admin` para gestionar:
- **Personas** - Conductores y clientes
- **Vehículos** - Flota de vehículos
- **Contratos** - Contratos de alquiler
- **Control Semanal** - Control diario de ingresos y gastos

Credenciales por defecto: `test@example.com` / `password`

## Estructura del Proyecto

```
app/
├── Filament/
│   ├── Resources/
│   │   ├── Personas/
│   │   ├── Vehiculos/
│   │   └── Contratos/
│   ├── Pages/ControlSemanal.php
│   └── Widgets/
├── Models/
│   ├── Persona.php
│   ├── Vehiculo.php
│   ├── Contrato.php
│   └── ControlDiario.php
└── Providers/

database/
└── migrations/
```

## Tech Stack

- Laravel 13
- Filament 5
- Tailwind CSS 4
- Vite
- SQLite / MySQL / PostgreSQL