<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VacacionesSolicitud;
use App\Models\VacacionesSaldo;
use App\Notifications\VacacionesSolicitudActualizada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VacacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_vacations_and_days_are_calculated_as_calendar_days(): void
    {
        $user = User::factory()->create(['fecha_ingreso' => '2020-01-15']);

        $response = $this->actingAs($user)->post('/vacaciones', [
            'fecha_desde' => '2026-02-02',
            'fecha_hasta' => '2026-02-06',
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vacaciones_solicitudes', [
            'user_id' => $user->id,
            'dias' => 5,
            'estado' => 'pendiente',
        ]);
    }

    public function test_only_the_area_manager_can_approve_a_request(): void
    {
        $jefe = User::factory()->create(['role' => 'user']);
        $empleado = User::factory()->create([
            'fecha_ingreso' => '2018-01-01',
            'jefe_id' => $jefe->id,
        ]);
        $solicitud = VacacionesSolicitud::create([
            'user_id' => $empleado->id,
            'creada_por' => $empleado->id,
            'fecha_desde' => '2026-02-02',
            'fecha_hasta' => '2026-02-06',
            'dias' => 5,
        ]);

        $this->actingAs($jefe)->patch("/vacaciones/{$solicitud->id}/aprobar")
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vacaciones_solicitudes', ['id' => $solicitud->id, 'estado' => 'aprobada']);
    }

    public function test_user_can_cancel_a_pending_own_request(): void
    {
        $user = User::factory()->create(['fecha_ingreso' => '2020-01-15']);
        $solicitud = VacacionesSolicitud::create([
            'user_id' => $user->id,
            'creada_por' => $user->id,
            'fecha_desde' => '2026-02-02',
            'fecha_hasta' => '2026-02-06',
            'dias' => 5,
        ]);

        $this->actingAs($user)->patch("/vacaciones/{$solicitud->id}/cancelar")
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vacaciones_solicitudes', ['id' => $solicitud->id, 'estado' => 'cancelada']);
    }

    public function test_manager_can_reverse_a_future_approval(): void
    {
        $jefe = User::factory()->create();
        $empleado = User::factory()->create([
            'fecha_ingreso' => '2018-01-01',
            'jefe_id' => $jefe->id,
        ]);
        $solicitud = VacacionesSolicitud::create([
            'user_id' => $empleado->id,
            'creada_por' => $empleado->id,
            'fecha_desde' => '2026-12-01',
            'fecha_hasta' => '2026-12-05',
            'dias' => 5,
            'estado' => 'aprobada',
            'revisada_por' => $jefe->id,
            'revisada_at' => now(),
        ]);

        $this->actingAs($jefe)->patch("/vacaciones/{$solicitud->id}/desaprobar")
            ->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('vacaciones_solicitudes', ['id' => $solicitud->id, 'estado' => 'pendiente']);
    }

    public function test_employee_receives_email_notification_when_request_changes_state(): void
    {
        Notification::fake();
        $jefe = User::factory()->create();
        $empleado = User::factory()->create(['fecha_ingreso' => '2018-01-01', 'jefe_id' => $jefe->id]);
        $solicitud = VacacionesSolicitud::create([
            'user_id' => $empleado->id,
            'creada_por' => $empleado->id,
            'fecha_desde' => '2026-12-01',
            'fecha_hasta' => '2026-12-05',
            'dias' => 5,
        ]);

        $this->actingAs($jefe)->patch("/vacaciones/{$solicitud->id}/aprobar");
        Notification::assertSentTo($empleado, VacacionesSolicitudActualizada::class, fn ($notification) => $notification->resultado === 'aprobada');

        $this->actingAs($jefe)->patch("/vacaciones/{$solicitud->id}/desaprobar");
        Notification::assertSentTo($empleado, VacacionesSolicitudActualizada::class, fn ($notification) => $notification->resultado === 'desaprobada');

        $this->actingAs($jefe)->patch("/vacaciones/{$solicitud->id}/rechazar", ['motivo_rechazo' => 'No hay cobertura suficiente.']);
        Notification::assertSentTo($empleado, VacacionesSolicitudActualizada::class, fn ($notification) => $notification->resultado === 'rechazada');
    }

    public function test_employee_and_manager_have_separate_access_areas(): void
    {
        $jefe = User::factory()->create();
        $empleado = User::factory()->create(['jefe_id' => $jefe->id]);

        $this->actingAs($empleado)->get('/vacaciones/admin-licencias')->assertForbidden();
        $this->actingAs($empleado)->get('/vacaciones/configuracion-laboral')->assertForbidden();
        $this->actingAs($jefe)->get('/vacaciones/admin-licencias')
            ->assertOk()->assertSee('Resumen del equipo')->assertSee('Solicitudes para validar');
        $this->actingAs($empleado)->get('/vacaciones/solicitud-estado')
            ->assertOk()->assertSee('Mis solicitudes');
    }

    public function test_manager_can_see_employee_vacation_history(): void
    {
        $jefe = User::factory()->create();
        $empleado = User::factory()->create(['jefe_id' => $jefe->id]);
        VacacionesSolicitud::create([
            'user_id' => $empleado->id,
            'creada_por' => $empleado->id,
            'fecha_desde' => '2026-01-12',
            'fecha_hasta' => '2026-01-16',
            'dias' => 5,
            'estado' => 'aprobada',
            'revisada_por' => $jefe->id,
            'revisada_at' => now(),
        ]);

        $this->actingAs($jefe)->get('/vacaciones/admin-licencias')
            ->assertOk()
            ->assertSee('Historial de vacaciones 2026')
            ->assertSee($empleado->name)
            ->assertSee('12/01/2026 al 16/01/2026');
    }

    public function test_pending_vacation_request_appears_only_for_the_responsible_manager(): void
    {
        $jefe = User::factory()->create();
        $otroUsuario = User::factory()->create();
        $empleado = User::factory()->create(['jefe_id' => $jefe->id]);
        VacacionesSolicitud::create([
            'user_id' => $empleado->id,
            'creada_por' => $empleado->id,
            'fecha_desde' => '2026-12-01',
            'fecha_hasta' => '2026-12-05',
            'dias' => 5,
        ]);

        $this->actingAs($jefe)->get('/mis-pendientes')
            ->assertOk()
            ->assertSee('Solicitud de '.$empleado->name)
            ->assertSee('Administración de licencias');
        $this->actingAs($otroUsuario)->get('/mis-pendientes')
            ->assertOk()
            ->assertDontSee('Solicitud de '.$empleado->name);
    }

    public function test_manager_can_filter_history_and_validation_by_employee_and_status(): void
    {
        $jefe = User::factory()->create();
        $empleado = User::factory()->create(['jefe_id' => $jefe->id]);
        $otroEmpleado = User::factory()->create(['jefe_id' => $jefe->id]);
        VacacionesSolicitud::create([
            'user_id' => $empleado->id,
            'creada_por' => $empleado->id,
            'fecha_desde' => '2026-12-01',
            'fecha_hasta' => '2026-12-05',
            'dias' => 5,
            'estado' => 'pendiente',
        ]);
        VacacionesSolicitud::create([
            'user_id' => $otroEmpleado->id,
            'creada_por' => $otroEmpleado->id,
            'fecha_desde' => '2026-11-02',
            'fecha_hasta' => '2026-11-06',
            'dias' => 5,
            'estado' => 'rechazada',
        ]);

        $this->actingAs($jefe)->get('/vacaciones/admin-licencias?empleado_id='.$empleado->id.'&estado_licencia=pendiente')
            ->assertOk()
            ->assertSee($empleado->name)
            ->assertSee('01/12/2026 al 05/12/2026')
            ->assertDontSee('02/11/2026 al 06/11/2026');
    }

    public function test_manager_cannot_operate_on_another_teams_request(): void
    {
        $jefe = User::factory()->create();
        $otroJefe = User::factory()->create();
        User::factory()->create(['jefe_id' => $jefe->id]);
        $empleado = User::factory()->create(['jefe_id' => $otroJefe->id]);
        $solicitud = VacacionesSolicitud::create([
            'user_id' => $empleado->id,
            'creada_por' => $empleado->id,
            'fecha_desde' => '2026-12-01',
            'fecha_hasta' => '2026-12-05',
            'dias' => 5,
        ]);

        $this->actingAs($jefe)->get('/vacaciones/admin-licencias')
            ->assertOk()
            ->assertDontSee($empleado->name);
        $this->actingAs($jefe)->patch("/vacaciones/{$solicitud->id}/aprobar")
            ->assertForbidden();
    }

    public function test_admin_can_load_previous_period_balance_and_request_consumes_oldest_balance_first(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $empleado = User::factory()->create(['fecha_ingreso' => '2018-01-01']);

        $this->actingAs($admin)->patch("/vacaciones/usuarios/{$empleado->id}/saldo", [
            'anio' => 2025,
            'dias_pendientes' => 6,
            'observaciones' => 'Saldo informado por RRHH',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($empleado)->get('/vacaciones/solicitud-estado?anio=2026')
            ->assertOk()
            ->assertSee('Saldo anterior')
            ->assertSee('Total disponible')
            ->assertSee('2025');

        $this->actingAs($empleado)->post('/vacaciones', [
            'fecha_desde' => '2026-02-02',
            'fecha_hasta' => '2026-02-09',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $solicitud = VacacionesSolicitud::query()->latest('id')->first();
        $this->assertDatabaseHas('vacaciones_solicitud_periodos', [
            'vacaciones_solicitud_id' => $solicitud->id,
            'anio' => 2025,
            'dias' => 6,
        ]);
        $this->assertDatabaseHas('vacaciones_solicitud_periodos', [
            'vacaciones_solicitud_id' => $solicitud->id,
            'anio' => 2026,
            'dias' => 2,
        ]);
        $this->assertDatabaseHas('vacaciones_saldos', [
            'user_id' => $empleado->id,
            'anio' => 2025,
            'dias_pendientes' => 6,
        ]);
    }

    public function test_current_year_balance_can_be_loaded_for_next_year_planning(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $empleado = User::factory()->create(['fecha_ingreso' => '2018-01-01']);
        $anioActual = now()->year;

        $this->actingAs($admin)->patch("/vacaciones/usuarios/{$empleado->id}/saldo", [
            'anio' => $anioActual,
            'dias_pendientes' => 5,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($empleado)->get('/vacaciones/solicitud-estado?anio='.($anioActual + 1))
            ->assertOk()
            ->assertSee((string) $anioActual)
            ->assertSee('Saldo anterior');
    }
}
