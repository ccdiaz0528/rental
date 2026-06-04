# Modelo de Negocio

## Propósito

Rental Manager es un sistema SaaS multiinquilino (multi-usuario) para la administración de flotas vehiculares. Permite a propietarios de vehículos y administradores de flotas llevar el control operativo y financiero de sus unidades.

## Actores

### Administrador (`admin`)
- Acceso a todos los datos del sistema sin restricciones
- Puede ver datos de cualquier usuario mediante un selector contextual
- Gestiona usuarios del sistema
- Accede a la trazabilidad completa (activity log)
- Configura el valor de administración semanal global

### Usuario (`user`)
- Solo ve sus propios datos (personas, vehículos, contratos, controles diarios)
- No puede ver datos de otros usuarios
- No accede a trazabilidad ni gestión de usuarios

## Entidades del Negocio

### Persona
Persona física vinculada a la operación. Puede ser:
- **Conductor**: maneja un vehículo de la flota
- **Propietario**: dueño del vehículo (no necesariamente lo conduce)
- **Otro**: otro tipo de vínculo

Cada persona tiene un estado (`activo` / `inactivo`).

### Vehículo
Unidad de la flota. Datos clave:
- **Placa**: identificador único
- **Cuota diaria**: valor base que debe generar por día de operación
- **Administración**: costo operativo diario del vehículo (ej. comisión del administrador)
- **Estado**: activo, inactivo, en mantenimiento
- **Conductor**: relación con una Persona (nullable)
- **SOAT** y **Tecnomecánica**: fechas de vencimiento para alertas

### Contrato
Acuerdo legal entre el propietario/administrador y un conductor. Tipos:
- **Alquiler**: el conductor paga un valor diario por usar el vehículo
- **Opción de compra**: el conductor puede comprar el vehículo al final del contrato

Cada contrato tiene un estado (`activo`, `finalizado`, `cancelado`) y puede tener un documento adjunto (PDF, DOCX, imagen).

### Control Diario
Registro granular de la operación diaria de cada vehículo. Es la entidad central del sistema.

- **Fecha + Vehículo**: combinación única (no pueden existir dos registros del mismo vehículo el mismo día)
- **Trabajo**: booleano que indica si el vehículo operó ese día
- **Valor generado**: ingreso real del día
- **Gasto**: descuento aplicado al día
- **Categoría de gasto**: daño, mantenimiento, multa, otro
- **Administración**: costo operativo del día (puede heredarse del vehículo o sobreescribirse)

### Deuda (Cartera)
Registro de deudas activas asociadas a una persona. Simple valor monetario.

### Configuración
Almacenamiento clave-valor (`configuraciones`). Actualmente solo una clave:
- `administracion_semanal`: valor de administración global (para el campo de administración en el control semanal, aunque ahora el valor se hereda de cada vehículo)

## Procesos de Negocio

### 1. Registro de Flota
1. Se crean Personas (conductores, propietarios)
2. Se crean Vehículos con su cuota diaria y costo de administración
3. Se asigna un conductor al vehículo (opcional)
4. Se registran fechas de vencimiento SOAT y tecnomecánica

### 2. Contratación
1. Se selecciona un vehículo y una persona
2. Se define el tipo de contrato (alquiler / opción de compra)
3. Se establece el valor diario y las fechas
4. Opcionalmente se adjunta el documento del contrato

### 3. Control Semanal
1. Cada semana (domingo a sábado) se genera automáticamente una vista tipo hoja de cálculo
2. Por defecto, cada vehículo activo genera su cuota diaria (asume que trabajó)
3. El usuario puede modificar celdas para reflejar:
   - Que el vehículo **no trabajó** (ingreso = 0)
   - Que generó **más o menos** de la cuota
   - Que tuvo **gastos** con su categoría
   - **Observaciones**
4. Si los valores son iguales a los defaults, no se crea registro en DB (ahorro de espacio)
5. El historial muestra las últimas 12 semanas con novedades

### 4. Reportes
1. Selección de período predefinido o personalizado
2. Visualización de:
   - Resumen general (esperado, real, neto, gastos, administración)
   - Gastos por categoría (con gráfico de barras)
   - Desglose por vehículo (rentabilidad individual)
   - Detalle diario (día por día)
   - Ajustes del período (días con cuota modificada)

### 5. Alertas de Vencimientos
- SOAT por vencer (≤30 días): alerta amarilla
- SOAT vencido: alerta roja
- Tecnomecánica por vencer (≤30 días): alerta amarilla
- Tecnomecánica vencida: alerta roja

## Flujo de Caja (Cálculo Financiero)

```
Esperado = Σ(cuota_diaria de cada vehículo activo × días del período)
Real = Σ(valor generado real, con defaults = cuota_diaria si trabajó, 0 si no)
Gastos = Σ(gasto registrado en cada control diario)
Administración = Σ(administración, heredada del vehículo o sobreescrita)
Neto = Real - Gastos - Administración
No percibido = Σ(cuota_diaria de días no trabajados)
Diferencia = Real - Esperado (ajustes)
```

## Modelo de Datos (ER)

```
User 1──N Persona
User 1──N Vehiculo
User 1──N Contrato
User 1──N ControlDiario
User 1──N Deuda

Persona 1──N Vehiculo (conductor)
Persona 1──N Contrato
Persona 1──N Deuda

Vehiculo 1──N Contrato
Vehiculo 1──N ControlDiario
```

## Reglas de Eliminación

- **Persona**: no se puede eliminar si tiene contratos activos
- **Vehículo**: no se puede eliminar si tiene contratos o controles diarios asociados
- **Usuario**: no se puede eliminar a sí mismo
