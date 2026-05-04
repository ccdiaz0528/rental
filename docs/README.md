# Rental Manager - Documentación del Proyecto

## 📋 Índice

1. [Descripción General](#descripción-general)
2. [Estructura del Proyecto](#estructura-del-proyecto)
3. [Tecnologías Utilizadas](#tecnologías-utilizadas)
4. [Primeros Pasos](#primeros-pasos)
5. [Documentación Adicional](#documentación-adicional)

---

## Descripción General

**Rental Manager** es un sistema de gestión para rental de vehículos desarrollado en Laravel + Filament. Permite administrar:

- **Vehículos**: Flota de vehículos con información técnica, documentos y asignación de conductores
- **Personas**: Clientes y conductores con datos de contacto
- **Contratos**: Acuerdos de alquiler entre empresa y clientes
- **Control Semanal**: Registro diario de trabajo, ingresos y gastos por vehículo

### Características Principales

- Panel de administración con Filament (admin panel)
- Widget de escritorio con estadísticas en tiempo real
- Control semanal con edición por celdas
- Historial de semanas anteriores
- Alertas de documentos por vencer (SOAT, Tecnomecánica)

---

## Estructura del Proyecto

```
rental-manager/
├── app/
│   ├── Filament/
│   │   ├── Pages/           # Páginas personalizadas
│   │   ├── Resources/       # Recursos CRUD (Vehículos, Personas, Contratos)
│   │   └── Widgets/         # Widgets del escritorio
│   └── Models/              # Modelos Eloquent
├── database/
│   ├── migrations/          # Migraciones de base de datos
│   └── database.sqlite      # Base de datos SQLite (desarrollo)
├── docs/                    # Documentación del proyecto
├── resources/
│   └── views/               # Vistas Blade
└── routes/                 # Rutas de la aplicación
```

### Dominios del Sistema

| Dominio | Descripción |
|---------|-------------|
| **Vehículos** | Flota de vehículos, documentación, conductor asignado |
| **Personas** | Clientes y conductores, datos de contacto |
| **Contratos** | Acuerdos de alquiler, período, valor diario |
| **ControlDiario** | Registro diario de trabajo, ingresos, gastos |

---

## Tecnologías Utilizadas

- **Framework**: Laravel 13.x
- **Admin Panel**: Filament 5.x
- **Base de datos**: SQLite (desarrollo) / MySQL (producción)
- **Frontend**: Vite + Tailwind CSS 4
- **PHP**: 8.2+

---

## Primeros Pasos

### Instalación

```bash
# Instalar dependencias
composer install

# Configurar entorno
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders (usuario de prueba)
php artisan db:seed
```

### Ejecución

```bash
# Iniciar servidor de desarrollo
php artisan serve

# Compilar assets (Vite)
npm run dev
```

### Credenciales de Prueba

- **Email**: test@example.com
- **Contraseña**: password

---

## Documentación Adicional

| Documento | Descripción |
|-----------|-------------|
| [USUARIO.md](./USUARIO.md) | Manual de funcionamiento para usuarios |
| [TECNICO.md](./TECNICO.md) | Manual técnico y de configuración |
| [LOGICA.md](./LOGICA.md) | Manual de lógica del negocio |

---

## Rutas del Sistema

| Ruta | Descripción |
|------|-------------|
| `/` | Página de inicio (bienvenida) |
| `/admin` | Panel de administración de Filament |
| `/admin/vehiculos` | Gestión de vehículos |
| `/admin/personas` | Gestión de personas (clientes/conductores) |
| `/admin/contratos` | Gestión de contratos |
| `/admin/control-semanal` | Control semanal de ingresos/gastos |

---

*Documentación generada automáticamente para Rental Manager*