# Contexto del proyecto — DOCNISYS / Sistema de Gestión Documental

## 1. Propósito

DOCNISYS es un sistema web interno para administrar y controlar la documentación de la organización. Su uso principal es acompañar el sistema de gestión de la calidad y facilitar el seguimiento de la norma ISO 9001, especialmente en lo relativo al control de la información documentada.

La aplicación centraliza documentos, responsables, permisos, versiones, aprobaciones, revisiones y recordatorios. De esta manera permite saber qué documento está vigente, quién lo creó o modificó, quién lo aprobó y qué versiones existieron anteriormente.

> Este documento describe el alcance observado en el código fuente. La aplicación aporta controles útiles para ISO 9001, pero no debe interpretarse por sí sola como evidencia de cumplimiento integral o certificación de la norma.

## 2. Objetivos del sistema

- Centralizar la documentación interna en un repositorio controlado.
- Organizar los documentos mediante categorías y subcategorías.
- Mantener trazabilidad sobre creación, modificación, aprobación y versionado.
- Evitar el acceso o la modificación por parte de usuarios no autorizados.
- Identificar y distribuir la última versión aprobada de cada documento.
- Programar revisiones periódicas y conservar constancia de su resolución.
- Notificar a responsables sobre aprobaciones, rechazos y revisiones pendientes.
- Proveer indicadores básicos sobre el estado de la documentación.

## 3. Alcance funcional actual

### Gestión de documentos

Cada documento contiene, como mínimo:

- título;
- archivo adjunto;
- descripción o detalle de la versión;
- categoría;
- estado;
- número de versión;
- creador;
- usuario de la última modificación;
- aprobador y fecha de aprobación, cuando corresponda;
- permisos asignados por usuario.

Los formatos admitidos al crear nuevas versiones son PDF, Word, Excel y PowerPoint. Los archivos se almacenan en Amazon S3.

### Clasificación

Los documentos se organizan en una jerarquía de dos niveles: categoría principal y subcategoría. Una subcategoría no puede contener otras categorías. El sistema impide eliminar categorías que todavía tengan documentos, historial o subcategorías asociados.

El ABM de categorías presenta la jerarquía completa mediante una tabla expandible, con búsqueda, filtros, conteos documentales directos y acumulados, y accesos contextuales para crear subcategorías. El alta y la edición permiten previsualizar la ubicación elegida y sólo ofrecen categorías principales como posibles padres.

Los formularios de alta y edición documental muestran las subcategorías agrupadas visualmente debajo de su categoría principal. Cuando el alta se inicia desde el panel de una categoría o subcategoría en Documentos, esa clasificación se propone automáticamente y puede ser modificada antes de guardar.

### Versionado e historial

Antes de reemplazar el contenido o archivo de un documento, la versión actual se archiva en `historial_documentos`. El historial conserva los principales metadatos de la versión, incluyendo archivo, estado, categoría, usuarios relacionados, fecha de aprobación y número de versión.

El sistema permite:

- consultar versiones anteriores;
- crear una nueva versión;
- restaurar una versión histórica;
- conservar la versión actual antes de una restauración;
- localizar la última versión aprobada.

### Flujo de aprobación

Existen dos modalidades:

1. **Documento controlado:** se crea o actualiza en estado `pendiente de aprobación`; un usuario con permiso de aprobación puede aprobarlo o rechazarlo.
2. **Registro sin aprobación:** permanece en estado `registro` y no atraviesa el circuito de aprobación.

Estados funcionales vigentes:

- `pendiente de aprobación`;
- `aprobado`;
- `registro`.

Al aprobar un documento se registran el aprobador y la fecha de aprobación. Al rechazarlo, el documento continúa pendiente y se notifica al último editor —o al creador como alternativa— junto con los comentarios del rechazo.

Cuando se solicita una exportación controlada, si la versión actual no está aprobada el sistema intenta utilizar la última versión aprobada del historial. La exportación PDF agrega una hoja con datos de control como versión, categoría, estado, aprobador, creador y fechas relevantes.

### Permisos

Los permisos se asignan por documento y por usuario:

- leer;
- escribir;
- aprobar;
- eliminar.

Cualquier permiso de nivel superior también habilita la lectura. El creador recibe todos los permisos sobre el documento al momento del alta. Además, los usuarios pueden ser habilitados o deshabilitados; un usuario deshabilitado no puede continuar utilizando el sistema.

Existe un rol global `admin` para la administración de usuarios. La creación de invitaciones se controla mediante la capacidad `create-invitations`.

### Recordatorios y revisiones

Un documento puede tener recordatorios asociados a uno o más usuarios. Las frecuencias soportadas son:

- una sola vez;
- diaria;
- semanal;
- mensual;
- anual.

Los recordatorios pueden generar notificaciones internas y por correo. Una tarea programada ejecuta `recordatorios:procesar` cada minuto, crea una ejecución pendiente para cada destinatario y calcula la próxima fecha.

Cada usuario dispone de:

- una lista de revisiones pendientes, vencidas y próximas;
- una vista de calendario;
- acciones para resolver o postergar una revisión;
- registro de observaciones al resolver;
- posibilidad de dejar constancia del resultado y de si se requiere una nueva versión.

La entidad `documento_revisiones` está orientada a conservar evidencia de la revisión: fecha, usuario, resultado, observación, necesidad de nueva versión y archivo de evidencia.

### Notificaciones

La aplicación cuenta con notificaciones para:

- documento pendiente de aprobación;
- documento aprobado;
- documento rechazado;
- recordatorio de documento.

Las notificaciones pueden consultarse dentro del sistema y marcarse como leídas.

### Panel de control

El dashboard principal prioriza la planificación ISO del período vigente y adapta su contenido a los permisos del usuario. Presenta indicadores visuales de riesgos, objetivos y acciones para quienes pueden consultar ISO, un resumen de acciones personales para quienes pueden gestionarlas y un bloque documental compacto con aprobaciones y revisiones.

La ruta `/mis-pendientes` funciona como bandeja personal unificada. Reúne, sin duplicar persistencia, aprobaciones y revisiones documentales junto con acciones de riesgos, verificaciones de eficacia, acciones de objetivos y acciones de proveedores asignadas al usuario. La bandeja sólo muestra acciones ISO ejecutables a usuarios con capacidad de gestión; cada elemento se resuelve en la ficha de su módulo de origen para preservar permisos y trazabilidad.

Desde `Mi perfil` cada usuario puede elegir su página de inicio, el filtro inicial y la densidad de `Mis pendientes`, y el horizonte utilizado para identificar próximos vencimientos. Los destinos se validan contra sus permisos y Planificación ISO no puede configurarse como inicio sin acceso al módulo.

El perfil también admite iniciales automáticas, un avatar predefinido o una foto personal. Las fotos se validan y almacenan en S3; se sirven mediante una ruta autenticada. La única foto cargada se conserva aunque el usuario seleccione temporalmente iniciales o un avatar predefinido, y una nueva carga reemplaza y elimina la foto anterior. El avatar se utiliza de forma acotada en el menú, la página de perfil, la cabecera de la bandeja y fichas ISO donde reconocer al responsable aporta contexto, sin incorporarlo indiscriminadamente a las tablas.

## 4. Usuarios y responsabilidades

| Perfil o responsabilidad | Alcance observado |
| --- | --- |
| Administrador | Gestiona usuarios habilitados y deshabilitados; puede acceder a funciones administrativas. |
| Creador o editor | Carga documentos, actualiza metadatos y genera nuevas versiones según sus permisos. |
| Aprobador | Aprueba o rechaza versiones de documentos para los que recibió permiso. |
| Lector | Consulta y descarga los documentos autorizados. |
| Responsable de revisión | Recibe recordatorios, revisa documentos y registra la resolución o postergación. |

Una misma persona puede asumir varias responsabilidades según los permisos asignados en cada documento.

## 5. Relación con ISO 9001

El sistema da soporte principalmente al control de la información documentada mediante:

- identificación del documento y su versión;
- revisión y aprobación antes de su liberación;
- control de acceso y modificación;
- disponibilidad de la versión aprobada;
- conservación de versiones anteriores;
- trazabilidad de responsables y fechas;
- revisiones periódicas y registro de evidencias;
- protección frente a eliminaciones no autorizadas.

Para evaluar cumplimiento normativo deben considerarse también los procedimientos organizacionales, criterios de retención, respaldo y recuperación, tratamiento de documentos externos, capacitación, auditorías, gestión de riesgos y demás controles que no necesariamente están representados en esta aplicación.

## 6. Modelo conceptual

```text
Usuario
  ├── crea / modifica / aprueba documentos
  ├── recibe permisos por documento
  └── recibe y resuelve recordatorios

Categoría
  ├── puede depender de otra categoría
  └── agrupa documentos

Documento
  ├── pertenece a una categoría
  ├── tiene permisos por usuario
  ├── conserva versiones en el historial
  ├── puede requerir aprobación
  ├── tiene recordatorios
  └── registra revisiones

Recordatorio
  ├── pertenece a un documento
  ├── tiene uno o más destinatarios
  └── genera ejecuciones periódicas

Ejecución de recordatorio
  └── puede producir una revisión documentada
```

## 7. Arquitectura técnica

- **Backend:** PHP 8.1 o superior y Laravel 10.
- **Base de datos:** MySQL.
- **Frontend:** Blade, Tailwind CSS, Alpine.js y Vite.
- **Autenticación:** Laravel Breeze.
- **Autorización:** permisos propios por documento y paquete `spatie/laravel-permission`.
- **Archivos:** Amazon S3 mediante Flysystem.
- **PDF:** DOMPDF, FPDF y FPDI.
- **Conversión de documentos:** LibreOffice en modo headless, configurable mediante `SOFFICE_PATH`.
- **Notificaciones:** base de datos y correo.
- **Procesos periódicos:** scheduler de Laravel.

### Componentes principales

```text
app/Http/Controllers/DocumentoController.php
    Operaciones, aprobación, permisos, versiones, descargas y exportación.

app/Http/Controllers/DocumentoRecordatorioController.php
    Configuración, calendario y resolución de recordatorios y revisiones.

app/Console/Commands/ProcesarRecordatoriosDocumentos.php
    Generación automática de ejecuciones y notificaciones.

app/Models/
    Entidades y relaciones del dominio.

resources/views/
    Interfaces Blade de documentos, categorías, recordatorios, usuarios y dashboard.

database/migrations/
    Evolución del esquema de datos.
```

## 8. Operación y configuración

La instalación requiere, como mínimo:

- PHP y Composer;
- Node.js y npm;
- una base MySQL;
- credenciales de S3;
- configuración de correo si se enviarán notificaciones;
- LibreOffice para convertir archivos Office a PDF;
- ejecución permanente del scheduler de Laravel.

Comandos habituales:

```bash
composer install
npm install
php artisan migrate
npm run build
php artisan schedule:work
```

Las variables sensibles y específicas del entorno se configuran en `.env`. No deben incorporarse credenciales reales a la documentación ni al control de versiones.

## 9. Consideraciones y deuda técnica observada

- El `README.md` actual es introductorio y su bloque de instalación está incompleto.
- Las migraciones históricas muestran estados anteriores como `en curso`; migraciones posteriores los alinean con los estados funcionales actuales. La base debe construirse aplicando la secuencia completa de migraciones.
- Parte de la autorización se implementa directamente en controladores y modelos; no hay políticas de Laravel registradas para documentos.
- Algunas rutas administrativas dependen de validaciones dentro del controlador, mientras que otras utilizan middleware o capacidades. Conviene unificar este criterio.
- El dashboard contiene el indicador `tramitesPendientes` como valor fijo en cero; actualmente no existe un módulo real de trámites.
- Hay pruebas funcionales para autenticación, perfil y varios flujos ISO; los flujos críticos de documentos, permisos documentales, aprobación, versionado y recordatorios todavía necesitan pruebas específicas.
- El funcionamiento de los recordatorios depende de que el scheduler esté activo en el entorno.
- La exportación de archivos Office depende de LibreOffice y de su correcta configuración.
- Deben definirse fuera del código políticas organizacionales de respaldo, restauración, retención y disposición de documentos.

## 10. Glosario

- **Documento controlado:** documento que debe ser revisado y aprobado antes de considerarse vigente.
- **Registro:** evidencia de una actividad o resultado que no requiere el mismo flujo de aprobación.
- **Versión vigente:** versión actual aprobada y autorizada para su uso.
- **Historial:** conjunto de versiones anteriores conservadas por el sistema.
- **Revisión:** comprobación periódica de que un documento continúa siendo adecuado y aplicable.
- **Evidencia:** información que permite demostrar que una revisión o actividad fue realizada.

## 11. Módulo de planificación ISO

Además del control documental, la aplicación incluye un módulo bajo `/planificacion` para mantener evidencia operativa de la planificación del SGC. El acceso se divide en tres niveles acumulativos: consulta (`view`), gestión (`manage`) y administración (`admin`). El rol global `admin` tiene acceso completo.

### Períodos de planificación

Los registros de contexto, riesgos, evaluaciones de partes interesadas y objetivos se organizan por período anual. Un período puede estar en `borrador`, `vigente` o `cerrado`; sólo `borrador` y `vigente` se consideran abiertos para nuevas mutaciones. La activación, cierre y reapertura dejan transiciones auditables. El período también documenta si el cambio climático es relevante para el SGC y su fundamento.

### Contexto FODA y riesgos

El análisis de contexto registra fortalezas, debilidades, oportunidades y amenazas con código correlativo por período, fuente, proceso, responsable, relevancia y decisión de tratamiento. Los elementos pertinentes pueden originar riesgos u oportunidades.

Cada riesgo u oportunidad conserva valoración inicial de impacto y probabilidad, acciones de tratamiento, seguimientos, fecha de verificación, criterio y conclusión de eficacia, valoración final y transiciones de estado. La continuidad entre períodos se representa mediante una referencia al registro de origen, sin sobrescribir el historial anterior.

### Partes interesadas

Las partes interesadas son un catálogo permanente. Sus necesidades, requisitos, responsable, método de medición y consideraciones climáticas se evalúan por período. Una evaluación puede adjuntar evidencia documental o externa y vincular riesgos derivados.

### Proveedores

El catálogo de proveedores incluye criticidad, estado y periodicidad. Se registran selección inicial, ciclos de evaluación o reevaluación, calificaciones, decisiones, acciones correctivas y vínculos con riesgos. Existen comandos para importar evaluaciones históricas y revertir una importación por lote; estas operaciones deben conservar la trazabilidad del lote.

### Objetivos de calidad

Los objetivos se definen por período y pueden relacionarse con contexto, riesgos y partes interesadas. Incluyen responsables, plazos, indicadores, metas, tolerancias, mediciones, acciones y evaluaciones. Los resultados admiten agregación por último valor, promedio, suma, variación o variación porcentual; el cumplimiento se determina de forma centralizada en `ResultadoObjetivoService`.

Las revisiones y continuidades de objetivos preservan los valores anteriores y su vigencia. Los informes del módulo reúnen vistas ejecutivas, resumidas y detalladas de la planificación.

### Trazabilidad transversal

Las entidades ISO relevantes registran cambios en `iso_historial_cambios` mediante el concern `RegistraCambiosIso`. Los flujos sensibles agregan registros específicos de transición, revisión o verificación. Esta evidencia forma parte del comportamiento funcional y no debe omitirse al implementar nuevas operaciones.

## 12. Mapa del repositorio

```text
app/
  Console/Commands/       tareas programadas e importaciones
  Http/Controllers/       gestión documental, usuarios y recordatorios
  Http/Controllers/Iso/   casos de uso de planificación ISO
  Models/                 dominio documental
  Models/Iso/             dominio de planificación ISO
  Services/Iso/           reglas compartidas del módulo ISO
database/migrations/      esquema evolutivo completo
resources/views/          vistas Blade por módulo
routes/                   rutas web, autenticación e ISO
tests/Feature/Iso/        pruebas funcionales de planificación
```

Las migraciones son la fuente de verdad del esquema. Para comprender una tabla creada en una migración inicial deben revisarse también las migraciones posteriores que agregan campos, estados o restricciones.

## 13. Desarrollo y verificación

La suite PHPUnit usa MySQL con la base `sgd_testing`, cola síncrona, sesión y caché en memoria. La base de pruebas debe ser independiente de cualquier base con datos reales.

Comprobaciones habituales:

```bash
php artisan test --filter=NombreDelTest
php artisan test tests/Feature/Iso
php artisan test
vendor/bin/pint --test
npm run build
```

Las pruebas ISO existentes cubren planificación, proveedores, objetivos e informes. Todavía se necesitan pruebas específicas más amplias para documentos, permisos documentales, aprobación, versionado y recordatorios.

## 14. Estado del contexto

Este documento refleja la estructura observada en el repositorio al 6 de agosto de 2026. Puede incluir trabajo aún no confirmado presente en el árbol local; antes de desarrollar debe revisarse `git status` y preservarse cualquier cambio ajeno.

## 15. Fuentes internas utilizadas

Este contexto fue elaborado a partir de:

- rutas web y de autenticación;
- modelos Eloquent;
- controladores de documentos, categorías, usuarios, invitaciones y recordatorios;
- migraciones de base de datos;
- comando programado de recordatorios;
- configuración de dependencias PHP y JavaScript;
- rutas, modelos, servicios, migraciones y pruebas del módulo ISO;
- vistas y estructura general del repositorio.

## 16. Documentación complementaria

- `docs/architecture.md`: componentes, capas, integraciones y decisiones técnicas.
- `docs/development.md`: preparación del entorno, pruebas y flujo de desarrollo.
- `docs/business-rules.md`: invariantes funcionales transversales.
- `docs/modules/proveedores.md`: ciclo completo de selección y evaluación de proveedores.
