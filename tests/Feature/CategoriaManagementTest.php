<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_index_renders_complete_expandable_hierarchy_and_summary(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $principal = Categoria::create(['nombre_categoria' => 'Gestión de calidad']);
        Categoria::create(['nombre_categoria' => 'Procedimientos', 'parent_id' => $principal->id]);
        Categoria::create(['nombre_categoria' => 'Registros', 'parent_id' => $principal->id]);

        $this->actingAs($user)->get(route('categorias.index'))
            ->assertOk()
            ->assertSee('Gestión de calidad')
            ->assertSee('Procedimientos')
            ->assertSee('Registros')
            ->assertSee('Expandir todas')
            ->assertSee('data-level="1"', false)
            ->assertDontSee('data-level="2"', false)
            ->assertSee('3</strong><small>Categorías totales', false);
    }

    public function test_create_subcategory_link_preselects_parent_and_store_uses_validated_fields(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $principal = Categoria::create(['nombre_categoria' => 'Compras']);

        $this->actingAs($user)->get(route('categorias.create', ['parent_id' => $principal->id]))
            ->assertOk()
            ->assertSee('Ubicación en el árbol')
            ->assertSee('value="'.$principal->id.'" data-name="Compras" selected', false);

        $this->actingAs($user)->post(route('categorias.store'), [
            'nombre_categoria' => 'Proveedores',
            'parent_id' => $principal->id,
            'tipo_visual' => 'subcategoria',
            'campo_no_permitido' => 'ignorado',
        ])->assertSessionHasNoErrors()->assertRedirect(route('categorias.index'));

        $this->assertDatabaseHas('categorias', [
            'nombre_categoria' => 'Proveedores',
            'parent_id' => $principal->id,
        ]);
    }

    public function test_subcategory_cannot_be_used_as_parent(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $principal = Categoria::create(['nombre_categoria' => 'Calidad']);
        $hija = Categoria::create(['nombre_categoria' => 'Registros', 'parent_id' => $principal->id]);

        $this->actingAs($user)->post(route('categorias.store'), [
            'nombre_categoria' => '2026',
            'parent_id' => $hija->id,
        ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('categorias', ['nombre_categoria' => '2026']);
    }

    public function test_category_with_children_cannot_become_subcategory(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $principal = Categoria::create(['nombre_categoria' => 'Calidad']);
        Categoria::create(['nombre_categoria' => 'Registros', 'parent_id' => $principal->id]);
        $otraPrincipal = Categoria::create(['nombre_categoria' => 'Administración']);

        $this->actingAs($user)->put(route('categorias.update', $principal), [
            'nombre_categoria' => 'Calidad',
            'parent_id' => $otraPrincipal->id,
        ])->assertSessionHasErrors('parent_id');

        $this->assertNull($principal->fresh()->parent_id);
    }

    public function test_model_also_enforces_two_level_limit(): void
    {
        $principal = Categoria::create(['nombre_categoria' => 'Auditorías']);
        $subcategoria = Categoria::create(['nombre_categoria' => 'Auditorías internas', 'parent_id' => $principal->id]);

        $this->expectException(\DomainException::class);
        Categoria::create(['nombre_categoria' => '2026', 'parent_id' => $subcategoria->id]);
    }

    public function test_category_with_children_cannot_be_deleted(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'habilitado' => true]);
        $principal = Categoria::create(['nombre_categoria' => 'Recursos humanos']);
        Categoria::create(['nombre_categoria' => 'Capacitación', 'parent_id' => $principal->id]);

        $this->actingAs($user)->delete(route('categorias.destroy', $principal))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categorias', ['id' => $principal->id]);
    }
}
