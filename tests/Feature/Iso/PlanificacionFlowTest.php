<?php

namespace Tests\Feature\Iso;

use App\Models\Iso\Contexto;
use App\Models\Iso\Periodo;
use App\Models\Iso\Riesgo;
use App\Models\Iso\PeriodoTransicion;
use App\Models\Iso\ParteInteresada;
use App\Models\Iso\ParteInteresadaEvaluacion;
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
        $this->actingAs($admin)->patch(route('planificacion.periodos.activar', $periodo))->assertSessionHasNoErrors();

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
        $this->actingAs($admin)->get(route('planificacion.informes.index', ['periodo' => $periodo->id]))->assertOk()->assertSee('Partes interesadas y evaluación periódica')->assertSee('Clientes')->assertSee('Doc. SGD: Encuesta de satisfacción 2026')->assertSee(route('documentos.validaPermiso',['id'=>$documento->id,'ruta'=>'documentos.show','permiso'=>'puedeLeer']));
    }
}
