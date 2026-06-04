# Manual de Usuario

## Acceso

1. Abre `http://rental-manager.test/admin` en tu navegador
2. Inicia sesión con tus credenciales

### Usuarios de prueba (después de seed)

| Rol | Email | Contraseña |
|-----|-------|------------|
| Administrador | admin@example.com | password |
| Usuario | test@example.com | password |

## Dashboard

Al iniciar sesión verás 6 widgets:

### 1. Resumen de hoy
- **Esperado**: suma de cuotas diarias de vehículos activos
- **Ingreso neto**: ingreso real del día menos gastos y administración
- **Gastos**: total de gastos del día
- **Administración**: costo operativo del día
- **Daño / Mantenimiento / Multa / Otro**: desglose de gastos por categoría
- Se actualiza cada 60 segundos

### 2. Resumen de la semana
- Mismos indicadores pero para la semana en curso (domingo a sábado)
- Se actualiza cada 60 segundos

### 3. Resumen del mes
- Mismos indicadores para el mes en curso
- Se actualiza cada 60 segundos

### 4. Indicadores de flota
- **Vehículos activos**: unidades en operación
- **Con conductor**: vehículos con conductor asignado
- **Ajustes semana**: celdas modificadas esta semana
- **Contratos alquiler/opción compra**: contratos activos por tipo
- Se actualiza cada 120 segundos

### 5. Alertas de vencimientos
- SOAT por vencer (≤30 días) y vencidos
- Tecnomecánica por vencer (≤30 días) y vencida
- Se actualiza cada 300 segundos

### 6. Últimos movimientos
- Tabla con los últimos 10 registros modificados
- Muestra fecha, vehículo, conductor, estado, ingreso, gasto, neto, categoría

### Selector de usuario (solo admin)
- Al inicio del dashboard hay un selector "Ver datos de:"
- Puedes seleccionar "Todos los usuarios" o un usuario específico
- La selección se mantiene entre widgets y páginas

## Gestión de Vehículos

**Navegación:** Menú → Vehículos

### Crear vehículo
1. Haz clic en "Nuevo vehículo"
2. Completa los campos:
   - **Placa**: identificador único del vehículo
   - **Administrador vehículo**: nombre del administrador (auto-completa con tu nombre)
   - **Marca, Modelo, Año, Color**: datos del vehículo
   - **Vencimiento SOAT / Tecnomecánica**: fechas para alertas
   - **Conductor**: selecciona una persona (solo muestra conductores activos)
   - **Cuota diaria**: valor base que debe generar por día
   - **Administración**: costo operativo diario
   - **Estado**: activo, inactivo, en mantenimiento

### Editar vehículo
Haz clic en el icono de lápiz en la tabla.

### Ver detalle
Haz clic en el icono de ojo para ver toda la información.

### Eliminar vehículo
Solo se puede eliminar si no tiene contratos ni controles diarios asociados.

## Gestión de Personas

**Navegación:** Menú → Personas

### Tipos de persona
- **Conductor**: maneja un vehículo
- **Propietario**: dueño del vehículo
- **Otro**: otro tipo de vínculo

### Estado
- **Activo**: conduce algún vehículo
- **Inactivo**: tiene deuda activa o no conduce

### Eliminar persona
No se puede eliminar si tiene contratos activos.

## Gestión de Contratos

**Navegación:** Menú → Contratos

### Tipos de contrato
- **Alquiler**: el conductor paga un valor diario por usar el vehículo
- **Opción de compra**: el conductor puede comprar el vehículo al final

### Campos
- **Vehículo**: selecciona de la flota
- **Conductor / Persona**: selecciona una persona activa
- **Valor diario**: monto del contrato
- **Fecha inicio / Fecha fin**: período del contrato
- **Estado**: activo, finalizado, cancelado
- **Documento**: sube PDF, Word o imagen del contrato

### Ver documentos
En la tabla, si un contrato tiene documento, verás un enlace "📎 Ver" que abre el documento en el navegador.

## Control Semanal

**Navegación:** Menú → Control Semanal

Esta es la herramienta principal del sistema. Presenta una cuadrícula semanal (domingo a sábado) donde cada columna es un vehículo activo y cada fila es un día.

### Interpretación de colores en celdas
| Color | Significado |
|-------|-------------|
| Gris claro | Default: trabajó y generó su cuota |
| Gris oscuro | Tiene cambios guardados |
| Rojo | No trabajó ese día |
| Ámbar | Tuvo gastos ese día |

### Editar una celda
1. Haz clic en cualquier celda de la cuadrícula
2. Se abre un modal con los siguientes campos:
   - **El vehículo trabajó este día**: si desmarcas, el ingreso será $0
   - **Valor generado**: ingreso real del día (prellenado con la cuota)
   - **Gasto del día**: si hay gasto, debes seleccionar una categoría
   - **Administración**: costo operativo (prellenado con el valor del vehículo)
   - **Observaciones**: notas opcionales
3. Haz clic en "Guardar"
4. Si todos los valores son iguales a los defaults, el registro se elimina (vuelve al estado por defecto)

### Navegación semanal
- **Anterior / Siguiente**: cambia de semana
- **Semana actual**: vuelve a la semana en curso
- **Selector de fecha**: salta a una semana específica

### Historial
A la derecha de la pantalla (o abajo en móvil) verás el historial de las últimas 12 semanas. Haz clic en una semana para navegar a ella.

### Totales
- **Fila superior**: neto semanal, gastos, administración
- **Fila inferior**: totales por vehículo (neto, ingreso, gastos)
- **Columnas derecha**: gastos del día, total del día, acumulado semanal

### Selector de usuario (solo admin)
Puedes filtrar los datos por usuario específico.

## Reportes

**Navegación:** Menú → Reportes

### Selección de período
- Hoy, Ayer, Esta semana, Semana pasada
- Este mes, Mes pasado, Este trimestre
- Este semestre, Este año, Año pasado
- Personalizado (elige fechas)

### Secciones del reporte

1. **Resumen del período**: esperado, ingreso real, no percibido, diferencia de ajustes, gastos, administración, neto

2. **Gastos por categoría**: gráfico de barras con distribución (daño, mantenimiento, multa, otro)

3. **Desglose por vehículo**: rentabilidad individual (ingresos, gastos, admin, neto)

4. **Detalle diario**: día por día del período (ingresos, gastos, admin, neto, registros)

5. **Ajustes del período**: días con cuota modificada o vehículos que no trabajaron

## Cartera (Deudas)

**Navegación:** Menú → Cartera (solo admin)

Registro de deudas activas de personas. Cada deuda tiene:
- Persona asociada
- Valor de la deuda

## Usuarios

**Navegación:** Menú → Usuarios (solo admin)

Gestiona los usuarios del sistema. Puedes:
- Crear usuarios
- Asignar roles (admin/user)
- Editar información
- Desactivar (no puedes eliminarte a ti mismo)

## Trazabilidad

**Navegación:** Menú → Trazabilidad (solo admin)

Registro de auditoría de todas las acciones del sistema:
- Creación, actualización y eliminación de registros
- Muestra qué cambió (valores anteriores → nuevos)
- Filtros por módulo y evento
- Fecha y hora exacta

## Página de inicio

`http://rental-manager.test/` — Página de bienvenida con enlace al panel de administración.

## Consejos útiles

1. **No necesitas guardar todos los días**: si un vehículo trabajó y generó su cuota exacta sin gastos, no se crea ningún registro. El sistema lo asume automáticamente.

2. **Los gastos siempre tienen categoría**: cuando registras un gasto debes clasificarlo como daño, mantenimiento, multa u otro.

3. **La administración puede variar por día**: aunque cada vehículo tiene un valor de administración por defecto, puedes cambiarlo día a día en el modal del control semanal.

4. **Las alertas de vencimientos se actualizan cada 5 minutos**: si registras una fecha de vencimiento, espera unos minutos para que el widget se actualice.

5. **Los widgets del dashboard usan caché**: los resúmenes se cachean por 1-5 minutos para no ralentizar el panel.
