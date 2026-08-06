<?php

namespace Tests\Feature\Iso;

use App\Models\Iso\Periodo;
use App\Models\Iso\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_offer_summary_and_detail_without_using_future_supplier_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $periodo2026 = Periodo::create(['anio' => 2026, 'nombre' => 'Planificacion 2026', 'estado' => 'cerrado', 'cambio_climatico_relevante' => false, 'fundamento_cambio_climatico' => 'Sin impacto directo.', 'creado_por' => $admin->id]);
        $periodo2027 = Periodo::create(['anio' => 2027, 'nombre' => 'Planificacion 2027', 'estado' => 'vigente', 'creado_por' => $admin->id]);
        $proveedor = Proveedor::create([
            'codigo' => 'PR-0001', 'nombre' => 'Proveedor temporal', 'producto_servicio' => 'Servicio critico',
            'area_responsable' => 'Administracion', 'fecha_alta' => '2020-01-01', 'criticidad' => 'critico',
            'periodicidad_meses' => 12, 'estado' => 'activo', 'creado_por' => $admin->id, 'actualizado_por' => $admin->id,
        ]);
        $proveedor->selecciones()->create([
            'fecha' => '2027-02-01', 'calificaciones' => [], 'puntaje' => 8, 'resultado' => 'aprobado',
            'conclusion' => 'Seleccion futura que no corresponde a 2026.', 'evaluado_por' => $admin->id,
        ]);
        $proveedor->evaluaciones()->create([
            'periodo_id' => $periodo2027->id, 'fecha_evaluacion' => '2027-06-01', 'tipo' => 'periodica',
            'calificaciones' => [], 'puntaje' => 8, 'resultado' => 'aprobado', 'decision' => 'continuar',
            'estado_ciclo' => 'cerrada', 'conclusion' => 'Evaluacion futura que no corresponde a 2026.',
            'requiere_analisis_riesgo' => false, 'evaluado_por' => $admin->id,
        ]);

        $this->actingAs($admin)->get(route('planificacion.informes.index', ['periodo' => $periodo2026->id, 'tipo' => 'resumido']))
            ->assertOk()->assertSee('Informe para auditor&iacute;a &middot; Resumido', false)
            ->assertSee('Proveedor temporal')->assertSee('Pendiente en 2026')
            ->assertDontSee('Evaluacion futura que no corresponde a 2026.');

        $this->actingAs($admin)->get(route('planificacion.informes.index', ['periodo' => $periodo2026->id, 'tipo' => 'detallado']))
            ->assertOk()->assertSee('Informe para auditor&iacute;a &middot; Detallado', false)
            ->assertSee('Sin selecci&oacute;n registrada hasta el cierre del per&iacute;odo.', false)
            ->assertSee('Sin evaluaci&oacute;n registrada para 2026.', false)
            ->assertDontSee('Seleccion futura que no corresponde a 2026.')
            ->assertDontSee('Evaluacion futura que no corresponde a 2026.');

        $this->actingAs($admin)->get(route('planificacion.informes.index', ['periodo' => $periodo2026->id, 'tipo' => 'inexistente']))->assertNotFound();
    }
}
