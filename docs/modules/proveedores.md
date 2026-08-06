# Módulo de proveedores

## Objetivo

El módulo mantiene el catálogo de proveedores externos y la evidencia de su selección, evaluación periódica, tratamiento y reevaluación. Forma parte de Planificación ISO y se publica bajo `/planificacion/proveedores`.

Las operaciones de lectura requieren `iso:view`; altas, modificaciones y gestión del ciclo requieren `iso:manage`. El administrador global tiene acceso completo.

## Modelo

```text
Proveedor
  ├── Selección inicial (máximo una)
  ├── Evaluaciones
  │     ├── Riesgos vinculados
  │     ├── Acciones
  │     └── Reevaluación sucesora
  ├── Documento o enlace de referencia
  └── Historial de cambios y ciclo de vida
```

Tablas principales:

| Tabla | Responsabilidad |
| --- | --- |
| `iso_proveedores` | Catálogo, criticidad, periodicidad, estado y datos de baja/reactivación. |
| `iso_proveedor_selecciones` | Selección inicial y sus calificaciones. |
| `iso_proveedor_evaluaciones` | Evaluaciones periódicas, reevaluaciones y estado del ciclo. |
| `iso_proveedor_evaluacion_riesgo` | Relación entre evaluación y riesgos. |
| `iso_proveedor_acciones` | Tratamientos obligatorios u oportunidades de mejora. |
| `iso_proveedor_importaciones` | Lotes de importación y reversión. |

## Catálogo y ciclo de vida

El código del proveedor sigue el formato correlativo `PR-####`. La combinación de nombre y producto o servicio debe ser única. El catálogo registra área responsable, fecha de alta, criticidad (`critico` o `no_critico`), periodicidad en meses, documento, enlace y observaciones.

Estados:

- `activo`: admite selección, evaluaciones y nuevas acciones.
- `inactivo`: conserva toda la historia pero bloquea nuevas operaciones del ciclo.

Dar de baja o reactivar requiere confirmación y un motivo de al menos cinco caracteres. La operación registra fecha y motivo. La edición general no puede usarse para cambiar el estado.

Un proveedor sólo puede eliminarse si no posee selección ni evaluaciones. La eliminación requiere confirmación y motivo, y se incorpora manualmente al historial porque ya no existe la fila principal.

## Selección inicial

Cada proveedor admite una única selección inicial, protegida tanto por validación como por un índice único sobre `proveedor_id`.

Se califican:

- características, obligatoria;
- recomendaciones, opcional;
- precio y condiciones, opcional.

El puntaje es el promedio de los valores informados, redondeado a dos decimales. La conclusión actual se registra como “Selección inicial registrada”.

## Evaluación periódica

La evaluación pertenece a un período ISO abierto e incluye cuatro calificaciones enteras entre 2 y 10:

- precio/calidad;
- resolución de imprevistos;
- calidad del producto o servicio;
- calidad de atención.

El puntaje es el promedio simple, redondeado a dos decimales. La clasificación compartida con la selección es:

| Puntaje | Resultado |
| --- | --- |
| `>= 7` | `aprobado` |
| `>= 5` y `< 7` | `condicional` |
| `< 5` | `no_aprobado` |

Las decisiones aceptadas por la interfaz actual son `continuar`, `reemplazar` y `suspender`. Registros históricos pueden contener `continuar_con_acciones`, que sigue contemplado al recalcular ciclos.

Reglas asociadas:

- Resultado no aprobado o condicional: exige justificación.
- Decisión de reemplazar o suspender: exige justificación.
- Continuar con un resultado distinto de aprobado: exige una acción obligatoria.
- Continuar: exige una fecha de próxima evaluación posterior a la evaluación actual.
- Si se solicita una acción, deben informarse descripción, área y fecha objetivo.
- Los riesgos vinculados deben pertenecer al mismo período.
- El documento de evidencia debe existir y el usuario debe poder leerlo; el enlace externo debe ser una URL válida.

## Ciclo de tratamiento y reevaluación

Tipos de evaluación:

- `periodica`: inicia un ciclo.
- `reevaluacion`: continúa una evaluación anterior.

Estados del ciclo:

```text
Evaluación aprobada ------------------------------> cerrada
Evaluación con acción obligatoria -> en_tratamiento
en_tratamiento --acciones obligatorias cerradas--> pendiente_reevaluacion
pendiente_reevaluacion --nueva evaluación--------> cerrada
Decisión reemplazar/suspender sin pendientes-----> cerrada
```

Sólo puede existir un ciclo no cerrado por proveedor. La creación se ejecuta dentro de una transacción y bloquea el ciclo activo para evitar carreras. Una reevaluación debe señalar una evaluación anterior del mismo proveedor en estado `pendiente_reevaluacion`; al guardarse, cierra el ciclo anterior.

Una evaluación aprobada se cierra directamente, salvo que el flujo haya creado una acción obligatoria por la decisión adoptada. Las acciones opcionales pueden agregarse a una evaluación cerrada, pero una evaluación cerrada no acepta nuevas acciones obligatorias.

## Acciones

Estados permitidos:

- `pendiente`;
- `en_proceso`;
- `completada`;
- `cancelada`.

Una acción cerrada (`completada` o `cancelada`) no puede volver a modificarse mediante el flujo actual. Para cerrarla debe informarse un resultado o motivo. Tras cada cambio se recalcula el ciclo:

- si quedan acciones obligatorias abiertas, `en_tratamiento`;
- si todas cerraron y la decisión mantiene al proveedor, `pendiente_reevaluacion`;
- en los demás casos, `cerrada`.

Las acciones pertenecientes a evaluaciones con período sólo pueden alterarse mientras dicho período esté abierto.

## Consultas e informes

El listado permite filtrar por estado, resultado de la última evaluación y próxima evaluación vencida. El detalle reúne selección, evaluaciones, acciones, riesgos, evidencia e historial de bajas y reactivaciones. Los informes ISO incorporan el estado de proveedores y sus ciclos dentro del período seleccionado.

## Importación histórica

Comando de análisis e importación:

```bash
php artisan iso:importar-proveedores archivo.xlsx --dry-run
php artisan iso:importar-proveedores archivo.xlsx --usuario=ID --lote=UUID
```

La importación:

- acepta `.xlsx`;
- atribuye los registros a un usuario indicado o a un administrador disponible;
- usa un UUID de lote, generado si no se informa;
- calcula SHA-256 y bloquea la reimportación de un archivo ya importado;
- agrupa proveedor, selección y evaluaciones bajo `importacion_lote`;
- no modifica la planilla de origen;
- revierte la transacción completa con `--dry-run`.

Los datos importados que no pueden inferirse de la planilla quedan marcados para revisión en las observaciones.

## Reversión de importaciones

```bash
php artisan iso:revertir-proveedores UUID --dry-run
php artisan iso:revertir-proveedores UUID --usuario=ID --confirmar=REVERTIR
```

La reversión sólo elimina registros creados por el lote. Se bloquea si una evaluación tiene acciones, riesgos o reevaluaciones ajenas al lote, o si un proveedor recibió selección o evaluaciones posteriores. La ejecución real requiere la confirmación literal `REVERTIR`; después marca el lote como `revertido` con usuario y fecha.

## Código y pruebas relacionados

- `app/Http/Controllers/Iso/ProveedorController.php`
- `app/Models/Iso/Proveedor*.php`
- `app/Console/Commands/ImportarEvaluacionProveedores.php`
- `app/Console/Commands/RevertirImportacionProveedores.php`
- `resources/views/iso/proveedores/`
- `tests/Feature/Iso/ProveedorFlowTest.php`

La prueba funcional cubre el ciclo principal, obligatoriedad de acciones, selección única, reevaluación, baja/reactivación, borrado condicionado e importación reversible.
