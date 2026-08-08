<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::query()
            ->withCount(['documentos', 'historialDocumentos', 'subcategorias'])
            ->orderBy('nombre_categoria')
            ->get();

        $arbol = $this->armarArbol($categorias);

        return view('categorias.index', [
            'arbol' => $arbol,
            'resumen' => [
                'total' => $categorias->count(),
                'principales' => $categorias->whereNull('parent_id')->count(),
                'subcategorias' => $categorias->whereNotNull('parent_id')->count(),
                'documentos' => $categorias->sum('documentos_count'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $parentPreseleccionado = Categoria::query()
            ->whereKey($request->integer('parent_id'))
            ->whereNull('parent_id')
            ->value('id');

        return view('categorias.create', [
            'opcionesPadre' => $this->opcionesJerarquicas(),
            'parentPreseleccionado' => $parentPreseleccionado,
            'puedeSerSubcategoria' => true,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre_categoria' => ['required', 'string', 'max:255', Rule::unique('categorias', 'nombre_categoria')],
            'parent_id' => ['nullable', 'integer', Rule::exists('categorias', 'id')->whereNull('parent_id')],
        ]);

        Categoria::create($data);

        return redirect()->route('categorias.index')->with('success', 'Categoría creada con éxito.');
    }

    public function edit(Categoria $categoria)
    {
        return view('categorias.edit', [
            'categoria' => $categoria,
            'opcionesPadre' => $this->opcionesJerarquicas($categoria),
            'parentPreseleccionado' => $categoria->parent_id,
            'puedeSerSubcategoria' => ! $categoria->subcategorias()->exists(),
        ]);
    }

    public function update(Request $request, Categoria $categoria)
    {
        $data = $request->validate([
            'nombre_categoria' => ['required', 'string', 'max:255', Rule::unique('categorias', 'nombre_categoria')->ignore($categoria->id)],
            'parent_id' => ['nullable', 'integer', Rule::exists('categorias', 'id')->whereNull('parent_id')],
        ]);

        if ((int) ($data['parent_id'] ?? 0) === $categoria->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'Una categoría no puede depender de sí misma.',
            ]);
        }

        if (($data['parent_id'] ?? null) && $categoria->subcategorias()->exists()) {
            throw ValidationException::withMessages([
                'parent_id' => 'Una categoría con subcategorías no puede convertirse en subcategoría.',
            ]);
        }

        $categoria->update($data);

        return redirect()->route('categorias.index')->with('success', 'Categoría actualizada con éxito.');
    }

    public function destroy(Categoria $categoria)
    {
        if ($categoria->documentos()->exists()) {
            return redirect()->route('categorias.index')->with('error', 'No se puede eliminar la categoría porque tiene documentos asociados.');
        }

        if ($categoria->historialDocumentos()->exists()) {
            return redirect()->route('categorias.index')->with('error', 'No se puede eliminar la categoría porque tiene historial de documentos asociado.');
        }

        if ($categoria->subcategorias()->exists()) {
            return redirect()->route('categorias.index')->with('error', 'No se puede eliminar la categoría porque tiene subcategorías asociadas.');
        }

        $categoria->delete();

        return redirect()->route('categorias.index')->with('success', 'Categoría eliminada con éxito.');
    }

    private function armarArbol(Collection $categorias): Collection
    {
        $porPadre = $categorias->groupBy(fn (Categoria $categoria) => $categoria->parent_id ?? 0);

        $construir = function (int $parentId = 0, array $visitados = []) use (&$construir, $porPadre): Collection {
            return $porPadre->get($parentId, collect())->map(function (Categoria $categoria) use (&$construir, $visitados) {
                if (in_array($categoria->id, $visitados, true)) {
                    return null;
                }

                $hijos = $construir($categoria->id, [...$visitados, $categoria->id])->filter()->values();

                return [
                    'categoria' => $categoria,
                    'hijos' => $hijos,
                    'documentos_totales' => $categoria->documentos_count + $hijos->sum('documentos_totales'),
                    'descendientes' => $hijos->count() + $hijos->sum('descendientes'),
                ];
            })->filter()->values();
        };

        return $construir();
    }

    private function opcionesJerarquicas(?Categoria $excluir = null): Collection
    {
        return Categoria::query()
            ->whereNull('parent_id')
            ->when($excluir, fn ($query) => $query->where('id', '!=', $excluir->id))
            ->orderBy('nombre_categoria')
            ->get()
            ->map(fn (Categoria $categoria) => ['categoria' => $categoria, 'nivel' => 0]);
    }
}
