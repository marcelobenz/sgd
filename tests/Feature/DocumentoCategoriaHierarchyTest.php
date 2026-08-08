<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Documento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentoCategoriaHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_document_shows_hierarchy_and_accepts_valid_preselection(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $principal = Categoria::create(['nombre_categoria' => 'Auditorías']);
        $subcategoria = Categoria::create(['nombre_categoria' => 'Audit internas', 'parent_id' => $principal->id]);

        $this->actingAs($user)->get(route('documentos.create', ['categoria' => $subcategoria->id]))
            ->assertOk()
            ->assertSee('Auditorías — Principal')
            ->assertSee('↳ Audit internas — Subcategoría')
            ->assertSee('value="'.$subcategoria->id.'" selected', false);

        $this->actingAs($user)->get(route('documentos.create', ['categoria' => 999999]))
            ->assertOk()
            ->assertDontSee('value="999999" selected', false);
    }

    public function test_edit_document_shows_hierarchy_and_preserves_current_category(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $principal = Categoria::create(['nombre_categoria' => 'Auditorías']);
        $subcategoria = Categoria::create(['nombre_categoria' => 'Audit externas', 'parent_id' => $principal->id]);
        $documento = $this->crearDocumento($user, $subcategoria, 'Informe externo');

        $this->actingAs($user)->get(route('documentos.edit', $documento))
            ->assertOk()
            ->assertSee('Auditorías — Principal')
            ->assertSee('↳ Audit externas — Subcategoría')
            ->assertSee('value="'.$subcategoria->id.'" selected', false);
    }

    public function test_document_category_panels_link_new_document_with_context(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $principal = Categoria::create(['nombre_categoria' => 'Auditorías']);
        $subcategoria = Categoria::create(['nombre_categoria' => 'Audit internas', 'parent_id' => $principal->id]);
        $this->crearDocumento($user, $principal, 'Plan de auditoría');
        $this->crearDocumento($user, $subcategoria, 'Informe interno');

        $this->actingAs($user)->get(route('documentos.index'))
            ->assertOk()
            ->assertSee(route('documentos.create', ['categoria' => $principal->id]), false)
            ->assertSee(route('documentos.create', ['categoria' => $subcategoria->id]), false);
    }

    private function crearDocumento(User $user, Categoria $categoria, string $titulo): Documento
    {
        $documento = new Documento;
        $documento->forceFill([
            'titulo' => $titulo,
            'path' => 'pruebas/'.$titulo.'.pdf',
            'contenido' => 'Contenido de prueba',
            'estado' => 'aprobado',
            'id_categoria' => $categoria->id,
            'id_usr_creador' => $user->id,
            'id_usr_ultima_modif' => $user->id,
            'version' => 1,
        ])->save();

        return $documento;
    }
}
