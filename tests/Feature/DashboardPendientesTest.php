<?php

namespace Tests\Feature;

use App\Models\Iso\Periodo;
use App\Models\Iso\Riesgo;
use App\Models\Iso\UsuarioPermiso;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPendientesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_manager_sees_personal_iso_actions_on_dashboard_and_inbox(): void
    {
        Carbon::setTestNow('2026-08-08 10:00:00');
        $user = User::factory()->create(['role' => 'user', 'habilitado' => true]);
        UsuarioPermiso::create(['user_id' => $user->id, 'puede_ver' => true, 'puede_gestionar' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $user->id]);
        $riesgo = Riesgo::create([
            'periodo_id' => $periodo->id,
            'numero' => 1,
            'codigo' => 'RO-2026-001',
            'tipo' => 'riesgo',
            'proceso' => 'Operaciones',
            'identificacion' => 'Interrupción del servicio',
            'efecto_potencial' => 'Demoras operativas',
            'impacto_inicial' => 3,
            'probabilidad_inicial' => 3,
            'indice_inicial' => 9,
            'responsable_id' => $user->id,
            'fecha_verificacion_prevista' => '2026-08-08',
            'estado' => 'en_proceso',
            'creado_por' => $user->id,
            'actualizado_por' => $user->id,
        ]);
        $riesgo->acciones()->create([
            'descripcion' => 'Probar el plan de continuidad',
            'responsable_id' => $user->id,
            'fecha_objetivo' => '2026-08-07',
            'estado' => 'pendiente',
            'creado_por' => $user->id,
            'actualizado_por' => $user->id,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Planificación 2026')
            ->assertSee('Probar el plan de continuidad')
            ->assertSee('Riesgos altos');

        $this->actingAs($user)->get(route('pendientes.index'))
            ->assertOk()
            ->assertSee('Probar el plan de continuidad')
            ->assertSee('Verificar RO-2026-001')
            ->assertSee('Vencida');
    }

    public function test_view_only_user_does_not_receive_executable_iso_actions(): void
    {
        $manager = User::factory()->create(['role' => 'user', 'habilitado' => true]);
        $viewer = User::factory()->create(['role' => 'user', 'habilitado' => true]);
        UsuarioPermiso::create(['user_id' => $viewer->id, 'puede_ver' => true]);
        $periodo = Periodo::create(['anio' => 2026, 'nombre' => 'Planificación 2026', 'estado' => 'vigente', 'creado_por' => $manager->id]);
        $riesgo = Riesgo::create([
            'periodo_id' => $periodo->id, 'numero' => 1, 'codigo' => 'RO-2026-001', 'tipo' => 'riesgo',
            'proceso' => 'Operaciones', 'identificacion' => 'Riesgo visible', 'efecto_potencial' => 'Demora',
            'impacto_inicial' => 2, 'probabilidad_inicial' => 2, 'indice_inicial' => 4,
            'responsable_id' => $viewer->id, 'estado' => 'en_proceso',
            'creado_por' => $manager->id, 'actualizado_por' => $manager->id,
        ]);
        $riesgo->acciones()->create([
            'descripcion' => 'Acción no ejecutable por el usuario de consulta', 'responsable_id' => $viewer->id,
            'fecha_objetivo' => '2026-09-01', 'estado' => 'pendiente',
            'creado_por' => $manager->id, 'actualizado_por' => $manager->id,
        ]);

        $this->actingAs($viewer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Estado de las acciones')
            ->assertDontSee('Acción no ejecutable por el usuario de consulta');

        $this->actingAs($viewer)->get(route('pendientes.index'))
            ->assertOk()
            ->assertDontSee('Acción no ejecutable por el usuario de consulta');
    }
}
