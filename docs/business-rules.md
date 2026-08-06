# Reglas de negocio

Este documento reúne invariantes observadas en el código. No sustituye procedimientos organizacionales ni constituye por sí solo evidencia de conformidad con ISO 9001.

## Usuarios y acceso

- Sólo usuarios autenticados y habilitados pueden operar el sistema.
- El rol global `admin` administra usuarios y tiene acceso completo al módulo ISO.
- Las invitaciones requieren la capacidad `create-invitations`.
- Los permisos documentales se asignan por usuario y documento: leer, escribir, aprobar y eliminar.
- Un permiso superior habilita lectura. El creador obtiene todos los permisos al dar de alta el documento.
- Planificación usa niveles acumulativos: `view`, `manage` y `admin`.

La autorización se valida en el servidor. Ocultar botones no reemplaza esa comprobación.

## Documentos

- Un documento controlado se crea o actualiza como `pendiente de aprobación`.
- Un registro sin aprobación conserva el estado `registro`.
- Sólo un usuario autorizado puede aprobar o rechazar.
- La aprobación registra aprobador y fecha.
- El rechazo mantiene el documento pendiente y notifica al último editor o, en su defecto, al creador.
- Antes de sustituir un archivo o sus datos principales, la versión vigente se guarda en el historial.
- Restaurar una versión histórica también debe preservar la versión que estaba vigente antes de la restauración.
- Para una exportación controlada se usa la versión actual aprobada o, si no lo está, la última versión aprobada disponible.
- Una categoría con documentos, historial o subcategorías no puede eliminarse.

## Recordatorios y revisiones

- Un recordatorio puede ser único, diario, semanal, mensual o anual y tener varios destinatarios.
- El procesamiento programado crea ejecuciones por destinatario y calcula la próxima fecha.
- Una ejecución puede resolverse o postergarse.
- La resolución puede producir una revisión con resultado, observación, necesidad de nueva versión y evidencia.
- El scheduler activo es una precondición operativa para la generación automática.

## Períodos ISO

- Cada período anual es único y se encuentra en `borrador`, `vigente` o `cerrado`.
- `borrador` y `vigente` son estados abiertos; `cerrado` impide nuevas mutaciones de planificación.
- Activar, cerrar y reabrir son transiciones explícitas y auditables.
- Un cierre conserva usuario, fecha, motivo y resumen de control según el flujo.
- La evaluación de relevancia del cambio climático y su fundamento pertenecen al período.

## Contexto, riesgos y acciones

- Los elementos FODA se numeran por tipo y período; el código identifica tipo, año y correlativo.
- Los riesgos y oportunidades se numeran por período y conservan valoración inicial.
- Impacto y probabilidad determinan el índice; las verificaciones conservan eficacia, conclusión y valoración resultante.
- Las acciones poseen responsable, fecha objetivo, estado y seguimiento.
- Cerrar o cancelar una acción requiere el resultado o motivo exigido por el flujo.
- Las transiciones de período, riesgo y acción deben conservarse en sus historiales específicos.
- La continuidad entre períodos crea un nuevo registro vinculado al origen; no sobrescribe el período anterior.

## Partes interesadas

- La parte interesada es permanente; su evaluación pertenece a un período.
- Se documentan necesidades, requisitos, pertinencia para el SGC, responsable y método de medición.
- Los requisitos climáticos se registran cuando aplican.
- Una evaluación puede relacionarse con riesgos del mismo período y con evidencia interna o externa.

## Objetivos de calidad

- Cada objetivo pertenece a un período y tiene código correlativo.
- Puede vincular contexto, riesgos y partes interesadas.
- Las mediciones pertenecen a indicadores y pueden aportar documento o enlace como evidencia.
- La agregación del indicador puede ser último valor, promedio, suma, variación o variación porcentual.
- El comparador y la tolerancia determinan `cumplido`, `aceptable`, `incumplido` o `pendiente` mediante `ResultadoObjetivoService`.
- Revisiones, cambios sustanciales y continuidad entre períodos deben conservar valores anteriores, motivo y vigencia.

## Proveedores

- El catálogo identifica de forma única una combinación de nombre y producto o servicio.
- Cada proveedor tiene una sola selección inicial.
- Un proveedor inactivo no admite selección, evaluación ni nuevas acciones.
- Las evaluaciones sólo se registran en períodos abiertos y los riesgos asociados deben pertenecer al mismo período.
- Sólo puede existir un ciclo de evaluación no cerrado por proveedor y prestación.
- Un resultado condicional o no aprobado con decisión de continuar exige justificación y acción obligatoria.
- Continuar exige programar una próxima evaluación.
- La reevaluación se habilita después de cerrar todas las acciones obligatorias.
- La baja y reactivación requieren confirmación y motivo; editar datos generales no cambia el estado.
- Sólo puede eliminarse un proveedor sin selección ni evaluaciones, con confirmación y motivo. La eliminación queda auditada.

El detalle y los umbrales están en `docs/modules/proveedores.md`.

## Evidencia y trazabilidad

- Las referencias a documentos deben respetar los permisos documentales del usuario.
- Un enlace externo debe ser una URL válida.
- La evidencia histórica no se elimina por conveniencia; se aplican las restricciones de la base y del agregado.
- Los cambios ISO relevantes se registran en `iso_historial_cambios` y, cuando corresponde, en tablas especializadas.
- Importaciones, cierres, reaperturas, bajas y reversiones deben ser atribuibles a un usuario.
