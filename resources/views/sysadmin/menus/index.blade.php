@extends('layouts.iplat1_layout1')
@include('components.iplat1.crud')
@include('components.iplat1.datatables')

@section('app-main-content')
<div class="p-4 p-md-5">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                @include('components.iplat1.breadcrumb', ['group' => 'sysadmin', 'currentPage' => ' '])
            </nav>
            <h4 class="fw-bold text-primary-green mb-1" style="font-family:'Poppins',sans-serif;">
                {{ __('sysadmin/menus/index.title') }}
            </h4>
            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle me-1"></i>{{ __('sysadmin/menus/index.managed_note') }}
            </p>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary search-filter-toggle" type="button"
                    data-target="filterBar-menus" title="{{ ucfirst(__('general.search')) }}">
                <i class="bi bi-funnel me-1"></i>{{ ucfirst(__('general.search')) }}
            </button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle filter-toolbar-btn" type="button"
                        data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bi bi-download me-1"></i>{{ ucfirst(__('general.download')) }}
                </button>
                <ul class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" onclick="exportData()">
                            <i class="bi bi-file-earmark-excel text-success me-2"></i>XLSX
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    @include('components.iplat1.search-filter', [
        'id' => 'menus',
        'filters' => [
            [
                'key' => 'name',
                'type' => 'text',
                'label' => __('sysadmin/menus/index.filter_label_name'),
                'placeholder' => __('sysadmin/menus/index.filter_placeholder_name'),
            ],
            [
                'key' => 'parent',
                'type' => 'select',
                'label' => __('sysadmin/menus/index.filter_label_parent'),
                'placeholder' => ucfirst(__('general.all')),
            ],
            [
                'key' => 'status',
                'type' => 'select',
                'label' => __('sysadmin/menus/index.filter_label_status'),
                'placeholder' => ucfirst(__('general.all')),
                'options' => [
                    '1' => __('sysadmin/menus/index.active'),
                    '0' => __('sysadmin/menus/index.inactive'),
                ],
                'newRow' => true,
            ],
            [
                'key' => 'created_at_from',
                'type' => 'date',
                'label' => __('sysadmin/menus/index.filter_label_created_from'),
                'newRow' => true,
            ],
            [
                'key' => 'created_at_to',
                'type' => 'date',
                'label' => __('sysadmin/menus/index.filter_label_created_to'),
            ],
            [
                'key' => 'updated_at_from',
                'type' => 'date',
                'label' => __('sysadmin/menus/index.filter_label_updated_from'),
                'newRow' => true,
            ],
            [
                'key' => 'updated_at_to',
                'type' => 'date',
                'label' => __('sysadmin/menus/index.filter_label_updated_to'),
            ],
        ],
    ])

    <table class="table table-hover align-middle mb-0" id="menusTable" style="width:100%">
        <thead>
            <tr class="text-uppercase small fw-bold">
                <th class="ps-3">No.</th>
                <th>{{ ucfirst(__('general.menu')) }}</th>
                <th>{{ ucfirst(__('sysadmin/menus/index.slug_label')) }}</th>
                <th>{{ ucfirst(__('sysadmin/menus/index.parent_label')) }}</th>
                <th>{{ ucfirst(__('sysadmin/menus/index.route_label')) }}</th>
                <th>{{ ucfirst(__('sysadmin/menus/index.permission_label')) }}</th>
                <th>{{ ucfirst(__('sysadmin/menus/index.order_label')) }}</th>
                <th>{{ ucfirst(__('sysadmin/menus/index.status_label')) }}</th>
                <th>{{ ucfirst(__('sysadmin/menus/index.created_at')) }}</th>
                <th>{{ ucfirst(__('sysadmin/menus/index.updated_at')) }}</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

@include('components.iplat1.confirm-delete')

@push('scripts')
<script>
$(function() {
    var routes = {
        menus: '{{ route("sysadmin.menus.ajax.all") }}',
        exportMenus: '{{ route("sysadmin.menus.ajax.export_menus") }}',
    };

    var lang = {
        loadingData: @json(__('sysadmin/menus/index.loading_data')),
        active: @json(__('sysadmin/menus/index.active')),
        inactive: @json(__('sysadmin/menus/index.inactive')),
    };

    // ==================== HELPERS ====================
    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str == null ? '' : str));
        return div.innerHTML;
    }

    function formatDateTime(d) {
        if (!d) return '-';
        var date = new Date(d);
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
               date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }

    // ==================== DATATABLES INIT ====================
    var menusTable = null;

    function initMenusTable() {
        if (menusTable) { menusTable.ajax.reload(null, false); return; }
        menusTable = IplatDataTable.init('#menusTable', {
            ajax: {
                url: routes.menus,
                dataSrc: 'data',
                complete: function () {
                    var parents = menusTable.column(3).data().unique().sort().toArray();
                    IplatFilter.populateSelect('filterBar-menus', 'parent', parents);
                }
            },
            language: {
                loadingRecords: '<div class="spinner-border spinner-border-sm me-2" role="status"></div>{{ __("sysadmin/menus/index.loading_data") }}'
            },
            columns: [
                { data: null, orderable: false, searchable: false, className: 'ps-3 text-muted', defaultContent: '' },
                { data: null, orderable: false, render: function(d) {
                    var name = appLocale === 'id' ? (d.name_id || d.name_en) : (d.name_en || d.name_id);
                    var indent = d.depth > 0 ? 'padding-left:' + (d.depth * 22) + 'px;' : '';
                    var glyph = d.depth > 0 ? '<i class="bi bi-arrow-return-right text-muted me-1"></i>' : '';
                    var icon = d.has_children ? '<i class="bi bi-folder-fill text-primary-green me-1"></i>' : '';
                    return '<span class="d-inline-block ' + (d.depth === 0 ? 'fw-semibold' : '') + '" style="' + indent + '">' + glyph + icon + escHtml(name) + '</span>';
                }},
                { data: 'slug', render: function(d) { return '<span class="text-muted">' + escHtml(d) + '</span>'; } },
                { data: 'parent_name', render: function(d) {
                    return d ? escHtml(d) : '<span class="text-muted">-</span>';
                }},
                { data: 'route_name', render: function(d) {
                    return d ? '<code>' + escHtml(d) + '</code>' : '<span class="text-muted">-</span>';
                }},
                { data: 'permission_name', render: function(d) {
                    return d ? '<span class="badge bg-light text-dark border">' + escHtml(d) + '</span>' : '<span class="text-muted">-</span>';
                }},
                { data: 'order', render: function(d, type, row) {
                    if (row.parent_id) {
                        return '<span class="text-muted">&#8627; ' + d + '</span>';
                    }
                    return '<span class="fw-semibold">' + d + '</span>';
                }},
                { data: 'is_active', render: function(d) {
                    return d
                        ? '<span class="badge bg-success">' + lang.active + '</span>'
                        : '<span class="badge bg-secondary">' + lang.inactive + '</span>';
                }},
                { data: 'created_at', render: function(d) { return formatDateTime(d); }},
                { data: 'updated_at', render: function(d) { return formatDateTime(d); }},
            ],
            fixedColumns: { start: 2, end: 0 },
            columnDefs: [
                { width: '20px', targets: 0 }
            ],
            ordering: false
        });
        menusTable.on('draw.dt', function () {
            var info = menusTable.page.info();
            menusTable.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1 + info.start;
            });
        });
        menusTable.settings()[0]._searchFilterId = 'menus';
        IplatFilter.init('#menusTable', {
            id: 'menus',
            table: menusTable,
            columnMap: {},
            customFilters: { name: true, parent: true, status: true, created_at_from: true, created_at_to: true, updated_at_from: true, updated_at_to: true },
            onStateChange: function(s) { window.filterState['menus'] = s; }
        });
    }

    initMenusTable();

    // ==================== EXPORT ====================
    window.exportData = function() {
        var state = window.filterState['menus'] || {};
        var clean = {};
        $.each(state, function(k, v) {
            if (v !== '' && v !== null && v !== undefined) clean[k] = v;
        });
        var params = $.param(clean);
        window.location.href = routes.exportMenus + (params ? '?' + params : '');
    };

    // ==================== SEARCH FILTER ====================
    IplatFilter.registerCustomSearch('name', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        var q = val.toLowerCase();
        return String(rowData.name_en || '').toLowerCase().indexOf(q) !== -1
            || String(rowData.name_id || '').toLowerCase().indexOf(q) !== -1
            || String(rowData.slug || '').toLowerCase().indexOf(q) !== -1;
    });

    IplatFilter.registerCustomSearch('parent', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        return rowData.parent_name === val;
    });

    IplatFilter.registerCustomSearch('status', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        return String(rowData.is_active ? 1 : 0) === String(val);
    });

    function dateFilterLogic(val, rowDate, operator) {
        if (!val || !rowDate) return true;
        var parts = val.split('/');
        var filterDate = new Date(parts[2], parts[1] - 1, parts[0]);
        if (operator === 'to') filterDate.setHours(23, 59, 59, 999);
        var dataDate = new Date(rowDate);
        if (operator === 'from') return dataDate >= filterDate;
        if (operator === 'to') return dataDate <= filterDate;
        return true;
    }

    IplatFilter.registerCustomSearch('created_at_from', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        return dateFilterLogic(val, rowData.created_at, 'from');
    });

    IplatFilter.registerCustomSearch('created_at_to', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        return dateFilterLogic(val, rowData.created_at, 'to');
    });

    IplatFilter.registerCustomSearch('updated_at_from', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        return dateFilterLogic(val, rowData.updated_at, 'from');
    });

    IplatFilter.registerCustomSearch('updated_at_to', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        return dateFilterLogic(val, rowData.updated_at, 'to');
    });

    window.filterState = {};

    $(document).on('click', '.search-filter-toggle', function () {
        var target = $(this).data('target');
        var id = target.replace('filterBar-', '');
        IplatFilter.toggle(target);
        window.filterState[id] = IplatFilter.getState(id);
    });
});
</script>
@endpush
@endsection
