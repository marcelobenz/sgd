@extends('layouts.main')

@section('heading', 'Categorías')

@section('contenidoPrincipal')
<div class="category-page">
    <header class="category-header">
        <div><span>Organización documental</span><h1>Categorías</h1><p>Administrá la estructura utilizada para clasificar los documentos del sistema.</p></div>
        <a href="{{ route('categorias.create') }}" class="btn btn-primary"><i class="fa-solid fa-folder-plus"></i> Nueva categoría</a>
    </header>

    <section class="category-stats">
        <div><span class="stat-icon blue"><i class="fa-regular fa-folder-open"></i></span><strong>{{ $resumen['total'] }}</strong><small>Categorías totales</small></div>
        <div><span class="stat-icon green"><i class="fa-solid fa-sitemap"></i></span><strong>{{ $resumen['principales'] }}</strong><small>Categorías principales</small></div>
        <div><span class="stat-icon violet"><i class="fa-solid fa-code-branch"></i></span><strong>{{ $resumen['subcategorias'] }}</strong><small>Subcategorías</small></div>
        <div><span class="stat-icon amber"><i class="fa-regular fa-file-lines"></i></span><strong>{{ $resumen['documentos'] }}</strong><small>Documentos clasificados</small></div>
    </section>

    <section class="category-panel">
        <div class="category-toolbar">
            <div class="category-search"><i class="fa-solid fa-magnifying-glass"></i><input id="categorySearch" type="search" placeholder="Buscar por nombre..." aria-label="Buscar categorías"></div>
            <select id="categoryFilter" class="form-control" aria-label="Filtrar categorías">
                <option value="all">Todas las categorías</option>
                <option value="roots">Sólo principales</option>
                <option value="children">Con subcategorías</option>
                <option value="documents">Con documentos</option>
                <option value="empty">Vacías</option>
            </select>
            <button id="expandAll" class="btn btn-outline-secondary" type="button"><i class="fa-solid fa-angles-down"></i> Expandir todas</button>
        </div>

        <div class="table-responsive category-table-wrap">
            <table class="table category-table mb-0">
                <thead><tr><th>Categoría</th><th class="text-center">Documentos directos</th><th class="text-center">Total del árbol</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
                <tbody id="categoryRows">
                    @forelse($arbol as $nodo)
                        @include('categorias._row', ['nodo' => $nodo, 'nivel' => 0, 'rootId' => $nodo['categoria']->id])
                    @empty
                        <tr class="empty-row"><td colspan="5"><div class="category-empty"><i class="fa-regular fa-folder-open"></i><strong>Todavía no hay categorías</strong><span>Creá la primera para comenzar a organizar los documentos.</span><a href="{{ route('categorias.create') }}" class="btn btn-primary btn-sm">Crear categoría</a></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div id="filterEmpty" class="category-empty d-none"><i class="fa-solid fa-filter-circle-xmark"></i><strong>No encontramos categorías</strong><span>Probá cambiar la búsqueda o el filtro seleccionado.</span></div>
    </section>
</div>
@endsection

@push('styles')
<style>
body{background:#f3f6fa}.category-page{max-width:1450px;margin:0 auto;padding:9px 4px 35px;color:#172033}.category-header{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:18px}.category-header>div>span{text-transform:uppercase;color:#2563a6;letter-spacing:.11em;font-size:.7rem;font-weight:800}.category-header h1{font-size:2rem;font-weight:800;margin:3px 0}.category-header p{color:#657286;margin:0}.category-header .btn{border-radius:9px;font-weight:700;padding:9px 15px}.category-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:17px}.category-stats>div{display:grid;grid-template-columns:45px auto;grid-template-rows:auto auto;column-gap:12px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;box-shadow:0 4px 14px rgba(28,46,70,.04)}.stat-icon{grid-row:1/3;width:43px;height:43px;border-radius:11px;display:grid;place-items:center;font-size:1.05rem}.stat-icon.blue{background:#e8f1fb;color:#2563a6}.stat-icon.green{background:#e8f5ef;color:#2f7d62}.stat-icon.violet{background:#f0ebfb;color:#7353b5}.stat-icon.amber{background:#fff2df;color:#ba7111}.category-stats strong{font-size:1.55rem;line-height:1}.category-stats small{color:#657286}.category-panel{background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 5px 17px rgba(28,46,70,.045);overflow:hidden}.category-toolbar{display:flex;align-items:center;gap:11px;padding:16px 18px;border-bottom:1px solid #e8edf3}.category-search{position:relative;flex:1}.category-search i{position:absolute;left:13px;top:12px;color:#8290a2}.category-search input{width:100%;height:40px;border:1px solid #d7dee8;border-radius:9px;padding:0 12px 0 38px;outline:none}.category-search input:focus{border-color:#4c83b8;box-shadow:0 0 0 3px rgba(37,99,166,.1)}.category-toolbar select{max-width:220px;height:40px;border-radius:9px}.category-toolbar .btn{height:40px;border-radius:9px;font-size:.83rem}.category-table thead th{background:#f7f9fc;color:#69778a;border:0;padding:11px 16px;text-transform:uppercase;letter-spacing:.06em;font-size:.67rem}.category-table tbody td{padding:13px 16px;border-top:1px solid #edf1f5;vertical-align:middle}.category-table tbody tr:hover{background:#fafcff}.category-name{display:flex;align-items:center;gap:10px;min-width:260px}.tree-spacer{width:calc(var(--level) * 28px);flex:0 0 calc(var(--level) * 28px);position:relative}.tree-spacer:after{content:"";position:absolute;right:9px;top:-29px;bottom:-29px;border-left:1px solid #d9e1ea}.tree-toggle,.tree-placeholder{width:28px;height:28px;flex:0 0 28px}.tree-toggle{border:0;border-radius:7px;background:#edf3f9;color:#2563a6;cursor:pointer;transition:.2s}.tree-toggle i{transition:.2s}.tree-toggle[aria-expanded="true"] i{transform:rotate(90deg)}.folder-icon{width:34px;height:34px;flex:0 0 34px;border-radius:9px;display:grid;place-items:center;background:#edf4fb;color:#2563a6}.category-name-copy{display:flex;flex-direction:column;min-width:0}.category-name-copy strong{font-size:.88rem}.category-name-copy small{color:#758397;font-size:.72rem}.number-badge{font-size:.78rem;font-weight:800;color:#334155}.tree-total{display:inline-flex;align-items:center;justify-content:center;min-width:30px;padding:3px 8px;border-radius:20px;background:#eef4fa;color:#285e91;font-size:.74rem;font-weight:800}.state-note{display:flex;flex-direction:column}.state-pill{align-self:flex-start;border-radius:20px;padding:3px 8px;font-size:.67rem;font-weight:800;background:#eaf6ef;color:#287051}.state-pill.locked{background:#fff1e0;color:#a9650e}.state-note small{color:#7b8797;font-size:.69rem;margin-top:3px}.category-actions{display:flex;justify-content:flex-end;gap:5px}.category-actions .btn{width:34px;height:34px;padding:0;display:grid;place-items:center;border-radius:8px}.category-actions .btn-add{background:#ebf5ef;color:#2f7d62}.category-actions .btn-edit{background:#edf3f9;color:#2563a6}.category-actions .btn-delete{background:#fcebed;color:#bd3e48;border:0}.category-actions .btn:disabled{opacity:.35;cursor:not-allowed}.category-empty{text-align:center;padding:55px 20px;color:#718096;display:flex;align-items:center;flex-direction:column;gap:5px}.category-empty i{font-size:2.1rem;color:#8ca1b8}.category-empty strong{color:#27364a}.category-empty .btn{margin-top:7px}.category-row[hidden]{display:none!important}
@media(max-width:991px){.category-stats{grid-template-columns:repeat(2,1fr)}.category-toolbar{flex-wrap:wrap}.category-search{flex-basis:100%}.category-toolbar select{max-width:none;flex:1}}@media(max-width:700px){.category-header{align-items:flex-start;flex-direction:column}.category-header .btn{width:100%}.category-table thead{display:none}.category-table,.category-table tbody{display:block}.category-table .category-row{display:grid;grid-template-columns:1fr auto;gap:10px;padding:13px 14px;border-top:1px solid #edf1f5}.category-table .category-row td{display:block;padding:0;border:0}.category-table .category-row td:first-child{grid-column:1/-1}.category-table .category-row td:nth-child(2):before{content:"Directos: ";color:#718096;font-size:.72rem}.category-table .category-row td:nth-child(3):before{content:"Total: ";color:#718096;font-size:.72rem}.category-table .category-row td:nth-child(4){grid-column:1}.category-table .category-row td:nth-child(5){grid-column:2;grid-row:2/4}.category-stats{gap:8px}.category-stats>div{padding:13px}.tree-spacer{width:calc(var(--level) * 16px);flex-basis:calc(var(--level) * 16px)}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const rows=[...document.querySelectorAll('.category-row')];
    const byId=new Map(rows.map(row=>[row.dataset.id,row]));
    const search=document.getElementById('categorySearch');
    const filter=document.getElementById('categoryFilter');
    const expandButton=document.getElementById('expandAll');
    const empty=document.getElementById('filterEmpty');
    let allExpanded=false;
    const descendants=id=>rows.filter(row=>row.dataset.ancestors.split(',').includes(String(id)));
    const directChildren=id=>rows.filter(row=>row.dataset.parent===String(id));
    const setExpanded=(row,expanded)=>{const button=row.querySelector('.tree-toggle');if(!button)return;button.setAttribute('aria-expanded',expanded?'true':'false');directChildren(row.dataset.id).forEach(child=>{child.hidden=!expanded;if(!expanded){descendants(child.dataset.id).forEach(item=>item.hidden=true);const nested=child.querySelector('.tree-toggle');if(nested)nested.setAttribute('aria-expanded','false')}})};
    document.querySelectorAll('.tree-toggle').forEach(button=>button.addEventListener('click',()=>{const row=button.closest('.category-row');setExpanded(row,button.getAttribute('aria-expanded')!=='true')}));
    const matchesFilter=row=>{switch(filter.value){case'roots':return row.dataset.level==='0';case'children':return Number(row.dataset.children)>0;case'documents':return Number(row.dataset.total)>0;case'empty':return Number(row.dataset.total)===0&&Number(row.dataset.children)===0;default:return true}};
    const applyFilters=()=>{const term=search.value.trim().toLocaleLowerCase('es');const filtering=term!==''||filter.value!=='all';if(!filtering){rows.forEach(row=>row.hidden=row.dataset.level!=='0');document.querySelectorAll('.tree-toggle').forEach(button=>button.setAttribute('aria-expanded','false'));empty.classList.add('d-none');return}rows.forEach(row=>row.hidden=true);const matches=rows.filter(row=>row.dataset.name.includes(term)&&matchesFilter(row));matches.forEach(row=>{row.hidden=false;row.dataset.ancestors.split(',').filter(Boolean).forEach(id=>{const ancestor=byId.get(id);if(ancestor){ancestor.hidden=false;const toggle=ancestor.querySelector('.tree-toggle');if(toggle)toggle.setAttribute('aria-expanded','true')}})});empty.classList.toggle('d-none',matches.length>0)};
    search.addEventListener('input',applyFilters);filter.addEventListener('change',applyFilters);
    expandButton.addEventListener('click',()=>{allExpanded=!allExpanded;search.value='';filter.value='all';rows.forEach(row=>row.hidden=!allExpanded&&row.dataset.level!=='0');document.querySelectorAll('.tree-toggle').forEach(button=>button.setAttribute('aria-expanded',allExpanded?'true':'false'));expandButton.innerHTML=allExpanded?'<i class="fa-solid fa-angles-up"></i> Contraer todas':'<i class="fa-solid fa-angles-down"></i> Expandir todas'});
    document.querySelectorAll('.delete-category-form').forEach(form=>form.addEventListener('submit',event=>{event.preventDefault();Swal.fire({icon:'warning',title:'Eliminar categoría',text:`Se eliminará “${form.dataset.name}”. Esta acción no se puede deshacer.`,showCancelButton:true,confirmButtonText:'Eliminar',cancelButtonText:'Cancelar',confirmButtonColor:'#c53b45'}).then(result=>{if(result.isConfirmed)form.submit()})}));
    applyFilters();
});
</script>
@endpush
