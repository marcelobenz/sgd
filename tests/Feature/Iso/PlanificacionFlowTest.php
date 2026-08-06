<?php

namespace Tests\Feature\Iso;

use App\Models\Iso\Contexto;
use App\Models\Iso\Periodo;
use App\Models\Iso\Riesgo;
use App\Models\Iso\PeriodoTransicion;
use App\Models\Iso\ParteInteresada;
use App\Models\Iso\ParteInteresadaEvaluacion;
use App\Models\Iso\RiesgoVerificacion;
use App\Models\Iso\HistorialCambio;
use App\Models\Documento;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanificacionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_complete_foda_to_risk_flow(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);

        $this->actingAs($admin)->post(route('planificacion.periodos.store'), [
            'anio' => 2026,
            'nombre' => 'Planificación 2026',
        ])->assertSessionHasNoErrors();

        $periodo = Periodo::firstOrFail();
        $this->actingAs($admin)->patch(route('planificacion.periodos.activar', $periodo), [
            'motivo' => 'Comienza la planificación operativa del ejercicio.',
            'comprende_impacto' => 1,
            'confirmacion' => 'ACTIVAR 2026',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('planificacion.foda.store'), [
            'periodo_id' => $periodo->id,
            'tipo' => 'amenaza',
            'titulo' => 'Dependencia de proveedor cloud',
            'descripcion' => 'El proveedor puede discontinuar un servicio crítico.',
            'proceso' => 'Operaciones',
            'fecha_identificacion' => '2026-07-31',
            'responsable_id' => $admin->id,
        ])->assertSessionHasNoErrors();

        $contexto = Contexto::firstOrFail();
        $this->assertSame('A-2026-001', $contexto->codigo);

        $this->actingAs($admin)->patch(route('planificacion.foda.evaluar', $contexto), [
            'relevante_sgc' => 1,
            'decision' => 'tratar_riesgo',
            'justificacion' => 'Puede afectar la continuidad del servicio.',
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('planificacion.riesgos.store'), [
            'periodo_id' => $periodo->id,
            'contexto_id' => $contexto->id,
            'tipo' => 'riesgo',
            'proceso' => 'Operaciones',
            'identificacion' => 'Interrupción del servicio cloud.',
            'efecto_potencial' => 'Indisponibilidad para los clientes.',
            'criterio_eficacia' => 'Recuperar el servicio en menos de dos horas.',
            'impacto_inicial' => 3,
            'probabilidad_inicial' => 2,
            'responsable_id' => $admin->id,
            'fecha_verificacion_prevista' => '2026-12-01',
            'accion_descripcion' => 'Evaluar y probar un proveedor alternativo.',
            'accion_responsable_id' => $admin->id,
            'accion_fecha_objetivo' => '2026-10-31',
        ])->assertSessionHasNoErrors();

        $riesgo = Riesgo::with('acciones')->firstOrFail();
        $this->assertSame('RO-2026-001', $riesgo->codigo);
        $this->assertSame(6, $riesgo->indice_inicial);
        $this->assertCount(1, $riesgo->acciones);

        $accion = $riesgo->acciones->first();
        $this->actingAs($admin)->post(route('planificacion.acciones.seguimientos.store', $accion), [
            'fecha' => '2026-10-15',
            'detalle' => 'Prueba de recuperación ejecutada.',
            'resultado' => 'Servicio recuperado en 45 minutos.',
            'enlace_externo' => 'https://example.test/evidencia/1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('iso_seguimientos', ['accion_id' => $accion->id, 'resultado' => 'Servicio recuperado en 45 minutos.']);
        $this->assertDatabaseHas('iso_historial_cambios', ['entidad_tipo' => Contexto::class, 'entidad_id' => $contexto->id]);
    }

    public function test_user_without_iso_permission_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'user', 'habilitado' => true]);
        $this->actingAs($user)->get(route('planificacion.index'))->assertForbidden();
    }

    public function test_guided_fields_accept_catalog_and_custom_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create([
            'anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('planificacion.riesgos.create'))
            ->assertOk()
            ->assertSee('riesgoWizard')
            ->assertSee('Identificación')
            ->assertSee('Evaluación')
            ->assertSee('Tratamiento opcional')
            ->assertSee('Desarrollo de productos y servicios')
            ->assertSee('Partes afectadas')
            ->assertSee('Escribí el nombre del proceso');

        $this->actingAs($admin)->post(route('planificacion.riesgos.store'), [
            'periodo_id' => $periodo->id,
            'tipo' => 'oportunidad',
            'proceso' => '__otro__',
            'proceso_otro' => 'Innovación aplicada',
            'identificacion' => 'Aplicación de IA agéntica.',
            'partes_interesadas_seleccion' => ['Clientes', '__otro__'],
            'partes_interesadas_otro' => 'Aliados comerciales',
            'efecto_potencial' => 'Nuevos productos y servicios.',
            'criterio_eficacia' => 'Validar al menos una aplicación comercial.',
            'impacto_inicial' => 3,
            'probabilidad_inicial' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('iso_riesgos', [
            'proceso' => 'Innovación aplicada',
            'partes_interesadas' => 'Clientes; Aliados comerciales',
        ]);
    }

    public function test_period_close_and_reopen_require_explicit_confirmation_and_are_audited(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create([
            'anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente',
            'cambio_climatico_relevante' => false, 'fundamento_cambio_climatico' => 'Evaluado sin efectos relevantes.',
            'creado_por' => $admin->id,
        ]);

        $this->actingAs($admin)->patch(route('planificacion.periodos.cerrar', $periodo), [
            'motivo' => 'La revisión anual fue completada.',
            'comprende_impacto' => 1,
            'confirmacion' => 'texto incorrecto',
        ])->assertSessionHasErrors('confirmacion');
        $this->assertSame('vigente', $periodo->fresh()->estado);

        $this->actingAs($admin)->patch(route('planificacion.periodos.cerrar', $periodo), [
            'motivo' => 'La revisión anual fue completada.',
            'comprende_impacto' => 1,
            'confirmacion' => 'CERRAR 2026',
        ])->assertSessionHasNoErrors();
        $this->assertSame('cerrado', $periodo->fresh()->estado);
        $this->actingAs($admin)->get(route('planificacion.periodos.index'))
            ->assertOk()->assertSee('Reabrir')->assertSee('La revisión anual fue completada.');

        $this->actingAs($admin)->patch(route('planificacion.periodos.reabrir', $periodo), [
            'motivo' => 'Se requiere incorporar una nueva evaluación.',
            'comprende_impacto' => 1,
            'confirmacion' => 'REABRIR 2026',
        ])->assertSessionHasNoErrors();

        $this->assertSame('borrador', $periodo->fresh()->estado);
        $this->assertSame(['cierre', 'reapertura'], PeriodoTransicion::orderBy('id')->pluck('accion')->all());

        $siguiente = Periodo::create(['anio' => 2027, 'nombre' => 'Planificación 2027', 'estado' => 'vigente', 'creado_por' => $admin->id]);
        $this->actingAs($admin)->patch(route('planificacion.periodos.activar', $periodo), [
            'motivo' => 'El ejercicio debe volver a ser el período operativo principal.',
            'comprende_impacto' => 1,
            'confirmacion' => 'ACTIVAR 2026',
        ])->assertSessionHasNoErrors();
        $this->assertSame('vigente', $periodo->fresh()->estado);
        $this->assertSame('borrador', $siguiente->fresh()->estado);
        $this->assertSame(['cierre', 'reapertura', 'activacion'], PeriodoTransicion::orderBy('id')->pluck('accion')->all());

        $this->actingAs($admin)->patch(route('planificacion.periodos.cerrar', $periodo), [
            'motivo' => 'La corrección excepcional fue completada y revisada.',
            'comprende_impacto' => 1,
            'confirmacion' => 'CERRAR 2026',
        ])->assertSessionHasNoErrors();
        $this->assertSame('cerrado', $periodo->fresh()->estado);
        $this->assertSame(['cierre', 'reapertura', 'activacion', 'cierre'], PeriodoTransicion::orderBy('id')->pluck('accion')->all());
    }

    public function test_period_cannot_close_with_open_risks_or_actions_and_closed_period_rejects_follow_up(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create([
            'anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente',
            'cambio_climatico_relevante' => false, 'fundamento_cambio_climatico' => 'Evaluación completada.',
            'creado_por' => $admin->id,
        ]);
        $riesgo = Riesgo::create([
            'periodo_id' => $periodo->id, 'numero' => 1, 'codigo' => 'RO-2026-001', 'tipo' => 'riesgo',
            'proceso' => 'Operaciones', 'identificacion' => 'Riesgo abierto.', 'efecto_potencial' => 'Interrupción.',
            'impacto_inicial' => 2, 'probabilidad_inicial' => 2, 'indice_inicial' => 4,
            'estado' => 'en_proceso', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id,
        ]);
        $cierre = ['motivo' => 'La planificación anual fue revisada completamente.', 'comprende_impacto' => 1, 'confirmacion' => 'CERRAR 2026'];
        $this->actingAs($admin)->patch(route('planificacion.periodos.cerrar', $periodo), $cierre)->assertStatus(422);

        $riesgo->update(['estado' => 'finalizado', 'eficacia' => 'si']);
        $accion = $riesgo->acciones()->create([
            'descripcion' => 'Acción pendiente.', 'responsable_id' => $admin->id, 'fecha_objetivo' => '2026-12-01',
            'estado' => 'pendiente', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id,
        ]);
        $this->actingAs($admin)->patch(route('planificacion.periodos.cerrar', $periodo), $cierre)->assertStatus(422);

        $accion->update(['estado' => 'completada', 'resultado' => 'Completada.', 'completada_en' => now()]);
        $this->actingAs($admin)->patch(route('planificacion.periodos.cerrar', $periodo), $cierre)->assertSessionHasNoErrors();
        $this->assertSame('cerrado', $periodo->fresh()->estado);

        $this->actingAs($admin)->post(route('planificacion.acciones.store', $riesgo), [])->assertStatus(422);
        $this->actingAs($admin)->post(route('planificacion.acciones.seguimientos.store', $accion), [])->assertStatus(422);
        $this->actingAs($admin)->patch(route('planificacion.acciones.reabrir', $accion), [])->assertStatus(422);
        $this->actingAs($admin)->patch(route('planificacion.riesgos.fecha-verificacion.update', $riesgo), [])->assertStatus(422);
        $this->actingAs($admin)->patch(route('planificacion.riesgos.verificar', $riesgo), [])->assertStatus(422);
    }

    public function test_controlled_permanent_risk_allows_closure_and_can_continue_in_next_period(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create([
            'anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente',
            'cambio_climatico_relevante' => false, 'fundamento_cambio_climatico' => 'Evaluación completada.',
            'creado_por' => $admin->id,
        ]);
        $riesgo = Riesgo::create([
            'periodo_id' => $periodo->id, 'numero' => 1, 'codigo' => 'RO-2026-001', 'tipo' => 'riesgo',
            'proceso' => 'Seguridad', 'identificacion' => 'Riesgo de seguimiento permanente.',
            'efecto_potencial' => 'Afectación continua.', 'criterio_eficacia' => 'Mantener controles operativos.',
            'impacto_inicial' => 2, 'probabilidad_inicial' => 2, 'indice_inicial' => 4,
            'estado' => 'permanente', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id,
        ]);
        $cierre = ['motivo' => 'La planificación anual fue revisada completamente.', 'comprende_impacto' => 1, 'confirmacion' => 'CERRAR 2026'];

        $this->actingAs($admin)->patch(route('planificacion.periodos.cerrar', $periodo), $cierre)->assertStatus(422);

        $riesgo->update(['fecha_verificacion_prevista' => today()->addMonths(6), 'eficacia' => 'si']);
        RiesgoVerificacion::create([
            'riesgo_id' => $riesgo->id, 'tipo' => 'intermedia', 'fecha' => today(), 'eficacia' => 'si',
            'conclusion' => 'Los controles permanecen eficaces.', 'impacto' => 1, 'probabilidad' => 1, 'indice' => 1,
            'estado_resultante' => 'permanente', 'verificado_por' => $admin->id,
        ]);
        $riesgo->acciones()->create([
            'descripcion' => 'Control anual completado.', 'responsable_id' => $admin->id,
            'fecha_objetivo' => today(), 'estado' => 'completada', 'resultado' => 'Control realizado.',
            'creado_por' => $admin->id, 'actualizado_por' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('planificacion.periodos.index'))
            ->assertOk()->assertDontSee('1 permanentes sin evaluación vigente o próxima revisión');
        $this->actingAs($admin)->patch(route('planificacion.periodos.cerrar', $periodo), $cierre)->assertSessionHasNoErrors();

        $siguiente = Periodo::create(['anio' => 2027, 'nombre' => 'Planificación 2027', 'estado' => 'borrador', 'creado_por' => $admin->id]);
        $this->actingAs($admin)->patch(route('planificacion.periodos.activar', $siguiente), [
            'motivo' => 'Comienza la planificación operativa del nuevo ejercicio.',
            'comprende_impacto' => 1, 'confirmacion' => 'ACTIVAR 2027', 'trasladar_permanentes' => 1,
        ])->assertSessionHasNoErrors();

        $continuidad = Riesgo::where('periodo_id', $siguiente->id)->sole();
        $this->assertSame($riesgo->id, $continuidad->riesgo_origen_id);
        $this->assertSame('permanente', $continuidad->estado);
        $this->assertSame(0, $continuidad->acciones()->count());
        $this->actingAs($admin)->get(route('planificacion.riesgos.show', $continuidad))
            ->assertOk()->assertSee('Continuidad del período anterior')->assertSee('RO-2026-001');
    }

    public function test_interested_party_is_permanent_and_keeps_periodic_evaluations(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id]);

        $this->actingAs($admin)->post(route('planificacion.partes.store'), [
            'nombre' => 'Clientes', 'pertinente_sgc' => 1,
            'necesidades_requisitos' => 'Atención personalizada y alta disponibilidad.',
            'area_responsable' => 'Comercial', 'metodo_medicion' => 'Encuestas de satisfacción y reuniones.',
            'requisitos_climaticos' => 0,
            'detalle_climatico' => 'No se identificaron requisitos aplicables.',
        ])->assertSessionHasNoErrors();

        $parte = ParteInteresada::firstOrFail();
        $riesgo = Riesgo::create([
            'periodo_id' => $periodo->id, 'numero' => 1, 'codigo' => 'RO-2026-001', 'tipo' => 'oportunidad',
            'proceso' => 'Gestión comercial', 'identificacion' => 'Mejorar experiencia del cliente.',
            'efecto_potencial' => 'Mayor satisfacción.', 'impacto_inicial' => 2, 'probabilidad_inicial' => 2,
            'indice_inicial' => 4, 'estado' => 'pendiente', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id,
        ]);
        $categoria = Categoria::create(['nombre_categoria' => 'Calidad']);
        $documento = Documento::forceCreate(['titulo' => 'Encuesta de satisfacción 2026', 'path' => 'pruebas/encuesta-2026.pdf', 'estado' => 'aprobado', 'id_categoria' => $categoria->id, 'id_usr_creador' => $admin->id, 'id_usr_ultima_modif' => $admin->id, 'version' => 1]);

        $this->actingAs($admin)->post(route('planificacion.partes.evaluaciones.store', $parte), [
            'periodo_id' => $periodo->id, 'fecha_evaluacion' => '2026-06-30', 'resultado' => 'cumplido',
            'observaciones' => 'Encuestas con resultado satisfactorio.', 'proxima_revision' => '2027-06-30',
            'riesgos' => [$riesgo->id], 'documento_id' => $documento->id,
        ])->assertSessionHasNoErrors();

        $evaluacion = ParteInteresadaEvaluacion::firstOrFail();
        $this->assertSame($periodo->id, $evaluacion->periodo_id);
        $this->assertTrue($evaluacion->riesgos->contains($riesgo));
        $this->actingAs($admin)->get(route('planificacion.partes.show', $parte))->assertOk()->assertSee('Historial de evaluaciones')->assertSee('Encuestas con resultado satisfactorio.');
        $this->actingAs($admin)->get(route('planificacion.informes.index', ['periodo' => $periodo->id, 'tipo' => 'detallado']))->assertOk()->assertSee('Partes interesadas y evaluaci&oacute;n peri&oacute;dica', false)->assertSee('Clientes')->assertSee('Doc. SGD: Encuesta de satisfacción 2026')->assertSee(route('documentos.validaPermiso',['id'=>$documento->id,'ruta'=>'documentos.show','permiso'=>'puedeLeer']));
    }

    public function test_efficacy_evaluation_separates_continuation_from_closure(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id]);
        $riesgo = Riesgo::create([
            'periodo_id' => $periodo->id, 'numero' => 1, 'codigo' => 'RO-2026-001', 'tipo' => 'riesgo',
            'proceso' => 'Infraestructura', 'identificacion' => 'Interrupción de servicios.', 'efecto_potencial' => 'Indisponibilidad.',
            'criterio_eficacia' => 'Recuperar el servicio en menos de dos horas.', 'impacto_inicial' => 3, 'probabilidad_inicial' => 2,
            'indice_inicial' => 6, 'estado' => 'en_proceso', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id,
        ]);
        $accion1 = $riesgo->acciones()->create(['descripcion' => 'Probar recuperación.', 'responsable_id' => $admin->id, 'fecha_objetivo' => '2026-09-01', 'estado' => 'pendiente', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id]);
        $accion2 = $riesgo->acciones()->create(['descripcion' => 'Contratar alternativa.', 'responsable_id' => $admin->id, 'fecha_objetivo' => '2026-09-15', 'estado' => 'en_proceso', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id]);

        $this->actingAs($admin)->patch(route('planificacion.riesgos.verificar', $riesgo), [
            'decision' => 'continuar', 'fecha' => '2026-08-15', 'eficacia' => 'parcial',
            'conclusion' => 'La primera prueba fue satisfactoria, resta completar el tratamiento.',
            'criterio_eficacia' => $riesgo->criterio_eficacia, 'impacto_final' => 2, 'probabilidad_final' => 2,
            'proxima_evaluacion' => '2026-09-30',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('iso_riesgo_verificaciones', ['riesgo_id' => $riesgo->id, 'tipo' => 'intermedia']);
        $this->assertSame('en_proceso', $riesgo->fresh()->estado);
        $this->assertSame('parcial', $riesgo->fresh()->eficacia);
        $this->assertSame('2026-09-30', $riesgo->fresh()->fecha_verificacion_prevista->toDateString());
        $this->actingAs($admin)->patch(route('planificacion.riesgos.verificar', $riesgo), [
            'decision' => 'continuar', 'fecha' => '2026-09-01', 'eficacia' => 'si',
            'conclusion' => 'El tratamiento fue eficaz, pero se pretende continuar observándolo.',
            'criterio_eficacia' => $riesgo->criterio_eficacia, 'impacto_final' => 2, 'probabilidad_final' => 1,
            'proxima_evaluacion' => '2026-10-30',
        ])->assertSessionHasErrors('justificacion_excepcion');
        $this->actingAs($admin)->patch(route('planificacion.riesgos.verificar', $riesgo), [
            'decision' => 'finalizar', 'fecha' => '2026-09-01', 'eficacia' => 'parcial',
            'conclusion' => 'La mejora todavía es incompleta.', 'criterio_eficacia' => $riesgo->criterio_eficacia,
            'impacto_final' => 2, 'probabilidad_final' => 2,
        ])->assertSessionHasErrors('justificacion_excepcion');

        $final = [
            'decision' => 'finalizar', 'fecha' => '2026-09-30', 'eficacia' => 'si', 'conclusion' => 'Resultado alcanzado.',
            'criterio_eficacia' => $riesgo->criterio_eficacia, 'impacto_final' => 3, 'probabilidad_final' => 1,
        ];
        $this->actingAs($admin)->patch(route('planificacion.riesgos.verificar', $riesgo), [
            ...$final, 'impacto_final' => 3, 'probabilidad_final' => 2,
        ])->assertSessionHasErrors('justificacion_excepcion');
        $this->actingAs($admin)->patch(route('planificacion.riesgos.verificar', $riesgo), $final)->assertSessionHasNoErrors();
        $this->assertSame('finalizado', $riesgo->fresh()->estado);
        $this->assertSame(3, $riesgo->fresh()->indice_final);
        $this->assertNull($riesgo->fresh()->fecha_verificacion_prevista);
        $this->assertSame(2, RiesgoVerificacion::count());
        $this->assertSame('pendiente', $accion1->fresh()->estado);
        $this->assertSame('en_proceso', $accion2->fresh()->estado);
        $riesgo->verificaciones()->where('estado_resultante', 'finalizado')->update([
            'justificacion_excepcion' => 'La evidencia operativa demuestra el resultado aunque la valoración requiera contexto.',
        ]);
        $this->actingAs($admin)->get(route('planificacion.riesgos.show', $riesgo))
            ->assertOk()->assertSee('Evaluación de cierre')->assertSee('30/09/2026')->assertSee('Eficaz')
            ->assertSee('Justificación de la decisión')
            ->assertSee('La evidencia operativa demuestra el resultado aunque la valoración requiera contexto.')
            ->assertDontSee('Evaluación de cierre programada');

        $nuevaAccion = ['descripcion' => 'Monitorear el nuevo esquema.', 'responsable_id' => $admin->id, 'fecha_objetivo' => '2026-11-01'];
        $this->actingAs($admin)->post(route('planificacion.acciones.store', $riesgo), $nuevaAccion)->assertSessionHasErrors('motivo_reapertura');
        $this->assertSame('finalizado', $riesgo->fresh()->estado);
        $this->actingAs($admin)->post(route('planificacion.acciones.store', $riesgo), $nuevaAccion + [
            'motivo_reapertura' => 'Se requiere monitorear la sostenibilidad del resultado.',
            'comprende_impacto' => 1, 'confirmacion_reapertura' => "REABRIR {$riesgo->codigo}",
        ])->assertSessionHasNoErrors();
        $this->assertSame('pendiente', $riesgo->fresh()->eficacia);
        $this->assertSame('en_proceso', $riesgo->fresh()->estado);
        $this->assertSame(2, RiesgoVerificacion::count());
        $this->assertDatabaseHas('iso_riesgo_transiciones', ['riesgo_id' => $riesgo->id, 'accion' => 'reapertura_por_accion', 'motivo' => 'Se requiere monitorear la sostenibilidad del resultado.']);

        $this->actingAs($admin)->get(route('planificacion.acciones.index'))->assertOk()->assertSee('Acciones y seguimiento')->assertSee('Monitorear el nuevo esquema.');
        $this->actingAs($admin)->get(route('planificacion.index'))->assertOk()->assertSee(route('planificacion.acciones.index'));
    }

    public function test_planned_efficacy_date_is_visible_audited_and_tables_are_sortable(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id]);
        $riesgoA = Riesgo::create([
            'periodo_id' => $periodo->id, 'numero' => 1, 'codigo' => 'RO-2026-001', 'tipo' => 'riesgo',
            'proceso' => 'Operaciones', 'identificacion' => 'Alfa interrupción', 'efecto_potencial' => 'Demora',
            'criterio_eficacia' => 'Reducir interrupciones', 'impacto_inicial' => 2, 'probabilidad_inicial' => 2,
            'indice_inicial' => 4, 'fecha_verificacion_prevista' => '2026-08-01', 'eficacia' => 'parcial', 'estado' => 'en_proceso',
            'creado_por' => $admin->id, 'actualizado_por' => $admin->id,
        ]);
        $riesgoB = Riesgo::create([
            'periodo_id' => $periodo->id, 'numero' => 2, 'codigo' => 'RO-2026-002', 'tipo' => 'oportunidad',
            'proceso' => 'Comercial', 'identificacion' => 'Zeta expansión', 'efecto_potencial' => 'Crecimiento',
            'criterio_eficacia' => 'Aumentar ventas', 'impacto_inicial' => 1, 'probabilidad_inicial' => 2,
            'indice_inicial' => 2, 'fecha_verificacion_prevista' => '2026-12-15', 'estado' => 'en_proceso',
            'creado_por' => $admin->id, 'actualizado_por' => $admin->id,
        ]);
        $riesgoA->acciones()->create(['descripcion' => 'Acción Alfa', 'responsable_id' => $admin->id, 'fecha_objetivo' => '2026-09-01', 'estado' => 'pendiente', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id]);
        $riesgoB->acciones()->create(['descripcion' => 'Acción Zeta', 'responsable_id' => $admin->id, 'fecha_objetivo' => '2026-10-01', 'estado' => 'pendiente', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id]);

        $this->actingAs($admin)->patch(route('planificacion.riesgos.fecha-verificacion.update', $riesgoA), [
            'fecha_verificacion_prevista' => '2026-11-20',
        ])->assertSessionHasNoErrors();
        $this->assertSame('2026-11-20', $riesgoA->fresh()->fecha_verificacion_prevista->toDateString());
        $cambio = HistorialCambio::where('entidad_tipo', Riesgo::class)->where('entidad_id', $riesgoA->id)->latest('id')->firstOrFail();
        $this->assertSame('2026-11-20 00:00:00', $cambio->valores_nuevos['fecha_verificacion_prevista']);

        $this->actingAs($admin)->get(route('planificacion.riesgos.show', $riesgoA))->assertOk()->assertSee('20/11/2026')
            ->assertSee('id="acciones-tab"', false)->assertSee('id="historial-tab"', false)
            ->assertSee('Programación de la próxima evaluación')->assertSee('Evaluar eficacia')
            ->assertSee('id="formularioEvaluacionEficacia"', false)->assertSee('iso-action-card');
        $this->actingAs($admin)->get(route('planificacion.informes.index', ['periodo' => $periodo->id, 'tipo' => 'detallado']))->assertOk()->assertSee('pr&oacute;xima revisi&oacute;n', false)->assertSee('20/11/2026');
        $this->actingAs($admin)->get(route('planificacion.riesgos.index', ['periodo' => $periodo->id, 'orden' => 'identificacion', 'direccion' => 'desc']))
            ->assertOk()->assertSeeInOrder(['Zeta expansión', 'Alfa interrupción'])->assertSee('Parcialmente eficaz')->assertSee('Pendiente de evaluación');
        $this->actingAs($admin)->get(route('planificacion.acciones.index', ['periodo' => $periodo->id, 'orden' => 'accion', 'direccion' => 'desc']))
            ->assertOk()->assertSeeInOrder(['Acción Zeta', 'Acción Alfa']);
    }

    public function test_closed_action_is_locked_and_can_only_be_reopened_with_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $admin->id]);
        $riesgo = Riesgo::create([
            'periodo_id' => $periodo->id, 'numero' => 1, 'codigo' => 'RO-2026-001', 'tipo' => 'riesgo',
            'proceso' => 'Operaciones', 'identificacion' => 'Interrupción', 'efecto_potencial' => 'Demora',
            'criterio_eficacia' => 'Reducir interrupciones', 'impacto_inicial' => 2, 'probabilidad_inicial' => 2,
            'indice_inicial' => 4, 'eficacia' => 'parcial', 'estado' => 'en_proceso',
            'creado_por' => $admin->id, 'actualizado_por' => $admin->id,
        ]);
        $accion = $riesgo->acciones()->create([
            'descripcion' => 'Probar recuperación', 'responsable_id' => $admin->id, 'fecha_objetivo' => '2026-09-01',
            'estado' => 'completada', 'resultado' => 'Prueba completada', 'completada_en' => now(),
            'creado_por' => $admin->id, 'actualizado_por' => $admin->id,
        ]);

        $this->actingAs($admin)->patch(route('planificacion.acciones.actualizar', $accion), [
            'estado' => 'pendiente', 'resultado' => 'Cambio directo',
        ])->assertSessionHasErrors('estado');
        $this->assertSame('completada', $accion->fresh()->estado);

        $this->actingAs($admin)->post(route('planificacion.acciones.seguimientos.store', $accion), [
            'fecha' => '2026-08-10', 'detalle' => 'Se adjunta evidencia posterior al cierre.',
        ])->assertSessionHasErrors('documento_id');
        $this->actingAs($admin)->post(route('planificacion.acciones.seguimientos.store', $accion), [
            'fecha' => '2026-08-10', 'detalle' => 'Se adjunta evidencia posterior al cierre.',
            'enlace_externo' => 'https://evidencias.example/prueba-recuperacion',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, $accion->seguimientos()->count());
        $this->assertSame('evidencia_complementaria', $accion->seguimientos()->first()->tipo);

        $this->actingAs($admin)->patch(route('planificacion.acciones.reabrir', $accion), [
            'motivo' => 'Se detectó que la prueba debe repetirse.', 'comprende_impacto' => 1, 'confirmacion' => 'incorrecto',
        ])->assertSessionHasErrors('confirmacion');
        $this->actingAs($admin)->patch(route('planificacion.acciones.reabrir', $accion), [
            'motivo' => 'Se detectó que la prueba debe repetirse.', 'comprende_impacto' => 1, 'confirmacion' => 'REABRIR ACCION',
        ])->assertSessionHasNoErrors();

        $this->assertSame('en_proceso', $accion->fresh()->estado);
        $this->assertNull($accion->fresh()->resultado);
        $this->assertSame('pendiente', $riesgo->fresh()->eficacia);
        $this->assertDatabaseHas('iso_accion_transiciones', ['accion_id' => $accion->id, 'estado_anterior' => 'completada', 'estado_nuevo' => 'en_proceso', 'motivo' => 'Se detectó que la prueba debe repetirse.']);
        $this->actingAs($admin)->get(route('planificacion.riesgos.show', $riesgo))->assertOk()->assertSee('Acción reabierta')->assertSee('Se detectó que la prueba debe repetirse.');
    }
}
