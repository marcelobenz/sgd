@foreach ($categorias as $categoriaPrincipal)
    <option value="{{ $categoriaPrincipal->id }}" @selected((string) $categoriaSeleccionada === (string) $categoriaPrincipal->id)>
        {{ $categoriaPrincipal->nombre_categoria }} — Principal
    </option>
    @foreach ($categoriaPrincipal->subcategorias as $subcategoria)
        <option value="{{ $subcategoria->id }}" @selected((string) $categoriaSeleccionada === (string) $subcategoria->id)>
            &nbsp;&nbsp;&nbsp;↳ {{ $subcategoria->nombre_categoria }} — Subcategoría
        </option>
    @endforeach
@endforeach
