@php
    $categoria = $nodo['categoria'];
    $tieneHijos = $nodo['hijos']->isNotEmpty();
    $bloqueada = $categoria->documentos_count > 0 || $categoria->historial_documentos_count > 0 || $tieneHijos;
    $motivo = $tieneHijos ? 'Tiene subcategorías' : ($categoria->documentos_count > 0 ? 'Tiene documentos' : ($categoria->historial_documentos_count > 0 ? 'Tiene historial' : 'Se puede eliminar'));
    $ancestros = isset($ancestros) ? [...$ancestros, $categoria->parent_id] : [];
    $ancestros = array_values(array_filter($ancestros));
@endphp
<tr class="category-row" data-id="{{ $categoria->id }}" data-parent="{{ $categoria->parent_id ?? '' }}" data-root="{{ $rootId }}" data-level="{{ $nivel }}" data-ancestors="{{ implode(',', $ancestros) }}" data-name="{{ str($categoria->nombre_categoria)->lower() }}" data-children="{{ $nodo['descendientes'] }}" data-total="{{ $nodo['documentos_totales'] }}" @if($nivel>0) hidden @endif>
    <td><div class="category-name" style="--level:{{ $nivel }}"><span class="tree-spacer"></span>@if($tieneHijos)<button class="tree-toggle" type="button" aria-expanded="false" aria-label="Mostrar subcategorías de {{ $categoria->nombre_categoria }}"><i class="fa-solid fa-chevron-right"></i></button>@else<span class="tree-placeholder"></span>@endif<span class="folder-icon"><i class="fa-{{ $tieneHijos?'solid':'regular' }} fa-folder{{ $tieneHijos?'-tree':'' }}"></i></span><span class="category-name-copy"><strong>{{ $categoria->nombre_categoria }}</strong><small>{{ $nivel===0?'Categoría principal':'Subcategoría nivel '.($nivel+1) }}@if($nodo['descendientes']) · {{ $nodo['descendientes'] }} descendientes @endif</small></span></div></td>
    <td class="text-center"><span class="number-badge">{{ $categoria->documentos_count }}</span></td>
    <td class="text-center"><span class="tree-total">{{ $nodo['documentos_totales'] }}</span></td>
    <td><div class="state-note"><span class="state-pill {{ $bloqueada?'locked':'' }}">{{ $bloqueada?'Protegida':'Disponible' }}</span><small>{{ $motivo }}</small></div></td>
    <td><div class="category-actions">@if($nivel===0)<a class="btn btn-add" href="{{ route('categorias.create',['parent_id'=>$categoria->id]) }}" title="Agregar subcategoría"><i class="fa-solid fa-folder-plus"></i></a>@else<button class="btn btn-add" type="button" disabled title="Las subcategorías no pueden contener otras categorías"><i class="fa-solid fa-folder-plus"></i></button>@endif<a class="btn btn-edit" href="{{ route('categorias.edit',$categoria) }}" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>@if($bloqueada)<button class="btn btn-delete" type="button" disabled title="{{ $motivo }}"><i class="fa-regular fa-trash-can"></i></button>@else<form class="delete-category-form" data-name="{{ $categoria->nombre_categoria }}" action="{{ route('categorias.destroy',$categoria) }}" method="POST">@csrf @method('DELETE')<button class="btn btn-delete" type="submit" title="Eliminar"><i class="fa-regular fa-trash-can"></i></button></form>@endif</div></td>
</tr>
@foreach($nodo['hijos'] as $hijo)
    @include('categorias._row', ['nodo'=>$hijo, 'nivel'=>$nivel+1, 'rootId'=>$rootId, 'ancestros'=>$ancestros])
@endforeach
