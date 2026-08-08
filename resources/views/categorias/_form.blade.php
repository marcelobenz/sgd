@php
    $parentSeleccionado = old('parent_id', $parentPreseleccionado);
    $tipoInicial = $parentSeleccionado ? 'subcategoria' : 'principal';
@endphp
<div class="category-form-page">
    <header class="form-page-header">
        <a href="{{ route('categorias.index') }}" class="back-link"><i class="fa-solid fa-arrow-left"></i> Volver a categorías</a>
        <span>Organización documental</span><h1>{{ $titulo }}</h1><p>{{ $descripcion }}</p>
    </header>

    @if ($errors->any())
        <div class="alert alert-danger form-errors"><i class="fa-solid fa-circle-exclamation"></i><div><strong>Revisá los datos ingresados</strong><ul class="mb-0 mt-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
    @endif

    <form action="{{ $accion }}" method="POST" id="categoryForm">
        @csrf
        @if($metodo) @method($metodo) @endif
        <div class="form-layout">
            <section class="form-card">
                <header><span class="form-card-icon"><i class="fa-solid fa-folder-tree"></i></span><div><h2>Datos de la categoría</h2><p>Definí su nombre y posición en la estructura.</p></div></header>
                <div class="form-card-body">
                    <div class="form-group"><label for="nombre_categoria">Nombre de la categoría <span>*</span></label><input type="text" id="nombre_categoria" name="nombre_categoria" class="form-control @error('nombre_categoria') is-invalid @enderror" value="{{ old('nombre_categoria',$nombreActual) }}" maxlength="255" required autofocus placeholder="Ej.: Procedimientos operativos"><small>Usá un nombre breve, reconocible y diferente al resto.</small></div>

                    <fieldset class="category-type"><legend>Ubicación</legend>
                        <label><input type="radio" name="tipo_visual" value="principal" @checked($tipoInicial==='principal')><span class="type-option"><i class="fa-regular fa-folder"></i><span><strong>Categoría principal</strong><small>Aparecerá en el primer nivel.</small></span></span></label>
                        <label class="{{ $puedeSerSubcategoria ? '' : 'disabled' }}"><input type="radio" name="tipo_visual" value="subcategoria" @checked($tipoInicial==='subcategoria') @disabled(!$puedeSerSubcategoria)><span class="type-option"><i class="fa-solid fa-code-branch"></i><span><strong>Subcategoría</strong><small>{{ $puedeSerSubcategoria ? 'Dependerá de una categoría principal.' : 'No disponible porque contiene subcategorías.' }}</small></span></span></label>
                    </fieldset>

                    <div class="form-group parent-selector" id="parentSelector"><label for="parent_id">Categoría principal <span>*</span></label><select id="parent_id" name="parent_id" class="form-control @error('parent_id') is-invalid @enderror"><option value="">Seleccioná dónde ubicarla</option>@foreach($opcionesPadre as $opcion)<option value="{{ $opcion['categoria']->id }}" data-name="{{ $opcion['categoria']->nombre_categoria }}" @selected((string)$parentSeleccionado===(string)$opcion['categoria']->id)>{{ $opcion['categoria']->nombre_categoria }}</option>@endforeach</select><small>Las subcategorías sólo pueden depender de una categoría principal.</small></div>
                </div>
            </section>

            <aside class="preview-card">
                <span class="preview-kicker">Vista previa</span><h2>Ubicación en el árbol</h2>
                <div class="tree-preview" id="treePreview"><div class="preview-parent" id="previewParent"><i class="fa-solid fa-folder"></i><span>Sin categoría padre</span></div><div class="preview-child" id="previewChild"><i class="fa-regular fa-folder-open"></i><strong>{{ old('nombre_categoria',$nombreActual) ?: 'Nueva categoría' }}</strong></div></div>
                <div class="preview-help"><i class="fa-solid fa-circle-info"></i><p id="previewHelp">Las categorías principales organizan los grandes grupos documentales.</p></div>
            </aside>
        </div>

        <div class="form-actions"><a href="{{ route('categorias.index') }}" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> {{ $textoBoton }}</button></div>
    </form>
</div>

@push('styles')
<style>
body{background:#f3f6fa}.category-form-page{max-width:1100px;margin:0 auto;padding:8px 4px 35px;color:#172033}.form-page-header{margin-bottom:19px}.back-link{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:#2563a6;margin-bottom:15px}.form-page-header>span{display:block;text-transform:uppercase;color:#2563a6;letter-spacing:.11em;font-size:.68rem;font-weight:800}.form-page-header h1{font-size:2rem;font-weight:800;margin:3px 0}.form-page-header p{color:#657286;margin:0}.form-errors{display:flex;gap:12px;align-items:flex-start;border-radius:11px}.form-errors>i{font-size:1.15rem;margin-top:3px}.form-layout{display:grid;grid-template-columns:minmax(0,1.55fr) minmax(290px,.75fr);gap:18px;align-items:start}.form-card,.preview-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 5px 17px rgba(28,46,70,.045);overflow:hidden}.form-card>header{display:flex;gap:12px;padding:20px 22px 16px;border-bottom:1px solid #edf1f5}.form-card-icon{width:40px;height:40px;display:grid;place-items:center;border-radius:10px;background:#eaf2fa;color:#2563a6}.form-card h2,.preview-card h2{font-size:1.05rem;font-weight:800;margin:0}.form-card header p{font-size:.78rem;color:#657286;margin:3px 0}.form-card-body{padding:22px}.form-card label,.category-type legend{font-size:.79rem;font-weight:800}.form-card label>span{color:#c53b45}.form-card .form-control{height:42px;border-color:#d5dde7;border-radius:8px}.form-card .form-control:focus{border-color:#4a80b4;box-shadow:0 0 0 3px rgba(37,99,166,.1)}.form-card small{display:block;color:#738095;font-size:.72rem;margin-top:5px}.category-type{border:0;padding:0;margin:22px 0 18px}.category-type legend{margin-bottom:8px}.category-type{display:grid;grid-template-columns:1fr 1fr;gap:10px}.category-type legend{grid-column:1/-1}.category-type>label{margin:0;cursor:pointer}.category-type label.disabled{cursor:not-allowed}.category-type label.disabled .type-option{opacity:.5;background:#f3f4f6}.category-type input{position:absolute;opacity:0}.type-option{display:flex;align-items:center;gap:11px;border:1px solid #dce3eb;border-radius:10px;padding:13px;color:#526174;transition:.15s}.type-option>i{width:34px;height:34px;border-radius:9px;background:#eef3f8;display:grid;place-items:center;color:#55708c}.type-option>span{display:flex;flex-direction:column}.type-option small{margin:2px 0 0}.category-type input:checked+.type-option{border-color:#3976ad;background:#f1f6fb;box-shadow:0 0 0 1px #3976ad;color:#1f4f7a}.category-type input:checked+.type-option>i{background:#dfeefa;color:#2563a6}.preview-card{padding:22px;position:sticky;top:100px}.preview-kicker{text-transform:uppercase;letter-spacing:.1em;color:#2563a6;font-size:.67rem;font-weight:800}.tree-preview{margin:22px 0;padding:17px;background:#f7f9fc;border:1px solid #e2e8f0;border-radius:11px;min-height:108px}.preview-parent,.preview-child{display:flex;align-items:center;gap:9px;font-size:.82rem}.preview-parent{color:#40536a}.preview-parent i{color:#2f6b9f}.preview-child{margin:14px 0 0 28px;position:relative}.preview-child:before{content:"";position:absolute;left:-17px;top:-16px;width:11px;height:27px;border-left:1px solid #bac7d5;border-bottom:1px solid #bac7d5}.preview-child i{color:#5b7895}.tree-preview.is-root .preview-parent{display:none}.tree-preview.is-root .preview-child{margin:9px 0}.tree-preview.is-root .preview-child:before{display:none}.preview-help{display:flex;align-items:flex-start;gap:9px;color:#66758a;background:#edf4fa;padding:11px;border-radius:9px}.preview-help i{color:#2c70a8;margin-top:2px}.preview-help p{font-size:.74rem;margin:0}.form-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}.form-actions .btn{border-radius:8px;padding:9px 16px;font-weight:700}
@media(max-width:800px){.form-layout{grid-template-columns:1fr}.preview-card{position:static}.category-type{grid-template-columns:1fr}}@media(max-width:500px){.form-actions{flex-direction:column-reverse}.form-actions .btn{width:100%}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const types=[...document.querySelectorAll('input[name="tipo_visual"]')];const selector=document.getElementById('parentSelector');const parent=document.getElementById('parent_id');const name=document.getElementById('nombre_categoria');const tree=document.getElementById('treePreview');const previewParent=document.querySelector('#previewParent span');const previewChild=document.querySelector('#previewChild strong');const help=document.getElementById('previewHelp');
    const refresh=()=>{const isChild=types.find(item=>item.checked)?.value==='subcategoria';selector.style.display=isChild?'block':'none';parent.required=isChild;if(!isChild)parent.value='';tree.classList.toggle('is-root',!isChild);const option=parent.options[parent.selectedIndex];previewParent.textContent=isChild?(option?.dataset.name||'Seleccioná una categoría padre'):'Sin categoría padre';previewChild.textContent=name.value.trim()||'Nueva categoría';help.textContent=isChild?'La nueva categoría quedará anidada dentro de la categoría seleccionada.':'Las categorías principales organizan los grandes grupos documentales.'};
    types.forEach(item=>item.addEventListener('change',refresh));parent.addEventListener('change',refresh);name.addEventListener('input',refresh);refresh();
});
</script>
@endpush
