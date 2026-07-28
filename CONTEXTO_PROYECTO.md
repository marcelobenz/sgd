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

Los documentos se organizan en categorías jerárquicas. Una categoría puede tener una categoría padre y subcategorías. El sistema impide eliminar categorías que todavía tengan documentos, historial o subcategorías asociados.

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

El dashboard presenta métricas y actividad básica:

- total de documentos;
- documentos aprobados;
- documentos pendientes;
- registros;
- pendientes que el usuario actual puede aprobar;
- recordatorios vencidos;
- próximos recordatorios;
- últimos documentos modificados.

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
- La cobertura de pruebas automatizadas visible se concentra en autenticación y perfil; los flujos críticos de documentos, permisos, aprobación, versionado y recordatorios necesitan pruebas específicas.
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

## 11. Fuentes internas utilizadas

Este contexto fue elaborado a partir de:

- rutas web y de autenticación;
- modelos Eloquent;
- controladores de documentos, categorías, usuarios, invitaciones y recordatorios;
- migraciones de base de datos;
- comando programado de recordatorios;
- configuración de dependencias PHP y JavaScript;
- vistas y estructura general del repositorio.
