<?php

namespace Tests\Feature\Iso;

use App\Models\Iso\Objetivo;
use App\Models\Iso\ObjetivoAccion;
use App\Models\Iso\ObjetivoEvaluacion;
use App\Models\Iso\ObjetivoMedicion;
use App\Models\Iso\Periodo;
use App\Models\Iso\UsuarioPermiso;
use App\Services\Iso\ResultadoObjetivoService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObjetivoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_quality_objective_can_be_created_measured_and_evaluated(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id]);

        $this->actingAs($admin)->get(route('planificacion.objetivos.create', ['periodo' => $periodo->id]))
            ->assertOk()->assertSee('Nuevo objetivo de calidad')->assertSee('Indicador y meta')->assertSee('Relaciones y acción');

        $this->actingAs($admin)->post(route('planificacion.objetivos.store'), $this->datosObjetivo($periodo, $admin))
            ->assertSessionHasNoErrors();

        $objetivo = Objetivo::with('indicadorPrincipal')->firstOrFail();
        $this->assertSame('OBJ-2026-001', $objetivo->codigo);
        $this->assertDatabaseHas('iso_objetivo_indicadores', ['objetivo_id' => $objetivo->id, 'meta' => 75, 'tolerancia' => 5]);
        $this->assertDatabaseHas('iso_objetivo_acciones', ['objetivo_id' => $objetivo->id, 'estado' => 'pendiente']);

        $this->actingAs($admin)->post(route('planificacion.objetivos.mediciones.store', [$objetivo, $objetivo->indicadorPrincipal]), [
            'fecha_medicion' => '2026-06-30', 'periodo_referencia' => 'Primer semestre', 'valor' => 72,
            'observaciones' => 'Resultado parcial de la encuesta.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, ObjetivoMedicion::count());
        $this->actingAs($admin)->get(route('planificacion.objetivos.show', $objetivo))
            ->assertOk()->assertSee('72')->assertSee('Aceptable')->assertSee('Resultado parcial de la encuesta.');

        $evaluacion = [
            'fecha_evaluacion' => '2026-07-01', 'tipo' => 'seguimiento', 'cumplimiento' => 'aceptable',
            'conclusion' => 'El resultado se encuentra dentro de la tolerancia definida.',
            'decision' => 'continuar', 'proxima_evaluacion' => '2026-12-15',
        ];
        $this->actingAs($admin)->post(route('planificacion.objetivos.evaluaciones.store', $objetivo), $evaluacion)
            ->assertSessionHasErrors('justificacion');
        $this->actingAs($admin)->post(route('planificacion.objetivos.evaluaciones.store', $objetivo), $evaluacion + [
            'justificacion' => 'El desvío de tres puntos está dentro de la tolerancia aprobada de cinco puntos.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, ObjetivoEvaluacion::count());
        $this->assertSame('activo', $objetivo->fresh()->estado);
        $this->actingAs($admin)->get(route('planificacion.objetivos.index', ['periodo' => $periodo->id]))
            ->assertOk()->assertSee('Incrementar la satisfacción del cliente')->assertSee('Aceptable')
            ->assertSee('Pendiente de evaluación de cierre');
        $this->actingAs($admin)->get(route('planificacion.objetivos.index', ['periodo' => $periodo->id, 'cumplimiento' => 'aceptable', 'situacion' => 'activo']))
            ->assertOk()->assertSee('Incrementar la satisfacción del cliente')->assertSee('Cumplimiento / situación');
        $this->actingAs($admin)->get(route('planificacion.objetivos.index', ['periodo' => $periodo->id, 'cumplimiento' => 'incumplido']))
            ->assertOk()->assertDontSee('Incrementar la satisfacción del cliente');
        $this->actingAs($admin)->get(route('planificacion.index'))->assertOk()->assertSee('Objetivos de calidad');
        $this->actingAs($admin)->get(route('planificacion.informes.index', ['periodo' => $periodo->id, 'tipo' => 'detallado']))
            ->assertOk()->assertSee('Objetivos de calidad, mediciones y evaluaci&oacute;n de cierre', false)
            ->assertSee('OBJ-2026-001')->assertSee('Porcentaje de respuestas positivas')
            ->assertSee('Primer semestre')->assertSee('Resultado parcial de la encuesta.')
            ->assertSee('Realizar y analizar la encuesta de satisfacción.')
            ->assertSee('El resultado se encuentra dentro de la tolerancia definida.');
    }

    public function test_closed_objective_action_is_locked_and_reopening_is_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id]);
        $this->actingAs($admin)->post(route('planificacion.objetivos.store'), $this->datosObjetivo($periodo, $admin))->assertSessionHasNoErrors();
        $accion = ObjetivoAccion::firstOrFail();

        $this->actingAs($admin)->patch(route('planificacion.objetivos.acciones.update', $accion), [
            'estado' => 'completada', 'resultado' => '',
        ])->assertSessionHasErrors('resultado');
        $this->actingAs($admin)->patch(route('planificacion.objetivos.acciones.update', $accion), [
            'estado' => 'completada', 'resultado' => 'Encuesta realizada y resultados analizados.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('completada', $accion->fresh()->estado);

        $this->actingAs($admin)->patch(route('planificacion.objetivos.acciones.update', $accion), [
            'estado' => 'en_proceso', 'resultado' => 'Cambio directo',
        ])->assertSessionHasErrors('estado');
        $this->actingAs($admin)->patch(route('planificacion.objetivos.acciones.reabrir', $accion), [
            'motivo' => 'Se requiere ampliar la muestra de la encuesta.', 'comprende_impacto' => 1,
            'confirmacion' => 'REABRIR ACCION',
        ])->assertSessionHasNoErrors();

        $this->assertSame('en_proceso', $accion->fresh()->estado);
        $this->assertDatabaseHas('iso_objetivo_accion_transiciones', [
            'accion_id' => $accion->id, 'estado_anterior' => 'completada', 'estado_nuevo' => 'en_proceso',
            'motivo' => 'Se requiere ampliar la muestra de la encuesta.',
        ]);
    }

    public function test_active_objective_with_period_closure_evaluation_allows_closure_and_continuity(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create([
            'anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id,
            'cambio_climatico_relevante' => false, 'fundamento_cambio_climatico' => 'Evaluación completada.',
        ]);
        $this->actingAs($admin)->post(route('planificacion.objetivos.store'), $this->datosObjetivo($periodo, $admin))->assertSessionHasNoErrors();
        $objetivo = Objetivo::with(['indicadorPrincipal', 'acciones'])->firstOrFail();
        $accion = $objetivo->acciones->firstOrFail();
        $this->actingAs($admin)->patch(route('planificacion.objetivos.acciones.update', $accion), [
            'estado' => 'completada', 'resultado' => 'Encuesta realizada.',
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('planificacion.objetivos.mediciones.store', [$objetivo, $objetivo->indicadorPrincipal]), [
            'fecha_medicion' => '2026-12-20', 'periodo_referencia' => 'Cierre anual', 'valor' => 80,
        ])->assertSessionHasNoErrors();
        $cierre = ['motivo' => 'La planificación anual fue revisada completamente.', 'comprende_impacto' => 1, 'confirmacion' => 'CERRAR 2026'];

        $this->actingAs($admin)->post(route('planificacion.objetivos.evaluaciones.store', $objetivo), [
            'fecha_evaluacion' => '2026-12-21', 'tipo' => 'seguimiento', 'cumplimiento' => 'cumplido',
            'conclusion' => 'La meta fue alcanzada.', 'decision' => 'continuar', 'proxima_evaluacion' => '2027-06-30',
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->patch(route('planificacion.periodos.cerrar', $periodo), $cierre)->assertStatus(422);

        $this->actingAs($admin)->post(route('planificacion.objetivos.evaluaciones.store', $objetivo), [
            'fecha_evaluacion' => '2026-12-31', 'tipo' => 'cierre_periodo', 'cumplimiento' => 'cumplido',
            'conclusion' => 'Se cierra el seguimiento 2026 y se mantiene el objetivo para 2027.',
            'decision' => 'continuar', 'proxima_evaluacion' => '2027-06-30',
        ])->assertSessionHasNoErrors();
        $this->assertSame('activo', $objetivo->fresh()->estado);
        $this->actingAs($admin)->get(route('planificacion.objetivos.index', ['periodo' => $periodo->id]))
            ->assertOk()->assertSee('Cierre del período evaluado')->assertSee('31/12/2026');
        $this->actingAs($admin)->patch(route('planificacion.periodos.cerrar', $periodo), $cierre)->assertSessionHasNoErrors();

        $siguiente = Periodo::create(['anio' => 2027, 'nombre' => 'Planificación 2027', 'estado' => 'borrador', 'creado_por' => $admin->id]);
        $this->actingAs($admin)->patch(route('planificacion.periodos.activar', $siguiente), [
            'motivo' => 'Comienza la planificación de objetivos del nuevo ejercicio.',
            'comprende_impacto' => 1, 'confirmacion' => 'ACTIVAR 2027', 'trasladar_objetivos' => 1,
        ])->assertSessionHasNoErrors();

        $continuidad = Objetivo::where('periodo_id', $siguiente->id)->sole();
        $this->assertSame($objetivo->id, $continuidad->objetivo_origen_id);
        $this->assertSame('activo', $continuidad->estado);
        $this->assertSame('2027-01-01', $continuidad->fecha_inicio->toDateString());
        $this->assertSame(0, $continuidad->acciones()->count());
        $this->assertSame(0, $continuidad->evaluaciones()->count());
        $this->assertSame(0, $continuidad->indicadorPrincipal->mediciones()->count());
    }

    public function test_closed_period_rejects_new_quality_objectives(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'cerrado', 'creado_por' => $admin->id]);
        $this->actingAs($admin)->post(route('planificacion.objetivos.store'), $this->datosObjetivo($periodo, $admin))->assertStatus(422);
        $this->assertSame(0, Objetivo::count());
    }

    public function test_percentage_variation_remains_pending_until_two_measurements_exist(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id]);
        $datos = $this->datosObjetivo($periodo, $admin);
        $datos['indicador_agregacion'] = 'variacion_porcentual';
        $datos['indicador_meta'] = 20;

        $this->actingAs($admin)->post(route('planificacion.objetivos.store'), $datos)->assertSessionHasNoErrors();
        $objetivo = Objetivo::with('indicadorPrincipal')->firstOrFail();
        $indicador = $objetivo->indicadorPrincipal;

        ObjetivoMedicion::create([
            'indicador_id' => $indicador->id,
            'fecha_medicion' => '2026-06-30',
            'periodo_referencia' => 'Base',
            'valor' => 100,
            'creado_por' => $admin->id,
        ]);

        $servicio = app(ResultadoObjetivoService::class);
        $this->assertNull($servicio->resultado($indicador));
        $this->assertSame('pendiente', $servicio->cumplimiento($indicador));

        ObjetivoMedicion::create([
            'indicador_id' => $indicador->id,
            'fecha_medicion' => '2026-12-31',
            'periodo_referencia' => 'Cierre',
            'valor' => 121,
            'creado_por' => $admin->id,
        ]);

        $this->assertSame(21.0, $servicio->resultado($indicador));
        $this->assertSame('cumplido', $servicio->cumplimiento($indicador));
    }

    public function test_recent_objective_can_be_corrected_and_empty_objective_can_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id]);
        $datos = $this->datosObjetivo($periodo, $admin);
        unset($datos['accion_descripcion'], $datos['accion_area_responsable'], $datos['accion_responsable_id'], $datos['accion_fecha_objetivo']);
        $this->actingAs($admin)->post(route('planificacion.objetivos.store'), $datos)->assertSessionHasNoErrors();
        $objetivo = Objetivo::firstOrFail();

        $corregidos = array_merge($datos, [
            'indicador_meta' => 80,
            'motivo_cambio' => 'La meta correcta era 80 y se ingresó 75 por error.',
            'confirmacion_cambio' => 1,
        ]);
        $this->actingAs($admin)->patch(route('planificacion.objetivos.update', $objetivo), $corregidos)->assertSessionHasNoErrors();
        $this->assertSame('80.0000', $objetivo->indicadorPrincipal()->firstOrFail()->meta);
        $this->assertDatabaseHas('iso_objetivo_revisiones', ['objetivo_id' => $objetivo->id, 'tipo' => 'correccion']);

        $this->actingAs($admin)->delete(route('planificacion.objetivos.destroy', $objetivo), [
            'motivo_eliminacion' => 'Ficha duplicada creada durante una prueba.',
            'comprende_eliminacion' => 1,
            'confirmacion_eliminacion' => 'ELIMINAR OBJETIVO',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('iso_objetivos', ['id' => $objetivo->id]);
        $this->assertDatabaseHas('iso_historial_cambios', ['entidad_id' => $objetivo->id, 'evento' => 'eliminado_por_error']);
    }

    public function test_objective_with_measurements_requires_controlled_revision_and_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id]);
        $datos = $this->datosObjetivo($periodo, $admin);
        unset($datos['accion_descripcion'], $datos['accion_area_responsable'], $datos['accion_responsable_id'], $datos['accion_fecha_objetivo']);
        $this->actingAs($admin)->post(route('planificacion.objetivos.store'), $datos)->assertSessionHasNoErrors();
        $objetivo = Objetivo::with('indicadorPrincipal')->firstOrFail();
        ObjetivoMedicion::create([
            'indicador_id' => $objetivo->indicadorPrincipal->id, 'fecha_medicion' => '2026-06-30',
            'periodo_referencia' => 'Primer semestre', 'valor' => 72, 'registrado_por' => $admin->id,
        ]);

        $cambio = array_merge($datos, ['indicador_meta' => 80, 'motivo_cambio' => 'Revisión aprobada de la meta anual.', 'confirmacion_cambio' => 1]);
        $this->actingAs($admin)->patch(route('planificacion.objetivos.update', $objetivo), $cambio)->assertSessionHasErrors('motivo_cambio');
        $this->actingAs($admin)->patch(route('planificacion.objetivos.revisar', $objetivo), $cambio + ['fecha_vigencia' => '2026-08-05'])->assertSessionHasNoErrors();

        $this->assertSame('80.0000', $objetivo->indicadorPrincipal()->firstOrFail()->meta);
        $this->assertDatabaseHas('iso_objetivo_revisiones', [
            'objetivo_id' => $objetivo->id, 'tipo' => 'revision',
            'motivo' => 'Revisión aprobada de la meta anual.',
        ]);
        $this->actingAs($admin)->delete(route('planificacion.objetivos.destroy', $objetivo), [
            'motivo_eliminacion' => 'Intento inválido', 'comprende_eliminacion' => 1,
            'confirmacion_eliminacion' => 'ELIMINAR OBJETIVO',
        ])->assertSessionHasErrors('eliminar_objetivo');
        $this->assertDatabaseHas('iso_objetivos', ['id' => $objetivo->id]);
    }

    public function test_consultation_user_can_view_objectives_but_cannot_manage_them(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $consulta = User::factory()->create(['role' => 'user', 'habilitado' => true]);
        UsuarioPermiso::create([
            'user_id' => $consulta->id,
            'puede_ver' => true,
            'puede_gestionar' => false,
            'puede_administrar' => false,
        ]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id]);
        $this->actingAs($admin)->post(route('planificacion.objetivos.store'), $this->datosObjetivo($periodo, $admin))->assertSessionHasNoErrors();
        $objetivo = Objetivo::with('indicadorPrincipal', 'acciones')->firstOrFail();
        $indicador = $objetivo->indicadorPrincipal;
        $accion = $objetivo->acciones->firstOrFail();
        ObjetivoMedicion::create([
            'indicador_id' => $indicador->id,
            'fecha_medicion' => '2026-06-30',
            'periodo_referencia' => 'Primer semestre',
            'valor' => 72,
            'registrado_por' => $admin->id,
        ]);

        $this->actingAs($consulta)->get(route('planificacion.objetivos.index', ['periodo' => $periodo->id]))
            ->assertOk()
            ->assertSee($objetivo->titulo)
            ->assertDontSee('Nuevo objetivo');
        $this->actingAs($consulta)->get(route('planificacion.objetivos.show', $objetivo))
            ->assertOk()
            ->assertSee('Historial de mediciones')
            ->assertDontSee('Registrar medición')
            ->assertDontSee('Agregar acción')
            ->assertDontSee('Evaluar objetivo')
            ->assertDontSee('id="gestionarObjetivo"', false)
            ->assertDontSee('Editar definición e indicador')
            ->assertDontSee('Eliminar objetivo creado por error');

        $this->actingAs($consulta)->get(route('planificacion.objetivos.create', ['periodo' => $periodo->id]))->assertForbidden();
        $this->actingAs($consulta)->post(route('planificacion.objetivos.store'), [])->assertForbidden();
        $this->actingAs($consulta)->patch(route('planificacion.objetivos.update', $objetivo), [])->assertForbidden();
        $this->actingAs($consulta)->patch(route('planificacion.objetivos.revisar', $objetivo), [])->assertForbidden();
        $this->actingAs($consulta)->delete(route('planificacion.objetivos.destroy', $objetivo), [])->assertForbidden();
        $this->actingAs($consulta)->post(route('planificacion.objetivos.mediciones.store', [$objetivo, $indicador]), [])->assertForbidden();
        $this->actingAs($consulta)->post(route('planificacion.objetivos.acciones.store', $objetivo), [])->assertForbidden();
        $this->actingAs($consulta)->patch(route('planificacion.objetivos.acciones.update', $accion), [])->assertForbidden();
        $this->actingAs($consulta)->patch(route('planificacion.objetivos.acciones.reabrir', $accion), [])->assertForbidden();
        $this->actingAs($consulta)->post(route('planificacion.objetivos.acciones.seguimientos.store', $accion), [])->assertForbidden();
        $this->actingAs($consulta)->post(route('planificacion.objetivos.evaluaciones.store', $objetivo), [])->assertForbidden();

        $this->assertSame(1, ObjetivoMedicion::count());
        $this->assertSame(0, ObjetivoEvaluacion::count());
    }

    public function test_management_user_keeps_objective_controls_and_can_record_measurements(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $gestor = User::factory()->create(['role' => 'user', 'habilitado' => true]);
        UsuarioPermiso::create([
            'user_id' => $gestor->id,
            'puede_ver' => false,
            'puede_gestionar' => true,
            'puede_administrar' => false,
        ]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id]);
        $this->actingAs($admin)->post(route('planificacion.objetivos.store'), $this->datosObjetivo($periodo, $admin))->assertSessionHasNoErrors();
        $objetivo = Objetivo::with('indicadorPrincipal')->firstOrFail();

        $this->actingAs($gestor)->get(route('planificacion.objetivos.create', ['periodo' => $periodo->id]))
            ->assertOk()
            ->assertSee('Nuevo objetivo de calidad');
        $this->actingAs($gestor)->get(route('planificacion.objetivos.show', $objetivo))
            ->assertOk()
            ->assertSee('Registrar medición')
            ->assertSee('Agregar acción');
        $this->actingAs($gestor)->post(route('planificacion.objetivos.mediciones.store', [$objetivo, $objetivo->indicadorPrincipal]), [
            'fecha_medicion' => '2026-06-30',
            'periodo_referencia' => 'Primer semestre',
            'valor' => 78,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('iso_objetivo_mediciones', [
            'indicador_id' => $objetivo->indicadorPrincipal->id,
            'valor' => 78,
            'registrado_por' => $gestor->id,
        ]);
    }

    private function datosObjetivo(Periodo $periodo, User $admin): array
    {
        return [
            'periodo_id' => $periodo->id, 'compromiso_politica' => 'Satisfacción del cliente',
            'proceso' => 'Gestión comercial', 'titulo' => 'Incrementar la satisfacción del cliente',
            'descripcion' => 'Mejorar la percepción de los clientes sobre los servicios.',
            'area_responsable' => 'Comercial', 'responsable_id' => $admin->id,
            'fecha_inicio' => '2026-01-01', 'fecha_objetivo' => '2026-12-31',
            'periodicidad_seguimiento' => 'Semestral',
            'indicador_nombre' => 'Porcentaje de respuestas positivas',
            'indicador_metodo_calculo' => 'Respuestas positivas dividido total de respuestas por cien.',
            'indicador_unidad' => 'Porcentaje', 'indicador_fuente' => 'Encuesta de satisfacción',
            'indicador_frecuencia' => 'Semestral', 'indicador_agregacion' => 'ultimo',
            'indicador_comparador' => 'mayor_igual', 'indicador_meta' => 75, 'indicador_tolerancia' => 5,
            'accion_descripcion' => 'Realizar y analizar la encuesta de satisfacción.',
            'accion_area_responsable' => 'Comercial', 'accion_responsable_id' => $admin->id,
            'accion_fecha_objetivo' => '2026-06-30',
        ];
    }
}
