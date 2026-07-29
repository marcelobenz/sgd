@extends('layouts.main')

@section('heading')
<style>
    :root {
        --docs-primary: #405581;
        --docs-primary-dark: #2f4168;
        --docs-surface: #fff;
        --docs-background: #f4f6fa;
        --docs-border: #e2e7f0;
        --docs-text: #253047;
        --docs-muted: #6c778c;
        --docs-approved: #2f9e67;
        --docs-pending: #db5353;
        --docs-register: #3f7fc4;
    }

    .documents-page {
        min-height: calc(100vh - 120px);
        color: var(--docs-text);
    }

    .documents-header,
    .detail-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 24px;
    }

    .documents-title,
    .detail-title {
        margin: 0;
        font-size: 1.75rem;
        font-weight: 700;
        letter-spacing: -.025em;
    }

    .documents-subtitle,
    .detail-path {
        margin: 5px 0 0;
        color: var(--docs-muted);
    }

    .detail-path {
        margin: 0 0 5px;
        font-size: .82rem;
    }

    .documents-toolbar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        padding: 14px;
        margin-bottom: 22px;
        border: 1px solid var(--docs-border);
        border-radius: 14px;
        background: var(--docs-surface);
        box-shadow: 0 5px 18px rgba(38, 53, 82, .05);
    }

    .documents-search {
        position: relative;
        flex: 1 1 300px;
    }

    .documents-search i {
        position: absolute;
        top: 50%;
        left: 14px;
        color: #8b95a8;
        transform: translateY(-50%);
    }

    .documents-search input {
        height: 42px;
        padding-left: 40px;
        border-color: var(--docs-border);
        border-radius: 9px;
    }

    .documents-toolbar .custom-select {
        width: auto;
        min-width: 190px;
        height: 42px;
        border-color: var(--docs-border);
        border-radius: 9px;
    }

    .btn-docs-primary {
        min-height: 42px;
        padding: 9px 16px;
        border: 0;
        border-radius: 9px;
        background: var(--docs-primary);
        color: #fff;
        box-shadow: 0 4px 10px rgba(64, 85, 129, .2);
    }

    .btn-docs-primary:hover,
    .btn-docs-primary:focus {
        background: var(--docs-primary-dark);
        color: #fff;
    }

    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(285px, 1fr));
        gap: 18px;
    }

    .category-card {
        position: relative;
        display: flex;
        min-height: 250px;
        flex-direction: column;
        padding: 20px;
        overflow: hidden;
        border: 1px solid var(--docs-border);
        border-radius: 16px;
        background: var(--docs-surface);
        box-shadow: 0 8px 24px rgba(38, 53, 82, .07);
        cursor: pointer;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .category-card::before {
        position: absolute;
        top: 0;
        right: 0;
        left: 0;
        height: 4px;
        background: var(--docs-primary);
        content: "";
    }

    .category-card:hover,
    .category-card:focus {
        border-color: #bdc8dc;
        outline: none;
        box-shadow: 0 13px 30px rgba(38, 53, 82, .13);
        transform: translateY(-3px);
    }

    .category-card.subcategory-card {
        min-height: 220px;
    }

    .category-card-header {
        display: flex;
        align-items: flex-start;
        gap: 13px;
    }

    .category-icon {
        display: grid;
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        border-radius: 12px;
        background: #edf1f8;
        color: var(--docs-primary);
        font-size: 1.25rem;
        place-items: center;
    }

    .category-type {
        min-height: 18px;
        margin-bottom: 3px;
        color: var(--docs-muted);
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .category-name {
        margin: 0;
        font-size: 1.12rem;
        font-weight: 700;
        line-height: 1.3;
    }

    .category-total {
        display: flex;
        align-items: baseline;
        gap: 6px;
        margin: 22px 0 15px;
    }

    .category-total strong {
        font-size: 1.65rem;
        line-height: 1;
    }

    .category-total span {
        color: var(--docs-muted);
        font-size: .9rem;
    }

    .category-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 7px;
        margin-bottom: 16px;
    }

    .category-stat {
        padding: 9px 6px;
        border-radius: 9px;
        background: var(--docs-background);
        text-align: center;
    }

    .category-stat strong {
        display: block;
        font-size: 1rem;
    }

    .category-stat span {
        display: block;
        margin-top: 1px;
        color: var(--docs-muted);
        font-size: .7rem;
    }

    .category-stat.approved strong { color: var(--docs-approved); }
    .category-stat.pending strong { color: var(--docs-pending); }
    .category-stat.register strong { color: var(--docs-register); }

    .status-distribution {
        display: flex;
        height: 5px;
        overflow: hidden;
        border-radius: 8px;
        background: #eef1f6;
    }

    .status-distribution .approved { background: var(--docs-approved); }
    .status-distribution .pending { background: var(--docs-pending); }
    .status-distribution .register { background: var(--docs-register); }

    .category-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: auto;
        padding-top: 15px;
        color: var(--docs-muted);
        font-size: .77rem;
    }

    .category-footer .open-category {
        color: var(--docs-primary);
        font-weight: 700;
    }

    .category-detail,
    .subcategory-panel {
        display: none;
    }

    .category-detail.active,
    .subcategory-panel.active {
        display: block;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 13px;
        padding: 0;
        border: 0;
        background: transparent;
        color: var(--docs-primary);
        font-weight: 600;
    }

    .section-label {
        margin: 28px 0 14px;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .section-label:first-child {
        margin-top: 0;
    }

    .detail-summary {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .summary-pill {
        padding: 5px 9px;
        border-radius: 20px;
        background: #edf1f7;
        color: var(--docs-muted);
        font-size: .75rem;
        font-weight: 600;
    }

    .documents-list {
        display: grid;
        gap: 10px;
    }

    .document-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        align-items: center;
        gap: 18px;
        padding: 15px 17px;
        border: 1px solid var(--docs-border);
        border-radius: 12px;
        background: var(--docs-surface);
        box-shadow: 0 4px 14px rgba(38, 53, 82, .045);
        cursor: pointer;
        transition: border-color .17s ease, box-shadow .17s ease, transform .17s ease;
    }

    .document-row:hover,
    .document-row:focus {
        border-color: #bdc8dc;
        outline: none;
        box-shadow: 0 8px 20px rgba(38, 53, 82, .1);
        transform: translateX(2px);
    }

    .document-main {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 13px;
    }

    .document-icon {
        display: grid;
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: 10px;
        background: #f0f3f8;
        color: var(--docs-primary);
        place-items: center;
    }

    .document-info {
        min-width: 0;
    }

    .document-title {
        margin: 0 0 5px;
        overflow: hidden;
        font-size: .98rem;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .document-title span {
        color: var(--docs-muted);
        font-size: .78rem;
        font-weight: 600;
    }

    .document-meta {
        color: var(--docs-muted);
        font-size: .78rem;
    }

    .document-actions {
        display: flex;
        align-items: center;
        gap: 3px;
        white-space: nowrap;
    }

    .document-actions .btn {
        min-width: 37px;
        border: 1px solid #edf0f4;
        color: #40516f;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 18px;
        font-size: .72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .status-badge::before {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        content: "";
    }

    .status-approved { background: #e8f6ef; color: #247c50; }
    .status-approved::before { background: var(--docs-approved); }
    .status-pending { background: #fcecec; color: #aa3d3d; }
    .status-pending::before { background: var(--docs-pending); }
    .status-register { background: #eaf2fb; color: #326ca9; }
    .status-register::before { background: var(--docs-register); }
    .status-other { background: #f0f2f6; color: #59657a; }
    .status-other::before { background: #7a8598; }

    .empty-state {
        padding: 55px 20px;
        border: 1px dashed #cbd3e0;
        border-radius: 14px;
        background: rgba(255, 255, 255, .65);
        color: var(--docs-muted);
        text-align: center;
    }

    .empty-state.compact {
        padding: 28px 20px;
    }

    .empty-state i {
        display: block;
        margin-bottom: 10px;
        color: #a5afbf;
        font-size: 1.7rem;
    }

    @media (max-width: 767.98px) {
        .documents-header,
        .detail-heading {
            align-items: stretch;
            flex-direction: column;
        }

        .documents-header .btn-docs-primary,
        .detail-heading .btn-docs-primary,
        .documents-toolbar .custom-select {
            width: 100%;
        }

        .category-grid {
            grid-template-columns: 1fr;
        }

        .document-row {
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 10px;
        }

        .document-state {
            padding-left: 53px;
        }

        .document-actions {
            grid-column: 2;
            grid-row: 1 / span 2;
            flex-direction: column;
        }
    }
</style>
@endsection

@section('contenidoPrincipal')
<div class="container-fluid px-2 px-md-3 documents-page">
    <section id="categoriesView">
        <div class="documents-header">
            <div>
                <h1 class="documents-title">Documentos</h1>
                <p class="documents-subtitle">Explorá la documentación organizada por categorías.</p>
            </div>

            <a href="{{ route('documentos.create') }}" class="btn btn-docs-primary">
                <i class="fa-regular fa-file-lines mr-1"></i>
                Nuevo documento
            </a>
        </div>

        <div class="documents-toolbar">
            <div class="documents-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="categorySearch" class="form-control"
                    placeholder="Buscar categoría o documento..." autocomplete="off">
            </div>

            <select id="categoryFilter" class="custom-select" aria-label="Filtrar categorías">
                <option value="all">Todas las categorías</option>
                <option value="pending">Con documentos pendientes</option>
                <option value="approved">Con documentos aprobados</option>
                <option value="register">Con registros</option>
            </select>

            <select id="categoryOrder" class="custom-select" aria-label="Ordenar categorías">
                <option value="name">Ordenar por nombre</option>
                <option value="total">Mayor cantidad</option>
                <option value="updated">Última actualización</option>
            </select>
        </div>

        @if ($categoriasDocumentos->isEmpty())
            <div class="empty-state">
                <i class="fa-regular fa-folder-open"></i>
                <h5>No hay documentos cargados</h5>
                <p class="mb-0">Creá un documento para comenzar.</p>
            </div>
        @else
            <div id="categoryGrid" class="category-grid">
                @foreach ($categoriasDocumentos as $grupo)
                    @php
                        $categoria = $grupo['categoria'];
                        $textoBusqueda = $categoria->nombre_categoria . ' '
                            . $grupo['subcategorias']->pluck('categoria.nombre_categoria')->implode(' ') . ' '
                            . $grupo['documentos']->pluck('titulo')->implode(' ');
                    @endphp

                    <article class="category-card root-category-card"
                        tabindex="0"
                        role="button"
                        data-category-id="{{ $categoria->id }}"
                        data-search="{{ Illuminate\Support\Str::lower($textoBusqueda) }}"
                        data-name="{{ Illuminate\Support\Str::lower($categoria->nombre_categoria) }}"
                        data-total="{{ $grupo['total'] }}"
                        data-approved="{{ $grupo['aprobados'] }}"
                        data-pending="{{ $grupo['pendientes'] }}"
                        data-register="{{ $grupo['registros'] }}"
                        data-updated="{{ optional($grupo['ultima_modificacion'])->timestamp ?? 0 }}">
                        @include('documentos._categoria-card-contenido', [
                            'grupoTarjeta' => $grupo,
                            'tipoTarjeta' => $grupo['subcategorias']->isNotEmpty()
                                ? $grupo['subcategorias']->count() . ' subcategorías'
                                : 'Categoría',
                        ])
                    </article>
                @endforeach
            </div>

            <div id="categoryEmptySearch" class="empty-state d-none">
                <i class="fa-solid fa-magnifying-glass"></i>
                <h5>No encontramos categorías</h5>
                <p class="mb-0">Probá con otro término o filtro.</p>
            </div>
        @endif
    </section>

    @foreach ($categoriasDocumentos as $grupo)
        @php $categoria = $grupo['categoria']; @endphp

        <section id="categoryDetail-{{ $categoria->id }}" class="category-detail"
            data-category-id="{{ $categoria->id }}">
            <button type="button" class="back-link back-to-categories">
                <i class="fa-solid fa-arrow-left"></i>
                Volver a categorías
            </button>

            <div class="root-overview">
                <div class="detail-heading">
                    <div>
                        <div class="detail-path">Documentos / Categorías</div>
                        <h2 class="detail-title">{{ $categoria->nombre_categoria }}</h2>
                        <div class="detail-summary">
                            <span class="summary-pill">{{ $grupo['total'] }} documentos</span>
                            <span class="summary-pill">{{ $grupo['aprobados'] }} aprobados</span>
                            <span class="summary-pill">{{ $grupo['pendientes'] }} pendientes</span>
                            <span class="summary-pill">{{ $grupo['registros'] }} registros</span>
                        </div>
                    </div>

                    <a href="{{ route('documentos.create') }}" class="btn btn-docs-primary">
                        <i class="fa-solid fa-plus mr-1"></i>
                        Nuevo documento
                    </a>
                </div>

                @if ($grupo['subcategorias']->isNotEmpty())
                    <h3 class="section-label">Subcategorías</h3>
                    <div class="category-grid">
                        @foreach ($grupo['subcategorias'] as $subgrupo)
                            <article class="category-card subcategory-card"
                                tabindex="0"
                                role="button"
                                data-parent-id="{{ $categoria->id }}"
                                data-subcategory-id="{{ $subgrupo['categoria']->id }}">
                                @include('documentos._categoria-card-contenido', [
                                    'grupoTarjeta' => $subgrupo,
                                    'tipoTarjeta' => 'Subcategoría',
                                ])
                            </article>
                        @endforeach
                    </div>
                @endif

                @if ($grupo['documentos_directos']->isNotEmpty() || $grupo['subcategorias']->isEmpty())
                    <h3 class="section-label">
                        {{ $grupo['subcategorias']->isNotEmpty()
                            ? 'Documentos de la categoría principal'
                            : 'Documentos' }}
                    </h3>

                    <div class="documents-toolbar document-toolbar">
                        <div class="documents-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" class="form-control document-search"
                                placeholder="Buscar documento..." autocomplete="off">
                        </div>
                        <select class="custom-select document-status-filter">
                            <option value="all">Todos los estados</option>
                            <option value="approved">Aprobados</option>
                            <option value="pending">Pendientes</option>
                            <option value="register">Registros</option>
                        </select>
                    </div>

                    <div class="document-list-container">
                        @include('documentos._documentos-list', [
                            'documentosLista' => $grupo['documentos_directos'],
                            'contextoId' => 'categoria-' . $categoria->id,
                        ])
                    </div>
                @endif
            </div>

            @foreach ($grupo['subcategorias'] as $subgrupo)
                <div id="subcategoryPanel-{{ $subgrupo['categoria']->id }}"
                    class="subcategory-panel"
                    data-parent-id="{{ $categoria->id }}">
                    <button type="button" class="back-link back-to-root" data-parent-id="{{ $categoria->id }}">
                        <i class="fa-solid fa-arrow-left"></i>
                        Volver a {{ $categoria->nombre_categoria }}
                    </button>

                    <div class="detail-heading">
                        <div>
                            <div class="detail-path">
                                Documentos / {{ $categoria->nombre_categoria }}
                            </div>
                            <h2 class="detail-title">{{ $subgrupo['categoria']->nombre_categoria }}</h2>
                            <div class="detail-summary">
                                <span class="summary-pill">{{ $subgrupo['total'] }} documentos</span>
                                <span class="summary-pill">{{ $subgrupo['aprobados'] }} aprobados</span>
                                <span class="summary-pill">{{ $subgrupo['pendientes'] }} pendientes</span>
                                <span class="summary-pill">{{ $subgrupo['registros'] }} registros</span>
                            </div>
                        </div>

                        <a href="{{ route('documentos.create') }}" class="btn btn-docs-primary">
                            <i class="fa-solid fa-plus mr-1"></i>
                            Nuevo documento
                        </a>
                    </div>

                    <div class="documents-toolbar document-toolbar">
                        <div class="documents-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" class="form-control document-search"
                                placeholder="Buscar documento..." autocomplete="off">
                        </div>
                        <select class="custom-select document-status-filter">
                            <option value="all">Todos los estados</option>
                            <option value="approved">Aprobados</option>
                            <option value="pending">Pendientes</option>
                            <option value="register">Registros</option>
                        </select>
                    </div>

                    <div class="document-list-container">
                        @include('documentos._documentos-list', [
                            'documentosLista' => $subgrupo['documentos'],
                            'contextoId' => 'subcategoria-' . $subgrupo['categoria']->id,
                        ])
                    </div>
                </div>
            @endforeach
        </section>
    @endforeach
</div>
@endsection

@section('scripting')
<script>
    $(function () {
        const $categoriesView = $('#categoriesView');
        const $categoryGrid = $('#categoryGrid');
        const $categoryCards = $('.root-category-card');

        function normalized(value) {
            return (value || '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        }

        function activateByKeyboard($elements, callback) {
            $elements.on('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    callback.call(this, event);
                }
            });
        }

        function applyCategoryFilters() {
            const search = normalized($('#categorySearch').val());
            const filter = $('#categoryFilter').val();
            let visible = 0;

            $categoryCards.each(function () {
                const $card = $(this);
                const matchesSearch = normalized($card.data('search')).includes(search);
                const matchesFilter =
                    filter === 'all'
                    || (filter === 'pending' && Number($card.data('pending')) > 0)
                    || (filter === 'approved' && Number($card.data('approved')) > 0)
                    || (filter === 'register' && Number($card.data('register')) > 0);
                const show = matchesSearch && matchesFilter;

                $card.toggle(show);
                visible += show ? 1 : 0;
            });

            $('#categoryEmptySearch').toggleClass('d-none', visible > 0);
        }

        function sortCategories() {
            if (!$categoryGrid.length) {
                return;
            }

            const order = $('#categoryOrder').val();
            const cards = $categoryCards.get();

            cards.sort(function (a, b) {
                if (order === 'total') {
                    return Number($(b).data('total')) - Number($(a).data('total'));
                }

                if (order === 'updated') {
                    return Number($(b).data('updated')) - Number($(a).data('updated'));
                }

                return String($(a).data('name')).localeCompare(String($(b).data('name')), 'es');
            });

            $.each(cards, function (_, card) {
                $categoryGrid.append(card);
            });
        }

        function showCategory(categoryId, updateUrl = true) {
            $categoriesView.hide();
            $('.category-detail, .subcategory-panel').removeClass('active');
            const $detail = $('#categoryDetail-' + categoryId);
            $detail.addClass('active');
            $detail.find('.root-overview').show();
            window.scrollTo({ top: 0, behavior: 'smooth' });

            if (updateUrl && window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.set('categoria', categoryId);
                url.searchParams.delete('subcategoria');
                window.history.replaceState({}, '', url);
            }
        }

        function showCategories() {
            $('.category-detail, .subcategory-panel').removeClass('active');
            $categoriesView.show();
            window.scrollTo({ top: 0, behavior: 'smooth' });

            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('categoria');
                url.searchParams.delete('subcategoria');
                window.history.replaceState({}, '', url);
            }
        }

        function showSubcategory(parentId, subcategoryId, updateUrl = true) {
            const $detail = $('#categoryDetail-' + parentId);
            $detail.find('.root-overview').hide();
            $detail.find('.subcategory-panel').removeClass('active');
            $('#subcategoryPanel-' + subcategoryId).addClass('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });

            if (updateUrl && window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.set('categoria', parentId);
                url.searchParams.set('subcategoria', subcategoryId);
                window.history.replaceState({}, '', url);
            }
        }

        function showRootOverview(parentId) {
            const $detail = $('#categoryDetail-' + parentId);
            $detail.find('.subcategory-panel').removeClass('active');
            $detail.find('.root-overview').show();
            window.scrollTo({ top: 0, behavior: 'smooth' });

            if (window.history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('subcategoria');
                window.history.replaceState({}, '', url);
            }
        }

        function filterDocuments($container) {
            const search = normalized($container.find('.document-search').val());
            const status = $container.find('.document-status-filter').val();
            const $listContainer = $container.find('.document-list-container');
            let visible = 0;

            $listContainer.find('.document-row').each(function () {
                const $row = $(this);
                const show = normalized($row.data('search')).includes(search)
                    && (status === 'all' || $row.data('status') === status);

                $row.toggle(show);
                visible += show ? 1 : 0;
            });

            const hasDocuments = $listContainer.find('.document-row').length > 0;
            $listContainer.find('.document-filter-empty')
                .toggleClass('d-none', !hasDocuments || visible > 0);
        }

        $categoryCards.on('click', function () {
            showCategory($(this).data('category-id'));
        });
        activateByKeyboard($categoryCards, function () {
            showCategory($(this).data('category-id'));
        });

        $('.subcategory-card').on('click', function () {
            showSubcategory($(this).data('parent-id'), $(this).data('subcategory-id'));
        });
        activateByKeyboard($('.subcategory-card'), function () {
            showSubcategory($(this).data('parent-id'), $(this).data('subcategory-id'));
        });

        $('.back-to-categories').on('click', showCategories);
        $('.back-to-root').on('click', function () {
            showRootOverview($(this).data('parent-id'));
        });

        $('#categorySearch').on('input', applyCategoryFilters);
        $('#categoryFilter').on('change', applyCategoryFilters);
        $('#categoryOrder').on('change', sortCategories);

        $('.document-search').on('input', function () {
            filterDocuments($(this).closest('.root-overview, .subcategory-panel'));
        });
        $('.document-status-filter').on('change', function () {
            filterDocuments($(this).closest('.root-overview, .subcategory-panel'));
        });

        $('.document-row').on('click', function (event) {
            if ($(event.target).closest('.document-actions').length) {
                return;
            }

            window.location.href = $(this).data('detail-url');
        }).on('keydown', function (event) {
            if ((event.key === 'Enter' || event.key === ' ')
                && !$(event.target).closest('.document-actions').length) {
                event.preventDefault();
                window.location.href = $(this).data('detail-url');
            }
        });

        $('.delete-document').on('click', function () {
            const formId = $(this).data('form-id');

            Swal.fire({
                icon: 'warning',
                title: 'Eliminar documento',
                text: '¿Estás seguro de que deseas eliminar este documento?',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Eliminar',
            }).then(function (result) {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        });

        const initialUrl = new URL(window.location.href);
        const initialCategory = initialUrl.searchParams.get('categoria');
        const initialSubcategory = initialUrl.searchParams.get('subcategoria');

        if (initialCategory && document.getElementById('categoryDetail-' + initialCategory)) {
            showCategory(initialCategory, false);

            if (initialSubcategory && document.getElementById('subcategoryPanel-' + initialSubcategory)) {
                showSubcategory(initialCategory, initialSubcategory, false);
            }
        }

        sortCategories();
    });
</script>
@endsection
