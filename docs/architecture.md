# Arquitectura de DOCNISYS

## Visión general

DOCNISYS es un monolito web Laravel 10 con renderizado del lado del servidor. Reúne dos dominios relacionados:

1. **Gestión documental:** documentos, categorías, versiones, aprobación, permisos, recordatorios y revisiones.
2. **Planificación ISO:** períodos, contexto FODA, riesgos y oportunidades, acciones, partes interesadas, proveedores, objetivos e informes.

Los dominios se conectan mediante referencias opcionales a `documentos`, utilizadas como evidencia interna. Cuando la evidencia no reside en el sistema, varios registros admiten un enlace externo.

## Capas y flujo de una solicitud

```text
Navegador
  -> routes/web.php o routes/iso.php
  -> middleware de autenticación, usuario habilitado y permisos
  -> controlador
  -> servicio de dominio cuando existe una regla compartida
  -> modelos Eloquent
  -> MySQL / almacenamiento configurado
  -> vista Blade
```

### Presentación

- Las rutas principales están en `routes/web.php`; la autenticación, en `routes/auth.php`; y planificación, en `routes/iso.php`.
- Las vistas están agrupadas por función en `resources/views/`.
- Blade genera HTML, Alpine.js aporta comportamiento liviano y Tailwind CSS define los estilos.
- Vite compila `resources/css/app.css` y `resources/js/app.js`.

### Aplicación

- `app/Http/Controllers/` implementa los casos de uso documentales y administrativos.
- `app/Http/Controllers/Iso/` implementa los casos de uso de planificación.
- `app/Services/Iso/` centraliza reglas reutilizables: correlativos, períodos abiertos y resultados de indicadores.
- `app/Console/Commands/` contiene procesos sin interfaz web, como recordatorios e importaciones históricas.

Los controladores actuales todavía contienen parte de la lógica de negocio. Al extender un flujo, debe evitarse duplicarla: una regla compartida o compleja debe extraerse a un servicio sin cambiar el comportamiento observable.

### Persistencia

- Los modelos de gestión documental están en `app/Models/`.
- Los modelos de planificación están en `app/Models/Iso/` y usan tablas con prefijo `iso_`.
- `database/migrations/` es la fuente de verdad del esquema evolutivo.
- MySQL es el motor esperado en desarrollo, pruebas y operación.

La aplicación usa claves foráneas con distintas políticas de borrado. `restrictOnDelete` protege evidencia que no debe desaparecer; `nullOnDelete` conserva el registro al perder una referencia opcional; `cascadeOnDelete` se reserva para componentes que pertenecen al agregado padre.

## Seguridad y autorización

Todas las rutas funcionales exigen autenticación y un usuario habilitado. La autorización se divide en dos mecanismos:

- Gestión documental: rol global `admin`, capacidad `create-invitations` y permisos por documento.
- Planificación ISO: middleware `iso:view`, `iso:manage` e `iso:admin`, respaldado por `iso_usuario_permisos`. El administrador global supera estos niveles.

No hay políticas Laravel registradas en `AuthServiceProvider`; parte de las comprobaciones se realiza en controladores y modelos. Toda nueva operación debe validar autorización en el servidor, aunque la interfaz también oculte la acción.

## Archivos, PDF y procesos externos

- Los documentos se gestionan mediante el disco configurado por Laravel; en operación se prevé Amazon S3.
- DOMPDF, FPDF y FPDI generan o componen exportaciones PDF.
- LibreOffice en modo headless convierte archivos Office cuando corresponde; su ejecutable se configura con `SOFFICE_PATH`.
- El scheduler ejecuta `recordatorios:procesar` cada minuto. La infraestructura debe mantener activo `php artisan schedule:work` o un cron equivalente.
- Las notificaciones usan canales de base de datos y correo.

## Trazabilidad

El dominio documental conserva versiones anteriores en `historial_documentos`. El dominio ISO registra cambios generales en `iso_historial_cambios` mediante `RegistraCambiosIso` y agrega tablas específicas para transiciones, verificaciones y revisiones.

Una transición de estado no está completa si sólo cambia la fila principal: deben mantenerse el motivo, el usuario, las fechas, la evidencia y el registro histórico exigidos por el flujo.

## Decisiones y restricciones conocidas

- La aplicación es un monolito; no existe una API pública ni una SPA separada.
- La base debe construirse aplicando toda la secuencia de migraciones.
- La suite usa MySQL y `sgd_testing`, no SQLite.
- El scheduler y LibreOffice son dependencias operativas externas.
- La cobertura automatizada es mayor en autenticación y planificación ISO que en los flujos documentales críticos.

## Documentos relacionados

- `CONTEXTO_PROYECTO.md`: propósito y alcance funcional general.
- `docs/development.md`: instalación, ejecución y validación.
- `docs/business-rules.md`: invariantes transversales del dominio.
- `docs/modules/proveedores.md`: detalle del ciclo de proveedores.
