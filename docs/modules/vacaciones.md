# Módulo de vacaciones

## Alcance inicial

El menú principal se divide en tres accesos:

- **Solicitud / estado de licencias**, disponible para todos los empleados;
- **Admin de licencias**, disponible para jefes con colaboradores activos;
- **Configuración laboral**, disponible sólo para el administrador general.

La configuración laboral sólo permite asignar usuario, área, fecha de ingreso y
jefe de área. El rol de jefe se obtiene automáticamente cuando un usuario tiene
al menos un colaborador activo asignado.

La administración de licencias incluye el resumen del equipo, un cronograma
Gantt de vacaciones planificadas, las solicitudes para validar y el historial
anual completo de solicitudes de los colaboradores.

Cuando una solicitud es aprobada, rechazada o desaprobada, el empleado recibe
un correo con el período, la cantidad de días, el nuevo resultado y, cuando
corresponde, el motivo informado por el jefe.

El módulo tiene tres niveles de acceso. Un usuario consulta sólo su resumen,
envía solicitudes y puede cancelar las que aún están pendientes. Un jefe es un
usuario al que el administrador general le asignó al menos un colaborador y ve
el resumen de sus colaboradores directos, además de aprobar o rechazar sus
solicitudes. El administrador general conserva acceso completo.

La cantidad anual se calcula con la antigüedad al 31 de diciembre del período:

- hasta 5 años: 14 días corridos;
- más de 5 y hasta 10 años: 21 días corridos;
- más de 10 y hasta 20 años: 28 días corridos;
- más de 20 años: 35 días corridos.

La tabla de tramos está en `config/vacaciones.php` para poder adaptarla a un
convenio o política interna sin modificar el servicio. La fecha de ingreso,
área y jefe se configuran desde la pantalla de Vacaciones por un administrador.

## Datos y estados

Las solicitudes se almacenan en `vacaciones_solicitudes` y conservan fechas,
días corridos, observaciones, estado, revisor y motivo de rechazo. No se guarda
un saldo mutable: el resumen se deriva de las solicitudes aprobadas y
pendientes, evitando desajustes por ediciones manuales.

La primera versión no contempla todavía saldos transferidos, licencias
proporcionales por días efectivamente trabajados, feriados especiales,
notificaciones específicas ni liquidación al egreso. Esos casos deben
incorporarse antes de usar el módulo como fuente única de liquidación laboral.
