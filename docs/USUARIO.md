# Manual de Funcionamiento - Rental Manager

## 📖 Índice

1. [Introducción](#introducción)
2. [Acceso al Sistema](#acceso-al-sistema)
3. [Panel de Escritorio](#panel-de-escritorio)
4. [Gestión de Vehículos](#gestión-de-vehículos)
5. [Gestión de Personas](#gestión-de-personas)
6. [Gestión de Contratos](#gestión-de-contratos)
7. [Control Semanal](#control-semanal)
8. [Preguntas Frecuentes](#preguntas-frecuentes)

---

## Introducción

Este manual describe cómo usar el sistema Rental Manager para gestionar su flota de vehículos de alquiler. El sistema permite administrar vehículos, clientes, conductores, contratos y el control semanal de ingresos y gastos.

---

## Acceso al Sistema

### Inicio de Sesión

1. Acceda a la URL del sistema: `http://localhost:8000/admin`
2. Ingrese sus credenciales:
   - **Email**: test@example.com
   - **Contraseña**: password
3. Haga clic en "Iniciar sesión"

### Navegación

Una vez dentro del panel, verá el menú de navegación en el lateral izquierdo con las opciones:
- **Escritorio**: Panel principal con estadísticas
- **Vehículos**: Gestión de la flota
- **Personas**: Clientes y conductores
- **Contratos**: Acuerdos de alquiler
- **Control Semanal**: Registro diario de ingresos/gastos

---

## Panel de Escritorio

El panel de escritorio muestra un resumen visual de las métricas más importantes del sistema.

### Estadísticas Disponibles

#### Métricas Diarias
- **Ingreso hoy**: Ingreso real del día (después de gastos)
- **Esperado hoy**: Suma de cuotas diarias de todos los vehículos activos
- **Gastos hoy**: Total de gastos registrados para el día

#### Métricas Semanales
- **Esperado semana**: Ingreso esperado para 6 días de operación
- **Neto semana**: Ingreso menos gastos de la semana
- **Gastos semana**: Total de gastos de la semana

#### Métricas Mensuales
- **Esperado mes**: Ingreso esperado para el mes en curso
- **Neto mes**: Ingreso menos gastos del mes

#### Información de la Flota
- **Vehículos activos**: Número de vehículos en operación
- **Con conductor**: Vehículos con conductor asignado
- **Contratos activos**: Contratos vigentes
- **Ajustes semana**: Número de celdas modificadas en el control semanal

#### Alertas de Documentos
- **SOAT por vencer**: Cantidad de vehículos con SOAT vence en ≤30 días
- **SOAT vencido**: Cantidad de vehículos con SOAT vencido
- **Tecnomecánica por vencer**: Cantidad con tecnomecánica por vencer
- **Tecnomecánica vencida**: Cantidad con tecnomecánica vencida

### Widget de Ajustes Recientes

Debajo de las estadísticas, se muestra una tabla con los últimos 10 ajustes realizados en el control semanal, incluyendo:
- Fecha del registro
- Vehículo modificado
- Conductor asignado
- Ingreso y gastos registrados
- Estado (trabajó/no trabajó)
- Observaciones

---

## Gestión de Vehículos

### Crear un Vehículo

1. Navegue a **Vehículos** en el menú
2. Haga clic en el botón **Nuevo vehículo**
3. Complete los campos requeridos:
   - **Placa**: Identificador único del vehículo (requerido)
   - **Marca**: Fabricante del vehículo
   - **Modelo**: Modelo del vehículo
   - **Año**: Año de fabricación
   - **Color**: Color del vehículo
   - **Cuota diaria**: Valor que el conductor debe pagar diariamente (requerido)
   - **Estado**: Seleccione "Activo" o "Inactivo"
   - **SOAT**: Fecha de vencimiento del SOAT
   - **Tecnomecánica**: Fecha de vencimiento
4. Opcional: Asigne un conductor en la sección "Conductor"
5. Haga clic en **Crear** para guardar

### Editar un Vehículo

1. En la lista de vehículos, haga clic en el vehículo a editar
2. Haga clic en el botón **Editar** en la vista de detalles
3. Modifique los campos necesarios
4. Haga clic en **Guardar**

### Eliminar un Vehículo

1. En la lista de vehículos, haga clic en el vehículo a eliminar
2. Haga clic en el botón **Eliminar**
3. Confirme la acción

**Nota**: No podrá eliminar un vehículo que tenga contratos activos o registros en el control semanal.

---

## Gestión de Personas

Las personas pueden ser **clientes** (que rentan vehículos) o **conductores** (que conducen los vehículos de la flota).

### Crear una Persona

1. Navegue a **Personas** en el menú
2. Haga clic en el botón **Nueva persona**
3. Complete los campos:
   - **Nombre**: Nombre completo (requerido)
   - **Cédula**: Número de identificación
   - **Teléfono**: Número de contacto
   - **Dirección**: Dirección de residencia
   - **Tipo**: Seleccione "Cliente" o "Conductor"
   - **Observaciones**: Notas adicionales
4. Haga clic en **Crear** para guardar

### Asignar Conductor a un Vehículo

1. Vaya a **Vehículos**
2. Seleccione el vehículo al que desea asignar conductor
3. En el campo "Conductor", seleccione la persona (debe ser tipo "Conductor")
4. Guarde los cambios

---

## Gestión de Contratos

Los contratos representan los acuerdos de alquiler entre la empresa y los clientes.

### Crear un Contrato

1. Navegue a **Contratos** en el menú
2. Haga clic en el botón **Nuevo contrato**
3. Complete los campos:
   - **Vehículo**: Seleccione el vehículo a alquilar
   - **Cliente**: Seleccione el cliente (persona tipo "Cliente")
   - **Tipo**: Tipo de contrato (opcional)
   - **Fecha inicio**: Fecha de inicio del alquiler
   - **Fecha fin**: Fecha de finalización del alquiler
   - **Valor diario**: Valor que el cliente paga por día
   - **Estado**: Seleccione "Activo", "Finalizado" o "Cancelado"
   - **Observaciones**: Notas adicionales
4. Haga clic en **Crear** para guardar

### Estados de un Contrato

- **Activo**: El contrato está vigente
- **Finalizado**: El período del contrato terminó
- **Cancelado**: El contrato fue cancelado antes de su finalización

---

## Control Semanal

El control semanal es el núcleo del sistema. Permite registrar el trabajo diario de cada vehículo, los ingresos generados y los gastos.

### Acceder al Control Semanal

1. Navegue a **Control Semanal** en el menú
2. Verá una tabla con:
   - Filas: Días de la semana (domingo a sábado)
   - Columnas: Vehículos activos
   - Celdas: Ingreso del día para cada vehículo

### Valores por Defecto

Si no hay un registro guardado para un vehículo en un día específico:
- **Trabajo**: Se asume que trabajó (true)
- **Ingreso**: Se usa la cuota_diaria del vehículo
- **Gasto**: Se asume 0

Esto permite ver el "esperado" sin necesidad de registrar cada día.

### Modificar un Registro

Para ajustar un registro (cambiar ingreso, registrar gasto, marcar que no trabajó):

1. Haga clic en la celda correspondiente al vehículo y día
2. Se abrirá un modal con opciones:
   - **El vehículo trabajó este día**: Desmarque si el vehículo no trabajó
   - **Valor generado**: Ingreso del día (se usa cuota_diaria por defecto)
   - **Gasto del día**: Gastos adicionales del día
   - **Observaciones**: Notas adicionales
3. Haga clic en **Guardar** para aplicar los cambios

### Restablecer a Valores por Defecto

Si modifica un registro y desea volver a los valores por defecto:
1. Ajuste los valores para que coincidan con:
   - Trabajo: marcado
   - Valor generado: cuota_diaria del vehículo
   - Gasto: 0
   - Observaciones: vacío
2. Al guardar, el sistema detectará que son los valores por defecto y eliminará el registro

### Navegación entre Semanas

- Use los botones **Anterior** y **Siguiente** para navegar entre semanas
- Haga clic en **Semana actual** para volver a la semana en curso
- Use el campo **Ir a una fecha** para seleccionar una fecha específica

### Administración Semanal

El campo "Admin Semanal" permite especificar un costo de administración que se descuenta del cálculo del neto semanal.

### Historial de Semanas

En el panel derecho se muestra el historial de las últimas 12 semanas que tienen registros. Cada elemento muestra:
- Rango de fechas de la semana
- Número de ajustes realizados
- Días no trabajados
- Neto de la semana
- Esperado, Ingreso y Gasto

Haga clic en una semana del historial para navegar a esa semana.

### Cálculos del Resumen

El sistema calcula:
- **Esperado**: Suma de cuotas_diaria de todos los vehículos activos × días trabajados
- **Real (Ingreso ajustado)**: Suma de valores generados (o 0 si no trabajó)
- **Gastos**: Suma de todos los gastos registrados
- **Neto**: Ingreso - Gastos - Administración

---

## Preguntas Frecuentes

### ¿Cómo saber si un vehículo tiene el SOAT vencido?

El widget de escritorio muestra alertas en la sección "Alertas de documentos". También puede ver las fechas de vencimiento en los detalles de cada vehículo.

### ¿Puedo eliminar un vehículo que tiene contratos?

No. El sistema protege contra eliminación de vehículos con contratos activos o registros de control semanal.

### ¿Qué sucede si no registro el control diario?

El sistema usa valores por defecto (trabajo=true, cuota_diaria, gasto=0), por lo que siempre verá el "esperado" aunque no registre nada.

### ¿Cómo ver el historial de una semana anterior?

Use los botones de navegación o haga clic en una semana del historial en el panel derecho.

### ¿Para qué sirve el campo "Admin Semanal"?

Es un costo fijo semanal (ej: gastos de oficina) que se descuenta del cálculo del neto para obtener una visión más realista de las ganancias.

---

*Manual de usuario generado para Rental Manager*