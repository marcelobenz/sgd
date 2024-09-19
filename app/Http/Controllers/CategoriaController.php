<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        $categorias = Categoria::whereNull('parent_id')->with('subcategorias')->get();
        //$categorias = Categoria::all();
        return view('categorias.index', compact('categorias'));
    }

    public function create()
    {
        $categorias = Categoria::whereNull('parent_id')->get();
        return view('categorias.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_categoria' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categorias,id'
        ]);

        Categoria::create($request->all());
        return redirect()->route('categorias.index')->with('success', 'Categoría creada con éxito.');
    }

    public function edit(Categoria $categoria)
    {
        $categorias = Categoria::whereNull('parent_id')->where('id', '!=', $categoria->id)->get();
        return view('categorias.edit', compact('categoria', 'categorias'));
    }

    public function update(Request $request, Categoria $categoria)
    {
        $request->validate([
            'nombre_categoria' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categorias,id'
        ]);

        $categoria->update($request->all());
        return redirect()->route('categorias.index')->with('success', 'Categoría actualizada con éxito.');
    }

    public function destroy(Categoria $categoria)
    {
        try {
            // Verificar si la categoría tiene documentos asociados
            if ($categoria->documentos()->exists()) {
                return redirect()->route('categorias.index')->with('error', 'No se puede eliminar la categoría porque tiene documentos asociados.');
            }
    
            // Verificar si la categoría tiene historial de documentos asociado
            if ($categoria->historialDocumentos()->exists()) {
                return redirect()->route('categorias.index')->with('error', 'No se puede eliminar la categoría porque tiene historial de documentos asociado.');
            }
    
            // Verificar si la categoría tiene subcategorías asociadas
            if ($categoria->subcategorias()->exists()) {
                return redirect()->route('categorias.index')->with('error', 'No se puede eliminar la categoría porque tiene subcategorías asociadas.');
            }
    
            // Eliminar la categoría si no tiene documentos, historial ni subcategorías asociadas
            $categoria->delete();
            return redirect()->route('categorias.index')->with('success', 'Categoría eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('categorias.index')->with('error', $e->getMessage());
        }
    }
            
}
