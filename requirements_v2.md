Kokoro Pollo v2.1 — Funcionalidades Pendientes y Mejoras Previas a Producción
Estado General

El sistema actualmente cubre aproximadamente el 95% de la operación real del negocio.

Se encuentran implementados:

Autenticación
Usuarios
Inventario
Ventas POS
Caja
Apertura de caja
Cierre de caja
Créditos a empleados
ALSÉS
Reportes
Alertas de condimentos
Pedidos para llevar

Sin embargo aún existen funcionalidades y ajustes necesarios antes de considerar el sistema listo para producción.

PRIORIDAD ALTA
1. Implementación del Rol Jefe
Problema

Actualmente existen únicamente:

Administrador
Empleado

Pero el negocio opera realmente con:

Jefe
Administrador
Operador
Objetivo

Separar responsabilidades.

Rol Empleado

Acceso:

Ventas
Caja
Inventario

Restricciones:

No puede ver reportes
No puede crear usuarios
No puede modificar configuraciones
No puede abrir ni cerrar caja
Rol Administrador

Acceso:

Todo lo del empleado
Apertura de caja
Cierre de caja
Créditos
ALSÉS

Restricciones:

No puede administrar usuarios
No puede cambiar configuraciones críticas
No puede acceder a reportes gerenciales globales
Rol Jefe

Acceso total:

Usuarios
Configuración
Reportes
Auditoría
Todas las operaciones administrativas
Cambios requeridos

Base de datos:

Agregar nuevo valor:

Jefe

Actualizar:

ENUM rol
Middleware
Dashboard
Usuarios
Menús
2. Eliminación Completa de Broaster
Contexto

Durante el levantamiento funcional se confirmó que el negocio no vende pollo broaster.

Objetivo

Simplificar completamente la experiencia.

Eliminar

Configuración:

precio_broaster_cuarto
precio_broaster_medio
precio_broaster_entero

Ventas:

Eliminar selector:

Asado
Broaster

Mantener únicamente:

Pollo Asado

Base de datos

Eliminar dependencias innecesarias.

Conservar migraciones históricas si existen datos previos.

Beneficio:

Reducir clics.

Reducir errores.

Reducir complejidad visual.

3. Ventas por Empleado
Objetivo

Permitir al jefe medir desempeño operativo.

Indicadores

Por empleado:

Pedidos realizados
Ventas generadas
Promedio por pedido
Ventas por día
Ventas por rango de fechas

Reportes

Diario

Semanal

Mensual

PRIORIDAD MEDIA
4. Dashboard Ejecutivo
Objetivo

Crear un panel especializado para el jefe.

Indicadores principales

Hoy

Ventas
Utilidad estimada
Créditos activos
Gastos

Mes

Ventas acumuladas
Ticket promedio
Productos más vendidos

Alertas

Créditos vencidos
Inventario crítico
Condimentos críticos
Caja sin apertura
Caja sin cierre
5. Inventario Automático de Empaques
Contexto

El negocio utiliza cajas para despachar pedidos para llevar.

Actualmente no existe automatización.

Objetivo

Si un pedido es:

Para llevar

descontar automáticamente:

Caja de empaque

del inventario.

Requisitos

Configurable.

Activable o desactivable.

6. Exportación de Reportes
Objetivo

Permitir compartir información fuera del sistema.

Formatos

Excel (.xlsx)

PDF

CSV

Aplicar a:

Reportes diarios
Reportes semanales
Reportes mensuales
Créditos
Cierre de caja
PRIORIDAD BAJA
7. Auditoría Operativa

Registrar:

Usuario
Acción
Fecha
Valor anterior
Valor nuevo

Módulos

Inventario

Usuarios

Configuración

Créditos

Caja

8. Historial de Precios

Registrar cambios históricos de precios.

Permitir responder:

Cuándo cambió un precio
Quién lo cambió
Valor anterior
Valor nuevo
9. Sistema de Respaldo

Generar:

Backup manual
Backup programado

Permitir:

Restauración
REGLAS UX OBLIGATORIAS

Este sistema está diseñado para personas con conocimientos tecnológicos limitados.

Muchos usuarios nunca han utilizado software administrativo.

Por lo tanto:

Minimizar clics.
Minimizar formularios.
Minimizar navegación.
Mantener botones grandes.
Mantener textos claros.
Evitar lenguaje técnico.
Evitar tablas complejas.
Evitar configuraciones avanzadas visibles.  

Cada pantalla debe responder:

"¿Qué necesita hacer el usuario en este momento?"

y eliminar cualquier elemento que no aporte a esa tarea.

La simplicidad debe tener prioridad sobre la cantidad de funcionalidades.