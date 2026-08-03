<?php

namespace Tests\Feature\Iso;

use App\Models\Iso\Periodo;
use App\Models\Iso\Proveedor;
use App\Models\Iso\ProveedorEvaluacion;
use App\Models\Iso\Riesgo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProveedorFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_keeps_selection_periodic_evaluation_action_and_linked_risk(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id]);
        $riesgo = Riesgo::create([
            'periodo_id' => $periodo->id, 'numero' => 1, 'codigo' => 'RO-2026-001', 'tipo' => 'riesgo',
            'proceso' => 'Infraestructura', 'identificacion' => 'Dependencia de proveedor cloud.',
            'efecto_potencial' => 'Interrupción del servicio.', 'impacto_inicial' => 3, 'probabilidad_inicial' => 2,
            'indice_inicial' => 6, 'estado' => 'pendiente', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id,
        ]);

        $this->actingAs($admin)->post(route('planificacion.proveedores.store'), [
            'nombre' => 'Telecom', 'producto_servicio' => 'Cloud Services', 'area_responsable' => 'Infraestructura',
            'fecha_alta' => '2022-09-01', 'criticidad' => 'critico', 'periodicidad_meses' => 12,
        ])->assertSessionHasNoErrors();
        $proveedor = Proveedor::firstOrFail();

        $this->actingAs($admin)->post(route('planificacion.proveedores.selecciones.store', $proveedor), [
            'fecha' => '2022-09-01', 'caracteristicas' => 8, 'recomendaciones' => 7,
            'precio_condiciones' => 6,
        ])->assertSessionHasNoErrors();
        $this->assertSame('aprobado', $proveedor->selecciones()->firstOrFail()->resultado);

        $this->actingAs($admin)->post(route('planificacion.proveedores.evaluaciones.store', $proveedor), [
            'periodo_id' => $periodo->id, 'fecha_evaluacion' => '2026-06-04',
            'precio_calidad' => 4, 'resolucion_imprevistos' => 6, 'calidad_producto' => 8, 'calidad_atencion' => 6,
            'decision' => 'continuar', 'requiere_accion' => 1, 'conclusion' => 'El servicio es estable pero el costo debe revisarse.',
            'justificacion' => 'No existe un reemplazo inmediato con el mismo alcance.', 'proxima_evaluacion' => '2027-06-04',
            'requiere_analisis_riesgo' => 1, 'riesgos' => [$riesgo->id],
            'accion_descripcion' => 'Negociar condiciones comerciales.', 'accion_area_responsable' => 'Administración',
            'accion_responsable_id' => $admin->id, 'accion_fecha_objetivo' => '2026-09-30',
        ])->assertSessionHasNoErrors();

        $evaluacion = ProveedorEvaluacion::firstOrFail();
        $this->assertSame('condicional', $evaluacion->resultado);
        $this->assertSame('en_tratamiento', $evaluacion->estado_ciclo);
        $this->assertSame('6.00', $evaluacion->puntaje);
        $this->assertTrue($evaluacion->riesgos->contains($riesgo));
        $this->assertDatabaseHas('iso_proveedor_acciones', ['evaluacion_id' => $evaluacion->id, 'estado' => 'pendiente']);
        $this->actingAs($admin)->get(route('planificacion.proveedores.show', $proveedor))->assertOk()->assertSee('Evaluaciones')->assertSee('Negociar condiciones comerciales.')->assertSee('RO-2026-001');
        $this->actingAs($admin)->get(route('planificacion.informes.index', ['periodo' => $periodo->id]))->assertOk()->assertSee('Proveedores externos y evaluación periódica')->assertSee('Telecom');
    }

    public function test_conditional_supplier_cannot_continue_without_action_and_justification(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $proveedor = Proveedor::create(['codigo' => 'PR-0001', 'nombre' => 'Proveedor', 'producto_servicio' => 'Servicio', 'area_responsable' => 'Administración', 'criticidad' => 'no_critico', 'periodicidad_meses' => 12, 'estado' => 'activo', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id]);
        $this->actingAs($admin)->post(route('planificacion.proveedores.evaluaciones.store', $proveedor), [
            'fecha_evaluacion' => '2026-08-03', 'precio_calidad' => 5, 'resolucion_imprevistos' => 5,
            'calidad_producto' => 5, 'calidad_atencion' => 5, 'decision' => 'continuar', 'requiere_accion' => 0,
            'conclusion' => 'Resultado condicional.', 'proxima_evaluacion' => '2027-08-03', 'requiere_analisis_riesgo' => 0,
        ])->assertSessionHasErrors('requiere_accion');
        $this->assertSame(0, ProveedorEvaluacion::count());
    }

    public function test_supplier_has_only_one_initial_selection(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $proveedor = Proveedor::create(['codigo' => 'PR-0001', 'nombre' => 'Proveedor', 'producto_servicio' => 'Servicio', 'area_responsable' => 'Administración', 'criticidad' => 'no_critico', 'periodicidad_meses' => 12, 'estado' => 'activo', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id]);
        $seleccion = ['fecha' => '2026-08-03', 'caracteristicas' => 8, 'recomendaciones' => 7, 'precio_condiciones' => 7];
        $this->actingAs($admin)->post(route('planificacion.proveedores.selecciones.store', $proveedor), $seleccion)->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('planificacion.proveedores.selecciones.store', $proveedor), $seleccion)->assertSessionHasErrors('fecha');
        $this->assertSame(1, $proveedor->selecciones()->count());
    }

    public function test_required_actions_move_cycle_to_reevaluation_and_only_one_cycle_can_be_active(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $proveedor = Proveedor::create(['codigo' => 'PR-0044', 'nombre' => 'Proveedor crítico', 'producto_servicio' => 'Servicio', 'area_responsable' => 'Desarrollo', 'criticidad' => 'critico', 'periodicidad_meses' => 12, 'estado' => 'activo', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id]);
        $base = ['fecha_evaluacion' => '2026-08-03', 'precio_calidad' => 5, 'resolucion_imprevistos' => 4, 'calidad_producto' => 6, 'calidad_atencion' => 3, 'decision' => 'continuar', 'requiere_accion' => 1, 'conclusion' => 'Se continúa condicionalmente.', 'justificacion' => 'Se realizará tratamiento.', 'proxima_evaluacion' => '2027-08-03', 'requiere_analisis_riesgo' => 0, 'accion_descripcion' => 'Corregir el desempeño.', 'accion_area_responsable' => 'Desarrollo', 'accion_responsable_id' => $admin->id, 'accion_fecha_objetivo' => '2026-08-10'];
        $this->actingAs($admin)->post(route('planificacion.proveedores.evaluaciones.store', $proveedor), $base)->assertSessionHasNoErrors();
        $inicial = ProveedorEvaluacion::firstOrFail();
        $this->assertSame('no_aprobado', $inicial->resultado);
        $this->assertSame('en_tratamiento', $inicial->estado_ciclo);

        $aprobada = [...$base, 'precio_calidad' => 7, 'resolucion_imprevistos' => 7, 'calidad_producto' => 7, 'calidad_atencion' => 7, 'requiere_accion' => 0, 'conclusion' => 'Proveedor conforme.'];
        $this->actingAs($admin)->post(route('planificacion.proveedores.evaluaciones.store', $proveedor), $aprobada)->assertSessionHasErrors('fecha_evaluacion');
        $accion = $inicial->acciones()->firstOrFail();
        $this->actingAs($admin)->patch(route('planificacion.proveedores.acciones.update', $accion), ['estado' => 'completada', 'resultado' => 'Tratamiento terminado.'])->assertSessionHasNoErrors();
        $this->assertSame('pendiente_reevaluacion', $inicial->fresh()->estado_ciclo);

        $this->actingAs($admin)->post(route('planificacion.proveedores.evaluaciones.store', $proveedor), $aprobada + ['evaluacion_anterior_id' => $inicial->id])->assertSessionHasNoErrors();
        $reevaluacion = ProveedorEvaluacion::whereKeyNot($inicial->id)->firstOrFail();
        $this->assertSame('reevaluacion', $reevaluacion->tipo);
        $this->assertSame($inicial->id, $reevaluacion->evaluacion_anterior_id);
        $this->assertSame('aprobado', $reevaluacion->resultado);
        $this->assertSame('cerrada', $reevaluacion->estado_ciclo);
        $this->assertSame('cerrada', $inicial->fresh()->estado_ciclo);
        $this->assertSame($reevaluacion->id, $proveedor->fresh()->ultimaEvaluacion->id);
    }

    public function test_empty_supplier_can_be_deleted_but_supplier_with_activity_cannot(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $vacio = Proveedor::create(['codigo' => 'PR-0001', 'nombre' => 'Alta errónea', 'producto_servicio' => 'Sin uso', 'area_responsable' => 'Administración', 'criticidad' => 'no_critico', 'periodicidad_meses' => 12, 'estado' => 'activo', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id]);
        $this->actingAs($admin)->delete(route('planificacion.proveedores.destroy', $vacio), ['motivo' => 'Se creó por error.', 'confirmacion' => 1])->assertRedirect(route('planificacion.proveedores.index'));
        $this->assertDatabaseMissing('iso_proveedores', ['id' => $vacio->id]);
        $this->assertDatabaseHas('iso_historial_cambios', ['entidad_tipo' => Proveedor::class, 'entidad_id' => $vacio->id, 'evento' => 'eliminado']);

        $conActividad = Proveedor::create(['codigo' => 'PR-0002', 'nombre' => 'Proveedor utilizado', 'producto_servicio' => 'Servicio', 'area_responsable' => 'Administración', 'criticidad' => 'no_critico', 'periodicidad_meses' => 12, 'estado' => 'activo', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id]);
        $conActividad->selecciones()->create(['fecha' => '2026-08-03', 'calificaciones' => ['caracteristicas' => 8], 'puntaje' => 8, 'resultado' => 'aprobado', 'conclusion' => 'Seleccionado.', 'evaluado_por' => $admin->id]);
        $this->actingAs($admin)->from(route('planificacion.proveedores.show', $conActividad))->delete(route('planificacion.proveedores.destroy', $conActividad), ['motivo' => 'Intento de eliminación.', 'confirmacion' => 1])->assertSessionHasErrors('confirmacion');
        $this->assertDatabaseHas('iso_proveedores', ['id' => $conActividad->id]);
    }

    public function test_supplier_deactivation_and_reactivation_require_confirmation_and_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $proveedor = Proveedor::create(['codigo' => 'PR-0001', 'nombre' => 'Proveedor', 'producto_servicio' => 'Servicio', 'area_responsable' => 'Administración', 'criticidad' => 'no_critico', 'periodicidad_meses' => 12, 'estado' => 'activo', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id]);
        $this->actingAs($admin)->patch(route('planificacion.proveedores.baja', $proveedor), ['motivo' => 'Ya no presta el servicio.'])->assertSessionHasErrors('confirmacion');
        $this->actingAs($admin)->patch(route('planificacion.proveedores.baja', $proveedor), ['motivo' => 'Ya no presta el servicio.', 'confirmacion' => 1])->assertSessionHasNoErrors();
        $this->assertSame('inactivo', $proveedor->fresh()->estado);
        $this->assertNotNull($proveedor->fresh()->fecha_baja);
        $this->actingAs($admin)->post(route('planificacion.proveedores.selecciones.store', $proveedor), ['fecha' => '2026-08-03', 'caracteristicas' => 8])->assertStatus(422);
        $this->actingAs($admin)->patch(route('planificacion.proveedores.update', $proveedor), ['nombre' => 'Proveedor', 'producto_servicio' => 'Servicio', 'area_responsable' => 'Administración', 'criticidad' => 'no_critico', 'periodicidad_meses' => 12, 'estado' => 'activo'])->assertSessionHasNoErrors();
        $this->assertSame('inactivo', $proveedor->fresh()->estado);
        $this->actingAs($admin)->patch(route('planificacion.proveedores.reactivar', $proveedor), ['motivo' => 'Se retomó la contratación.', 'confirmacion' => 1])->assertSessionHasNoErrors();
        $this->assertSame('activo', $proveedor->fresh()->estado);
        $this->assertNotNull($proveedor->fresh()->fecha_reactivacion);
        $this->assertDatabaseHas('iso_historial_cambios', ['entidad_tipo' => Proveedor::class, 'entidad_id' => $proveedor->id, 'evento' => 'actualizado']);
        $this->actingAs($admin)->get(route('planificacion.proveedores.show', $proveedor))->assertOk()->assertSee('Historial de bajas y reactivaciones')->assertSee('Ya no presta el servicio.')->assertSee('Se retomó la contratación.');
        $this->actingAs($admin)->get(route('planificacion.proveedores.index'))->assertOk()->assertSee('Historial de bajas, reactivaciones y eliminaciones')->assertSee('Ya no presta el servicio.');
    }
}
