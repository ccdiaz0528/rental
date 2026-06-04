# Lógica de Negocio

## Control Diario — Defaults Inteligentes

Cuando no existe un registro en `control_diarios` para un vehículo en una fecha específica, el sistema **asume**:

```
trabajo = true
valor_generado = vehiculo.cuota_diaria
gasto = 0
administracion = vehiculo.administracion ?? 0
categoria_gasto = null
observaciones = null
```

Esto evita crear registros innecesarios. El registro solo se persiste cuando hay una **desviación** del default. Al editar una celda, si el usuario restablece todos los valores al default, el registro se **elimina** (vuelve al estado implícito).

### Lógica de persistencia (`saveRegistro` en `ControlSemanal.php`)

```
$isDefault = trabajo == true
    && valor_generado ≈ cuota_diaria
    && administracion ≈ admin_del_vehiculo
    && gasto ≈ 0
    && observaciones == ''

Si $isDefault:
    Si el registro existe → se elimina (delete)
Si no:
    Se crea o actualiza (updateOrCreate)
```

## Cálculos de Dashboard (Widgets)

### ResumenDiario (`sort=1`)
- Consulta vehículos activos
- Para cada vehículo: busca registro en `control_diarios` para hoy
- Si no hay registro: asume `trabajo=true, valor=cuota_diaria`
- Calcula: esperado, real, gastos (por categoría), administración, neto
- Cache: 60 segundos

### ResumenSemanal (`sort=2`)
- Semana: domingo 00:00 — sábado 23:59
- Itera 7 días por cada vehículo activo
- Acumula esperado, real, gastos, administración
- Cache: 60 segundos

### ResumenMensual (`sort=3`)
- Desde inicio de mes hasta hoy
- Itera todos los días × todos los vehículos activos
- Cache: 60 segundos

### IndicadoresFlota (`sort=4`)
- Vehículos activos
- Vehículos con conductor asignado (persona_id no null)
- Ajustes esta semana (total de registros en control_diarios)
- Contratos activos por tipo (alquiler / opción de compra)
- Cache: 120 segundos

### AlertasVencimientos (`sort=5`)
- SOAT por vencer: `fecha_vencimiento_soat BETWEEN hoy AND hoy+30`
- SOAT vencido: `fecha_vencimiento_soat < hoy`
- Tecnomecánica: mismo criterio
- Cache: 300 segundos

### PagosRecientesWidget (`sort=6`)
- Últimos 10 registros de `control_diarios` ordenados por `updated_at DESC`
- Muestra: fecha, vehículo, conductor, estado, ingreso, gasto, neto, categoría

## Reportes

### Períodos disponibles
- Hoy, Ayer, Esta semana, Semana pasada, Este mes, Mes pasado
- Este trimestre, Este semestre, Este año, Año pasado
- Personalizado (fecha inicio / fecha fin)

### Métricas calculadas
- **Esperado**: cuota_diaria × días × vehículos activos
- **Real**: ingreso real (o default si no hay registro)
- **Gastos**: suma de gastos registrados
- **Administración**: suma de costos administrativos
- **No percibido**: cuota_diaria de días donde `trabajo=false`
- **Diferencia**: Real - Esperado (suma de ajustes positivos y negativos)
- **Neto**: Real - Gastos - Administración

## Control Semanal — Vista de Cuadrícula

### Columnas
- Día (etiqueta + fecha)
- Una columna por vehículo activo (ordenado por placa)
- Columna "Gastos" (total del día)
- Columna "Total día" (ingresos - gastos - admin del día)
- Columna "Acumulado semana"

### Estados visuales de celda
| Condición | Color |
|-----------|-------|
| No trabajó (`trabajo=false`) | Rojo (danger) |
| Tiene gasto (`gasto > 0`) | Ámbar (warning) |
| Tiene cambios (registro existe) | Gris |
| Default (sin registro) | Gris claro |

### Fila de totales
- Neto semanal por vehículo
- Total de gastos
- Neto general

### Historial lateral
- Últimas 12 semanas (desde la actual hacia atrás)
- Solo muestra semanas con novedades (al menos 1 registro)
- Cada entrada muestra: esperado, ingreso, gastos, admin, neto

## Administración (campo)

Cada vehículo tiene un campo `administracion` (costo operativo diario). En el control semanal:
- Por defecto, se usa el valor del vehículo
- Se puede sobreescribir por día en el modal de edición
- El valor sobreescrito se guarda en `control_diarios.administracion`
- Si el valor editado es igual al default del vehículo, no se considera cambio

## Seguridad y Aislamiento de Datos

### RBAC (spatie/laravel-permission)
- Roles: `admin`, `user`
- Los permisos se controlan a nivel de vista/componente, no a nivel de tabla

### Global Scopes
Los modelos Persona, Vehiculo, Contrato y ControlDiario tienen un Global Scope que filtra por `user_id`:

```php
static::addGlobalScope('user', function (Builder $builder) {
    if (auth()->check() && !auth()->user()->hasRole('admin')) {
        $builder->where('user_id', auth()->id());
    }
});
```

### BelongsToUser Trait
Asigna automáticamente `user_id = auth()->id()` en el `creating` event si no se especifica.

### HasUserContext Trait (admin)
Permite a los administradores seleccionar un usuario específico (o "Todos") para ver sus datos. Usa:
- Cache para persistir la selección entre widgets/páginas
- Eventos Livewire (`userContextChanged`) para sincronizar
- Método `applyUserScope()` que aplica el filtro correspondiente

### Documentos de Contratos
- Almacenados en disco `local`
- Servidos por `routes/web.php` vía streaming
- Admin: acceso a todos
- User: solo a sus propios contratos

## Validaciones

### Vehículo
- Placa: único, max 10 chars, requerido
- Año: min 1990, max 2030

### Control Semanal
- `trabajo`: booleano requerido
- `valor_generado`: numérico ≥ 0, requerido
- `gasto`: numérico ≥ 0
- Si gasto > 0, `categoria_gasto` es requerido (uno de: daño, mantenimiento, multa, otro)

### Contrato
- Vehículo y persona requeridos
- Valor diario requerido
- Fecha inicio requerida (default hoy)
- Documento: tipos permitidos (pdf, doc/docx, jpg/jpeg, png)

## Caching

| Elemento | Clave | TTL |
|----------|-------|-----|
| Resumen diario | `dashboard_diario_v2_{userId}_{adminContext}` | 60s |
| Resumen semanal | `dashboard_semanal_v2_{userId}_{adminContext}` | 60s |
| Resumen mensual | `dashboard_mensual_v2_{userId}_{adminContext}` | 60s |
| Indicadores flota | `dashboard_flota_v2_{userId}_{adminContext}` | 120s |
| Alertas | `dashboard_vencimientos_v2_{userId}_{adminContext}` | 300s |
| Historial semanas | `admin_user_{id}_week_history_{weekStart}` | 60s |
| Configuraciones | `configuracion.{clave}` | 1 hora |
| Nombre usuario selector | `admin_user_{id}_name` | 1 hora |
