# Manual Técnico - Rental Manager

## 📖 Índice

1. [Requisitos del Sistema](#requisitos-del-sistema)
2. [Instalación](#instalación)
3. [Configuración](#configuración)
4. [Estructura de la Base de Datos](#estructura-de-la-base-de-datos)
5. [Estructura del Código](#estructura-del-código)
6. [Comandos Útiles](#comandos-útiles)
7. [Solución de Problemas](#solución-de-problemas)

---

## Requisitos del Sistema

- **PHP**: 8.2 o superior
- **Composer**: Última versión
- **Node.js**: 18.x o superior (para Vite)
- **npm**: 8.x o superior
- **Base de datos**: SQLite (desarrollo) o MySQL (producción)

### Extensiones PHP Requeridas

- PHP >= 8.2
- Extensiones: `pdo`, `sqlite3`, `mbstring`, `xml`, `ctype`, `json`, `tokenizer`, `openssl`

---

## Instalación

### 1. Clonar el Repositorio

```bash
git clone <url-del-repositorio> rental-manager
cd rental-manager
```

### 2. Instalar Dependencias de PHP

```bash
composer install
```

### 3. Configurar Archivo .env

Copie el archivo de ejemplo:

```bash
copy .env.example .env
```

Genere la clave de aplicación:

```bash
php artisan key:generate
```

### 4. Configurar Base de Datos

**Desarrollo (SQLite):**
```
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

**Producción (MySQL):**
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rental_manager
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Ejecutar Migraciones

```bash
php artisan migrate
```

### 6. Ejecutar Seeders (Opcional)

Para crear datos de prueba:

```bash
php artisan db:seed
```

Esto crea un usuario de prueba:
- **Email**: test@example.com
- **Contraseña**: password

### 7. Compilar Assets (Frontend)

```bash
npm install
npm run dev
```

### 8. Iniciar el Servidor

```bash
php artisan serve
```

El sistema estará disponible en `http://127.0.0.1:8000`

---

## Configuración

### Variables de Entorno (.env)

| Variable | Descripción | Valor por defecto |
|----------|-------------|-------------------|
| `APP_NAME` | Nombre de la aplicación | Rental Manager |
| `APP_ENV` | Entorno de ejecución | local |
| `APP_DEBUG` | Modo debug | true |
| `APP_URL` | URL de la aplicación | http://localhost |
| `DB_CONNECTION` | Tipo de base de datos | sqlite |
| `DB_DATABASE` | Ruta de la base de datos | database/database.sqlite |

### Configuración de Filament

El panel de administración se configura en:
`app/Providers/Filament/AdminPanelProvider.php`

```php
// Configuración del panel
->defaultRouteUrl('/admin')
->sidebarCollapsibleOnDesktop()
```

### Zonas Horarias

La zona horaria se configura en `config/app.php`:

```php
'timezone' => 'America/Bogota',
```

---

## Estructura de la Base de Datos

### Tablas

#### users
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| name | string | Nombre del usuario |
| email | string | Correo electrónico |
| password | string | Contraseña encriptada |
| remember_token | string | Token de recordar sesión |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

#### personas
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| nombre | string | Nombre completo |
| cedula | string | Número de identificación |
| telefono | string | Teléfono de contacto |
| direccion | string | Dirección de residencia |
| tipo | string | 'cliente' o 'conductor' |
| observaciones | text | Notas adicionales |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

#### vehiculos
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| placa | string | Identificador único del vehículo |
| marca | string | Fabricante |
| modelo | string | Modelo del vehículo |
| anio | integer | Año de fabricación |
| color | string | Color del vehículo |
| persona_id | bigint | Conductor asignado (FK) |
| cuota_diaria | float | Valor diario a pagar |
| estado | string | 'activo' o 'inactivo' |
| observaciones | text | Notas adicionales |
| fecha_vencimiento_soat | date | Vencimiento SOAT |
| fecha_vencimiento_tecnomecanico | date | Vencimiento tecnomecánica |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

#### contratos
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| vevhículo_id | bigint | Vehículo asociado (FK) |
| persona_id | bigint | Cliente (FK) |
| tipo | string | Tipo de contrato |
| fecha_inicio | date | Inicio del contrato |
| fecha_fin | date | Fin del contrato |
| valor_diario | float | Valor diario del alquiler |
| estado | string | 'activo', 'finalizado', 'cancelado' |
| observaciones | text | Notas adicionales |
| documento | string | Ruta del documento |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

#### control_diarios
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| vehiculo_id | bigint | Vehículo (FK) |
| fecha | date | Fecha del registro |
| trabajo | boolean | Si trabajó ese día |
| valor_generado | float | Ingreso del día |
| gasto | float | Gastos del día |
| observaciones | text | Notas adicionales |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

#### configuraciones
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| clave | string | Nombre de la configuración |
| valor | string | Valor de la configuración |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

---

## Estructura del Código

```
rental-manager/
├── app/
│   ├── Filament/
│   │   ├── Pages/
│   │   │   └── ControlSemanal.php      # Página de control semanal
│   │   ├── Resources/
│   │   │   ├── Contratos/              # Resource de contratos
│   │   │   ├── Personas/              # Resource de personas
│   │   │   └── Vehiculos/             # Resource de vehículos
│   │   └── Widgets/
│   │       ├── StatsOverview.php      # Widget de estadísticas
│   │       └── PagosRecientesWidget.php # Widget de ajustes recientes
│   └── Models/
│       ├── Configuracion.php          # Modelo de configuraciones
│       ├── Contrato.php               # Modelo de contratos
│       ├── ControlDiario.php          # Modelo de control diario
│       ├── Persona.php                # Modelo de personas
│       ├── User.php                   # Modelo de usuarios
│       └── Vehiculo.php               # Modelo de vehículos
├── database/
│   ├── migrations/                     # Migraciones de la base de datos
│   └── database.sqlite                 # Base de datos SQLite
├── docs/                              # Documentación del proyecto
├── resources/
│   └── views/
│       └── filament/
│           └── pages/
│               └── control-semanal.blade.php # Vista de control semanal
└── routes/
    ├── web.php                        # Rutas web
    └── console.php                    # Rutas de consola
```

### Modelos

Los modelos están en `app/Models/` y utilizan convenciones de Laravel:
- **Relaciones**: hasMany, belongsTo, etc.
- **Fillable**: atributos que pueden ser asignados masivamente
- **Casts**: conversión automática de tipos

### Recursos de Filament

Los recursos en `app/Filament/Resources/` definen:
- **Form**: Esquema del formulario de creación/edición
- **Table**: Configuración de la tabla de lista
- **Infolist**: Esquema de la vista de detalles

### Páginas Personalizadas

- **ControlSemanal**: Página personalizada con lógica de control semanal

### Widgets

- **StatsOverview**: Widget de estadísticas del escritorio
- **PagosRecientesWidget**: Tabla de últimos ajustes del control semanal

---

## Comandos Útiles

### Comandos de Artisan

```bash
# Iniciar servidor de desarrollo
php artisan serve

# Limpiar caché de configuración
php artisan config:clear

# Limpiar caché de rutas
php artisan route:clear

# Ver rutas disponibles
php artisan route:list

# Ejecutar migraciones
php artisan migrate

# Revertir última migración
php artisan migrate:rollback

# Ver errores de base de datos
php artisan db:show

# Ejecutar seeders
php artisan db:seed

# Abrir terminal interactiva (Tinker)
php artisan tinker

# Actualizar Filament
php artisan filament:upgrade
```

### Comandos de Composer

```bash
# Instalar dependencias
composer install

# Actualizar dependencias
composer update

# Instalar зависимости de desarrollo
composer require --dev pestphp/pest

# Ejecutar tests
composer test
```

### Comandos de NPM

```bash
# Instalar dependencias
npm install

# Compilar assets (desarrollo)
npm run dev

# Compilar assets (producción)
npm run build

# Compilar con hot reload
npm run dev -- --watch
```

---

## Solución de Problemas

### Error: "No application encryption key has been specified"

Ejecute:
```bash
php artisan key:generate
```

### Error: "Database not found" (SQLite)

Verifique que el archivo `database/database.sqlite` exista:
```bash
touch database/database.sqlite
```

### Error: "Class 'Imagick' not found"

Este error es opcional si no se usa manipulación de imágenes. Ignore o instale la extensión si es necesaria.

### Limpiar assets antiguos

Si los estilos no se cargan correctamente:
```bash
rm -rf public/css public/js public/hot
npm run dev
php artisan view:clear
```

### Error de permisos en Linux/Mac

```bash
chmod -R 775 storage bootstrap/cache
```

### Ver errores en producción

Configure en `.env`:
```
APP_DEBUG=false
```

Revise los logs en `storage/logs/`

---

*Manual técnico generado para Rental Manager*