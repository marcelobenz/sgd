<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Documento;
use App\Models\DocumentoPermiso;
use App\Models\User;
use App\Notifications\DocumentoPendienteAprobacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DocumentoPermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_error_de_validacion_conserva_los_permisos_enviados(): void
    {
        $creador = User::factory()->create(['habilitado' => true]);
        $lector = User::factory()->create(['habilitado' => true]);

        $response = $this->actingAs($creador)->post(route('documentos.store'), [
            '_documento_form' => '1',
            'titulo' => 'Documento incompleto',
            'permisos' => [
                $lector->id => ['puede_leer' => 'on'],
            ],
        ]);

        $response->assertSessionHasErrors(['archivo', 'id_categoria', 'contenido']);
        $this->assertSame('on', session()->getOldInput("permisos.{$lector->id}.puede_leer"));
    }

    public function test_al_editar_solo_notifica_a_los_aprobadores_nuevos(): void
    {
        Notification::fake();

        $creador = User::factory()->create(['habilitado' => true]);
        $aprobadorExistente = User::factory()->create(['habilitado' => true]);
        $aprobadorNuevo = User::factory()->create(['habilitado' => true]);
        $categoria = Categoria::create(['nombre_categoria' => 'Procedimientos']);
        $documento = Documento::forceCreate([
            'titulo' => 'Procedimiento',
            'path' => 'documentos/prueba.pdf',
            'contenido' => 'Contenido',
            'estado' => 'pendiente de aprobación',
            'id_categoria' => $categoria->id,
            'id_usr_creador' => $creador->id,
            'id_usr_ultima_modif' => $creador->id,
            'version' => 1,
        ]);

        foreach ([$creador, $aprobadorExistente] as $usuario) {
            DocumentoPermiso::create([
                'documento_id' => $documento->id,
                'user_id' => $usuario->id,
                'puede_leer' => true,
                'puede_escribir' => $usuario->is($creador),
                'puede_aprobar' => true,
                'puede_eliminar' => $usuario->is($creador),
            ]);
        }

        $response = $this->actingAs($creador)->put(route('documentos.update', $documento), [
            '_documento_form' => '1',
            'titulo' => 'Procedimiento actualizado',
            'id_categoria' => $categoria->id,
            'permisos' => [
                $aprobadorExistente->id => ['puede_leer' => 'on', 'puede_aprobar' => 'on'],
                $aprobadorNuevo->id => ['puede_leer' => 'on', 'puede_aprobar' => 'on'],
            ],
        ]);

        $response->assertRedirect(route('documentos.edit', $documento));
        Notification::assertNotSentTo($aprobadorExistente, DocumentoPendienteAprobacion::class);
        Notification::assertSentTo($aprobadorNuevo, DocumentoPendienteAprobacion::class);
    }
}
