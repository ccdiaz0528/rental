# Manual de Lógica - Rental Manager

## 📖 Índice

1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Lógica del Negocio](#lógica-del-negocio)
3. [Modelos y Relaciones](#modelos-y-relaciones)
4. [Flujos Principales](#flujos-principales)
5. [Cálculos y Fórmulas](#cálculos-y-fórmulas)
6. [Reglas de Negocio](#reglas-de-negocio)
7. [Casos de Uso](#casos-de-uso)

---

## Arquitectura del Sistema

El sistema está basado en una arquitectura MVC (Model-View-Controller) adaptada a Laravel + Filament:

```
┌─────────────────────────────────────────────────────────────────┐
│                        PRESENTACIÓN                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │   Páginas   │  │   Widgets   │  │   Recursos (CRUD)      │  │
│  │  Filament   │  │  Dashboard  │  │   Vehicles/Persons     │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        LOGICA DE NEGOCIO                        │
│  ┌─────────────────┐  ┌──────────────────────────────────────┐  │
│  │  ControlSemanal │  │        StatsOverview Widget         │  │
│  │  Page (PHP)     │  │        (Cálculos de métricas)       │  │
│  └─────────────────┘  └──────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                          DATOS                                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │   Modelos   │  │   Eloquent  │  │   Base de Datos        │  │
│  │   (PHP)     │  │   (Laravel)  │  │   (SQLite/MySQL)       │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### Componentes Principales

| Componente | Ubicación | Descripción |
|------------|-----------|-------------|
| **Modelos** | `app/Models/` | Representación de entidades del negocio |
| **Recursos Filament** | `app/Filament/Resources/` | CRUD automático para cada entidad |
| **Páginas** | `app/Filament/Pages/` | Páginas personalizadas con lógica |
| **Widgets** | `app/Filament/Widgets/` | Componentes visuales del escritorio |

---

## Lógica del Negocio

### Flujo de Personas (Clientes y Conductores)

```
Persona (tipo: cliente/conductor)
    │
    ├──[Cliente]──► Contrato (alquila) ──► Vehículo
    │
    └──[Conductor]──► Vehículo (asignado)
```

Una **Persona** puede tener dos roles:
1. **Cliente**: Alquila vehículos mediante contratos
2. **Conductor**: Conduce vehículos de la flota

Un cliente puede tener múltiples contratos (histórico de alquileres).
Un conductor puede tener asignado un vehículo a la vez (relación 1:1).

### Flujo de Vehículos

```
Vehículo (estado: activo/inactivo)
    │
    ├──► Persona (conductor asignado) [opcional]
    │
    ├──► Contrato (alquiler actual) [opcional]
    │
    └──► ControlDiario (registros diarios) [muchos]
```

Un vehículo puede estar:
- **Sin conductor**: Disponible para asignar
- **Con conductor asignado**: En operación
- **Con contrato activo**: Alquilado a un cliente

### Flujo de Control Semanal

```
Semana (Domingo - Sábado)
    │
    └──► 7 días
          │
          └──► Por cada vehículo activo:
                │
                └──► ControlDiario (registro del día)
                      ├── trabajo: boolean
                      ├── valor_generado: float
                      ├── gasto: float
                      └── observaciones: string
```

**Importante**: Si no existe un registro para un vehículo en un día:
- Se usan valores por defecto (trabajo=true, valor=cuota_diaria, gasto=0)
- Esto permite calcular el "esperado" sin registro manual

---

## Modelos y Relaciones

### Diagrama de Entidades

```
┌─────────────┐       ┌─────────────┐
│    User     │       │   Persona   │
├─────────────┤       ├─────────────┤
│ id          │       │ id          │
│ name        │       │ nombre      │
│ email       │       │ tipo        │───► 'cliente' / 'conductor'
│ password    │       │ cedula      │
└─────────────┘       │ telefono    │
      │               └──────┬──────┘
      │                      │
      │                 ┌────┴────┐
      │                 │         │
      ▼                 ▼         ▼
┌─────────────┐   ┌──────────┐ ┌────────────┐
│  Contrato   │◄──│Vehiculo  │ │   Vehículo │
├─────────────┤   ├──────────┤ │  (conductor)│
│ id          │   │ id       │ └────────────┘
│ vehiculo_id │───►│placa     │      │
│ persona_id  │    │cuota_   │      │
│ fecha_inicio│    │diaria   │      │
│ fecha_fin   │    │estado   │      │
│ valor_diario│    └────┬────┘      │
│ estado      │         │           │
└──────┬──────┘         │           │
       │          ┌─────┴─────┐     │
       │          │           │     │
       ▼          ▼           ▼     ▼
┌─────────────┐ ┌─────────────────────────┐
│  Control    │ │      ControlDiario      │
│  Diario      │ ├─────────────────────────┤
└─────────────┘ │ id                      │
               │ vehiculo_id ─────────────┤
               │ fecha                    │
               │ trabajo ─────────────────┘
               │ valor_generado
               │ gasto
               │ observaciones
               └─────────────────────────┘
```

### Descripción de Relaciones

| Modelo | Relación | Tipo | Descripción |
|--------|----------|------|-------------|
| Persona | contratos | HasMany | Un cliente puede tener muchos contratos |
| Persona | vehiculos | HasMany | Un conductor puede tener muchos vehículos (histórico) |
| Vehiculo | persona | BelongsTo | Un vehículo tiene un conductor asignado |
| Vehiculo | contratos | HasMany | Un vehículo puede tener muchos contratos |
| Vehiculo | controlDiarios | HasMany | Un vehículo tiene muchos registros de control |
| Contrato | vehiculo | BelongsTo | Un contrato renting un vehículo |
| Contrato | persona | BelongsTo | Un contrato pertenece a un cliente |
| ControlDiario | vehiculo | BelongsTo | Un registro pertenece a un vehículo |

---

## Flujos Principales

### 1. Crear un Nuevo Conductor y Asignarlo a un Vehículo

```
1. Crear Persona (tipo = 'conductor')
       │
       ▼
2. Crear Vehículo (estado = 'activo')
       │
       ▼
3. Asignar persona_id al vehículo (conductor)
       │
       ▼
4. El vehículo aparece en el Control Semanal
```

### 2. Crear un Contrato de Alquiler

```
1. Crear Persona (tipo = 'cliente') [si no existe]
       │
       ▼
2. Seleccionar Vehículo (estado = 'activo')
       │
       ▼
3. Crear Contrato con:
   - vehiculo_id = vehículo seleccionado
   - persona_id = cliente
   - fecha_inicio = fecha de inicio
   - fecha_fin = fecha de fin
   - valor_diario = cuota diaria
   - estado = 'activo'
```

### 3. Registrar Control Diario

```
1. Acceder a Control Semanal
       │
       ▼
2. Seleccionar semana (domingo - sábado)
       │
       ▼
3. Para cada vehículo:
   │
   ├──[Sin cambios]──► Usar valores por defecto
   │
   └──[Con cambios]──► Abrir modal y registrar:
                       ├── trabajo = true/false
                       ├── valor_generado =自定义
                       ├── gasto =自定义
                       └── observaciones = texto
```

---

## Cálculos y Fórmulas

### 1. Cálculo de "Esperado"

El esperado representa lo que *debería* generar la flota si todos los vehículos trabajan todos los días.

```
esperado = Σ (cuota_diaria de cada vehículo activo) × días de la semana
```

**Ejemplo**: 10 vehículos con cuota_diaria de $45,000 cada uno:
- Esperado día = 10 × $45,000 = $450,000
- Esperado semana = $450,000 × 7 = $3,150,000

### 2. Cálculo de "Real" (Ingreso Ajustado)

El real representa lo que *realmente* se generó, considerando si el vehículo trabajó o no.

```
real = Σ (valor_generado si trabajó) + Σ (cuota_diaria si no trabajó y no hay registro)
```

**Lógica**:
- Si `trabajo = true`: usar `valor_generado` (o cuota_diaria si es null)
- Si `trabajo = false`: usar 0
- Si no hay registro: usar `cuota_diaria` (asumir trabajó)

### 3. Cálculo de "Gastos"

```
gastos = Σ (gasto de cada registro)
```

### 4. Cálculo de "Neto"

```
neto = real - gastos - administracion_semanal
```

### 5. Cálculo de Días Sin Trabajo

```
dias_sin_trabajo = Σ (contador de días donde trabajo = false)
```

---

## Reglas de Negocio

### Regla 1: Valores por Defecto en Control Semanal

> **Si no existe un registro para un vehículo en un día específico, el sistema usa:**
> - `trabajo = true`
> - `valor_generado = cuota_diaria del vehículo`
> - `gasto = 0`

**Razón**: Permite calcular el "esperado" sin necesidad de registro diario.

### Regla 2: Protección de Eliminación de Vehículos

> **Un vehículo no puede eliminarse si:**
> - Tiene contratos asociados
> - Tiene registros en el control semanal

**Razón**: Mantener integridad referencial y datos históricos.

### Regla 3: Estados de Contrato

> **Los contratos pueden estar en:**
> - `activo`: Vigente, el alquiler está en curso
> - `finalizado`: El período terminó normalmente
> - `cancelado`: Se canceló antes del fin del período

### Regla 4: Estados de Vehículo

> **Los vehículos pueden estar en:**
> - `activo`: En operación, disponible para alquilar
> - `inactivo`: Fuera de operación, no aparece en control semanal

### Regla 5: Tipos de Persona

> **Las personas pueden ser:**
> - `cliente`: Puede alquilar vehículos mediante contratos
> - `conductor`: Puede ser asignado a vehículos para conducirlos

### Regla 6: Descuentos por No Trabajar

> **Si un vehículo no trabaja en un día:**
> - El ingreso de ese día = 0
> - Se cuenta en "días sin trabajo" del resumen

### Regla 7: Administración Semanal

> **El costo de administración semanal:**
> - Se configurable desde el panel de control semanal
> - Se descuenta del cálculo del neto
> - Se persiste en la tabla de configuraciones

---

## Casos de Uso

### Caso 1: Registro Diario Normal

Un conductor trabaja todos los días de la semana.

```
Día: Lunes
Vehículo: CYF40I
Cuota diaria: $45,000

Al final del día:
- El conductor NO reporta cambios
- No hay registro en control_diarios

Resultado en el sistema:
- trabajo = true (por defecto)
- valor_generado = $45,000 (por defecto)
- gasto = 0 (por defecto)

Cálculo:
- Esperado += $45,000
- Real += $45,000
- Gastos += $0
```

### Caso 2: Conductor No Trabaja un Día

El conductor no puede trabajar un día específico.

```
Día: Martes
Vehículo: CYF40I
Cuota diaria: $45,000

Usuario marca "No trabajó" en el modal:
- trabajo = false
- valor_generado = 0
- gasto = 0

Cálculo:
- Esperado += $45,000 (se suma igual porque debería trabajar)
- Real += 0 (no generó nada)
- Gastos += $0
- dias_sin_trabajo += 1
```

### Caso 3: Gastos del Día

El conductor tiene gastos (mantenimiento, multa, etc.)

```
Día: Miércoles
Vehículo: CYF40I
Cuota diaria: $45,000
Gasto: $15,000

Usuario registra:
- trabajo = true
- valor_generado = $45,000
- gasto = $15,000

Cálculo:
- Esperado += $45,000
- Real += $45,000
- Gastos += $15,000
- Neto día = $45,000 - $15,000 = $30,000
```

### Caso 4: Ingreso Diferente a la Cuota

El conductor generó más o menos de la cuota diaria.

```
Día: Jueves
Vehículo: CYF40I
Cuota diaria: $45,000
Ingreso real: $52,000

Usuario registra:
- trabajo = true
- valor_generado = $52,000
- gasto = 0

Cálculo:
- Esperado += $45,000
- Real += $52,000 (mayor que la cuota)
- Diferencia: +$7,000 de ingresos adicionales
```

### Caso 5: Semana con Administración

Se descuenta el costo de administración del neto.

```
Resumen de la semana:
- Real (ingresos): $3,150,000
- Gastos: $150,000
- Administración: $200,000

Cálculo del neto:
neto = $3,150,000 - $150,000 - $200,000
neto = $2,800,000
```

---

## Glosario

| Término | Definición |
|---------|------------|
| **Cuota diaria** | Valor que el conductor debe pagar diariamente por usar el vehículo |
| **Esperado** | Cálculo teórico de ingresos basado en cuotas diarias × días |
| **Real** | Ingresos reales después de considerar días sin trabajar y ajustes |
| **Neto** | Ingresos reales menos gastos y administración |
| **Control Diario** | Registro de un día específico para un vehículo específico |
| **Control Semanal** | Conjunto de 7 días de control (domingo a sábado) |
| **Cliente** | Persona que alquila vehículos mediante contratos |
| **Conductor** | Persona que conduce vehículos de la flota |

---

*Manual de lógica generado para Rental Manager*