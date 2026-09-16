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
                {{ ucfirst(__('general.role')) }} & {{ ucfirst(__('general.permissions')) }}
            </h4>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-4" id="rolePermTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="tab-roles"
                    data-bs-target="#panel-roles" type="button" role="tab" data-tab="roles">
                <i class="bi bi-shield-lock me-1"></i>{{ ucfirst(__('general.roles')) }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="tab-permissions"
                    data-bs-target="#panel-permissions" type="button" role="tab" data-tab="permissions">
                <i class="bi bi-key me-1"></i>{{ ucfirst(__('general.permissions')) }}
            </button>
        </li>
    </ul>

    <div class="tab-content" id="rolePermTabContent">

        {{-- ===================== TAB: ROLES ===================== --}}
        <div class="tab-pane fade show active" id="panel-roles" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary search-filter-toggle" type="button"
                            data-target="filterBar-roles" title="{{ ucfirst(__('general.search')) }}">
                        <i class="bi bi-funnel me-1"></i>{{ ucfirst(__('general.search')) }}
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle filter-toolbar-btn" type="button"
                                data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                            <i class="bi bi-download me-1"></i>{{ ucfirst(__('general.download')) }}
                        </button>
                        <ul class="dropdown-menu shadow-sm">
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" onclick="exportData('roles', 'xlsx')">
                                    <i class="bi bi-file-earmark-excel text-success me-2"></i>XLSX
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;"
                        onclick="openRoleModal()">
                    <i class="bi bi-plus-lg me-1"></i>{{ ucfirst(__('general.add')) }} {{ ucfirst(__('general.role')) }}
                </button>
            </div>
            @include('components.iplat1.search-filter', [
                'id' => 'roles',
                'filters' => [
                    [
                        'key' => 'name', 
                        'type' => 'text', 
                        'label' => __('sysadmin/role-permissions/index.filter_label_name'),
                        'placeholder' => __('sysadmin/role-permissions/index.filter_placeholder_name')
                    ],
                    [
                        'key' => 'guard', 
                        'type' => 'select', 
                        'label' => __('sysadmin/role-permissions/index.filter_label_guard'),
                        'placeholder' => ucfirst(__('general.all'))
                        // options diisi oleh initRolesTable() dan populateSelect()  --> otomatis baca data unique 
                    ],
                    [
                        'key' => 'hasPermissions', 
                        'type' => 'select', 
                        'label' => __('sysadmin/role-permissions/index.filter_label_has_permissions'),
                        'placeholder' => ucfirst(__('general.yes')) . ' ' . __('general.and') . ' ' . ucfirst(__('general.no')), 
                        'options' => [
                            '1' => __('sysadmin/role-permissions/index.filter_exists'),
                            '0' => __('sysadmin/role-permissions/index.filter_no'),
                        ]
                    ],
                    [
                        'key' => 'hasUsers', 
                        'type' => 'select', 
                        'label' => ucfirst(__('general.used')),
                        'placeholder' => ucfirst(__('general.yes')) . ' ' . __('general.and') . ' ' . ucfirst(__('general.no')), 
                        'options' => [
                            '1' => __('sysadmin/role-permissions/index.filter_exists'),
                            '0' => __('sysadmin/role-permissions/index.filter_no'),
                        ]
                    ],
                    [
                        'key' => 'created_at_from',
                        'type' => 'date',
                        'label' => __('sysadmin/role-permissions/index.filter_label_created_from'),
                        'newRow' => true,
                    ],
                    [
                        'key' => 'created_at_to',
                        'type' => 'date',
                        'label' => __('sysadmin/role-permissions/index.filter_label_created_to'),
                    ],
                ],
            ])
            <table class="table table-hover align-middle mb-0" id="rolesTable" style="width:100%">
                <thead>
                    <tr class="text-uppercase small fw-bold">
                        <th class="ps-3">No.</th>
                        <th>{{ ucfirst(__('general.name')) }}</th>
                        <th>{{ ucfirst(__('general.guard')) }}</th>
                        <th>{{ ucfirst(__('general.permissions')) }}</th>
                        <th>{{ ucfirst(__('general.used_by')) }}</th>
                        <th>{{ ucfirst(__('sysadmin/role-permissions/index.created_at')) }}</th>
                        <th class="text-center">{{ ucfirst(__('general.actions')) }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        {{-- ===================== TAB: PERMISSIONS ===================== --}}
        <div class="tab-pane fade" id="panel-permissions" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary search-filter-toggle" type="button"
                            data-target="filterBar-permissions" title="{{ ucfirst(__('general.search')) }}">
                        <i class="bi bi-funnel me-1"></i>{{ ucfirst(__('general.search')) }}
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle filter-toolbar-btn" type="button"
                                data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                            <i class="bi bi-download me-1"></i>{{ ucfirst(__('general.download')) }}
                        </button>
                        <ul class="dropdown-menu shadow-sm">
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" onclick="exportData('permissions', 'xlsx')">
                                    <i class="bi bi-file-earmark-excel text-success me-2"></i>XLSX
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <button class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;"
                        onclick="openPermissionModal()">
                    <i class="bi bi-plus-lg me-1"></i>{{ ucfirst(__('general.add')) }} {{ ucwords(__('general.permission')) }}
                </button>
            </div>
            @include('components.iplat1.search-filter', [
                'id' => 'permissions',
                'maxCols' => 3,
                'filters' => [
                    [
                        'key' => 'name', 
                        'type' => 'text', 
                        'label' => __('sysadmin/role-permissions/index.filter_label_name'),
                        'placeholder' => __('sysadmin/role-permissions/index.filter_placeholder_name')
                    ],
                    [
                        'key' => 'guard', 
                        'type' => 'select', 
                        'label' => __('sysadmin/role-permissions/index.filter_label_guard'),
                        'placeholder' => ucfirst(__('general.all'))
                    ],
                    [
                        'key' => 'inUse', 
                        'type' => 'select', 
                        'label' => __('sysadmin/role-permissions/index.filter_label_in_use'),
                        'placeholder' => ucfirst(__('general.yes')) . ' ' . __('general.and') . ' ' . ucfirst(__('general.no')), 
                        'options' => [
                            '1' => __('sysadmin/role-permissions/index.filter_yes'),
                            '0' => __('sysadmin/role-permissions/index.filter_no'),
                        ]
                    ],
                    [
                        'key' => 'created_at_from',
                        'type' => 'date',
                        'label' => __('sysadmin/role-permissions/index.filter_label_created_from'),
                        'newRow' => true,
                    ],
                    [
                        'key' => 'created_at_to',
                        'type' => 'date',
                        'label' => __('sysadmin/role-permissions/index.filter_label_created_to'),
                    ],
                ],
            ])
            <table class="table table-hover align-middle mb-0" id="permissionsTable" style="width:100%">
                <thead>
                    <tr class="text-uppercase small fw-bold">
                        <th class="ps-3">No.</th>
                        <th>{{ ucfirst(__('general.name')) }}</th>
                        <th>{{ ucfirst(__('general.guard')) }}</th>
                        <th>{{ ucfirst(__('general.used_by')) }}</th>
                        <th>{{ ucfirst(__('sysadmin/role-permissions/index.created_at')) }}</th>
                        <th class="text-center">{{ ucfirst(__('general.actions')) }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- ========== MODAL: ADD/EDIT ROLE ========== --}}
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary-green" id="roleModalTitle">
                    <i class="bi bi-shield-lock me-2"></i>{{ __('sysadmin/role-permissions/index.add_role') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="roleForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="roleId" name="id">
                    <div class="mb-3">
                        <label for="roleName" class="form-label fw-semibold small">{{ __('sysadmin/role-permissions/index.name_label') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="roleName" name="name" required
                               placeholder="{{ __('sysadmin/role-permissions/index.example_role_placeholder') }}">
                        <div class="invalid-feedback" id="roleNameError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="roleGuard" class="form-label fw-semibold small">{{ __('sysadmin/role-permissions/index.guard_label') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="roleGuard" name="guard_name" value="web" required>
                        <div class="invalid-feedback" id="roleGuardError"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">{{ ucfirst(__('general.cancel')) }}</button>
                    <button type="submit" class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;" id="btnRoleSubmit">
                        <i class="bi bi-check-lg me-1"></i>{{ ucfirst(__('general.save')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========== MODAL: ADD/EDIT PERMISSION ========== --}}
<div class="modal fade" id="permissionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary-green" id="permissionModalTitle">
                    <i class="bi bi-key me-2"></i>{{ __('sysadmin/role-permissions/index.add_permission') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="permissionForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="permissionId" name="id">
                    <div class="mb-3">
                        <label for="permissionName" class="form-label fw-semibold small">{{ __('sysadmin/role-permissions/index.name_label') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="permissionName" name="name" required
                               placeholder="{{ __('sysadmin/role-permissions/index.example_permission_placeholder') }}">
                        <div class="invalid-feedback" id="permissionNameError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="permissionGuard" class="form-label fw-semibold small">{{ __('sysadmin/role-permissions/index.guard_label') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="permissionGuard" name="guard_name" value="web" required>
                        <div class="invalid-feedback" id="permissionGuardError"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">{{ ucfirst(__('general.cancel')) }}</button>
                    <button type="submit" class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;" id="btnPermSubmit">
                        <i class="bi bi-check-lg me-1"></i>{{ ucfirst(__('general.save')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ========== MODAL: ASSIGN PERMISSIONS TO ROLE ========== --}}
<div class="modal fade" id="assignPermModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary-green" id="assignPermModalTitle">
                    <i class="bi bi-shield-check me-2"></i>{{ ucfirst(__('general.assign_permissions')) }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assignRoleId">
                <div id="assignPermList" class="row" style="max-height:400px;overflow-y:auto;">
                    <div class="col-12 text-center py-3 text-muted">{{ __('sysadmin/role-permissions/index.loading_permissions') }}</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">{{ ucfirst(__('general.cancel')) }}</button>
                <button type="button" class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;"
                        id="btnAssignPermSubmit">
                    <i class="bi bi-check-lg me-1"></i>{{ ucfirst(__('general.save') )}}
                </button>
            </div>
        </div>
    </div>
</div>

@include('components.iplat1.confirm-delete')
@include('components.iplat1.badge-list')

@push('scripts')
<script>
$(function() {
    var routes = {
        roles: '{{ route("sysadmin.role_permissions.ajax.roles") }}',
        exportRoles: '{{ route("sysadmin.role_permissions.ajax.export_roles") }}',
        rolePermissions: '{{ route("sysadmin.role_permissions.ajax.role_permissions", ":id") }}',
        roleUsers: '{{ route("sysadmin.role_permissions.ajax.role_users", ":id") }}',
        permissionRoles: '{{ route("sysadmin.permissions.ajax.permission_roles", ":id") }}',
        syncPermissions: '{{ route("sysadmin.role_permissions.ajax.sync_permissions", ":id") }}',
        permissions: '{{ route("sysadmin.permissions.ajax.all") }}',
        exportPermissions: '{{ route("sysadmin.permissions.ajax.export_permissions") }}',
        storeRole: '{{ route("sysadmin.role_permissions.ajax.store") }}',
        updateRole: '{{ route("sysadmin.role_permissions.ajax.update", ":id") }}',
        deleteRole: '{{ route("sysadmin.role_permissions.ajax.destroy", ":id") }}',
        storePermission: '{{ route("sysadmin.permissions.ajax.store") }}',
        updatePermission: '{{ route("sysadmin.permissions.ajax.update", ":id") }}',
        deletePermission: '{{ route("sysadmin.permissions.ajax.destroy", ":id") }}',
    };

    var lang = {
        loadingData: @json(__('sysadmin/role-permissions/index.loading_data')),
        loadingPermissions: @json(__('sysadmin/role-permissions/index.loading_permissions')),
        loading: @json(__('sysadmin/role-permissions/index.loading')),
        addRole: @json(__('sysadmin/role-permissions/index.add_role')),
        editRole: @json(__('sysadmin/role-permissions/index.edit_role')),
        addPermission: @json(__('sysadmin/role-permissions/index.add_permission')),
        editPermission: @json(__('sysadmin/role-permissions/index.edit_permission')),
        noDataRole: @json(__('sysadmin/role-permissions/index.no_data_role')),
        noDataPermission: @json(__('sysadmin/role-permissions/index.no_data_permission')),
        roles: @json(__('general.roles')),
        noPermissionsAvailable: @json(__('sysadmin/role-permissions/index.no_permissions_available')),
        permissionCount: @json(__('sysadmin/role-permissions/index.permission_count')),
        usersCount: @json(__('general.count_users')),
        permissionsOf: @json(__('sysadmin/role-permissions/index.permissions_of')),
        usersOf: @json(__('sysadmin/role-permissions/index.users_of')),
        rolesOf: @json(__('sysadmin/role-permissions/index.roles_of')),
        noPermissions: @json(__('sysadmin/role-permissions/index.no_permissions')),
        noUsers: @json(__('sysadmin/role-permissions/index.no_users')),
        noRoles: @json(__('sysadmin/role-permissions/index.no_roles')),
        assignPermissionsTo: @json(__('sysadmin/role-permissions/index.assign_permissions_to')),
        titleEdit: @json(__('sysadmin/role-permissions/index.title_edit')),
        titleDelete: @json(__('sysadmin/role-permissions/index.title_delete')),
        warningRoleInUse: @json(__('sysadmin/role-permissions/index.warning_role_in_use')),
        warningPermissionInUse: @json(__('sysadmin/role-permissions/index.warning_permission_in_use')),
        validationRequired: @json(__('validation.required')),
        validationMaxString: @json(__('validation.max.string')),
        attrName: @json(__('validation.attributes.name')),
        attrGuardName: @json(__('validation.attributes.guard')),
    };

    // ==================== HELPERS ====================
    function formatDateTime(d) {
        if (!d) return '-';
        var date = new Date(d);
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
               date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }

    // ==================== DATATABLES INIT ====================
    var rolesTable = null;
    var permissionsTable = null;

    function initRolesTable() {
        if (rolesTable) { rolesTable.ajax.reload(null, false); return; }
        rolesTable = IplatDataTable.init('#rolesTable', {
            ajax: {
                url: routes.roles,
                dataSrc: 'data',
                complete: function () {
                    var guards = rolesTable.column(2).data().unique().sort().toArray();
                    IplatFilter.populateSelect('filterBar-roles', 'guard', guards);
                }
            },
            language: {
                loadingRecords: '<div class="spinner-border spinner-border-sm me-2" role="status"></div>{{ __("sysadmin/role-permissions/index.loading_data") }}'
            },
            columns: [
                { data: null, orderable: false, searchable: false, className: 'ps-3 text-muted', defaultContent: '' },
                { data: 'name', className: 'fw-semibold' },
                { data: 'guard_name', render: function(d) { return '<span class="badge bg-secondary">' + escHtml(d) + '</span>'; } },
                { data: 'permissions_count', render: function(d, type, row) {
                    if (d === 0) return '<span class="text-muted">-</span>';
                    var label = d !== 1 ? lang.permissionCount.split('|')[1] : lang.permissionCount.split('|')[0];
                    return '<a href="javascript:void(0)" class="badge bg-info text-dark text-decoration-none" onclick="openBadgeList(\'permissions\', \'' + row.ulid + '\', \'' + escHtml(row.name) + '\')">' + label.replace(':count', d) + '</a>';
                }},
                { data: 'users_count', render: function(d, type, row) {
                    if (d === 0) return '<span class="text-muted">-</span>';
                    var label = d !== 1 ? lang.usersCount.split('|')[1] : lang.usersCount.split('|')[0];
                    return '<a href="javascript:void(0)" class="badge bg-warning text-dark text-decoration-none" onclick="openBadgeList(\'users\', \'' + row.ulid + '\', \'' + escHtml(row.name) + '\')">' + label.replace(':count', d) + '</a>';
                }},
                { data: 'created_at', render: function(d) { return formatDateTime(d); }},
                { data: null, orderable: false, className: 'text-center', render: function(d) {
                    return '<button class="btn btn-sm btn-outline-primary me-1" onclick="openAssignPermModal(\'' + d.ulid + '\', \'' + escHtml(d.name) + '\')" title="' + lang.titleEdit + '"><i class="bi bi-shield-check"></i></button>' +
                           '<button class="btn btn-sm btn-outline-warning me-1" onclick="openRoleModal(\'' + d.ulid + '\', \'' + escHtml(d.name) + '\', \'' + escHtml(d.guard_name) + '\')" title="' + lang.titleEdit + '"><i class="bi bi-pencil"></i></button>' +
                           '<button class="btn btn-sm btn-outline-danger" onclick="deleteRole(\'' + d.ulid + '\')" title="' + lang.titleDelete + '"><i class="bi bi-trash"></i></button>';
                }}
            ],
            fixedColumns: { start: 2, end: 0 },
            columnDefs: [
                { width: '20px', targets: 0 }
            ],
            order: [[1, 'asc']]
        });
        // tulis ulang kolom "No." setiap tabel berubah
        rolesTable.on('draw.dt', function () {
            var info = rolesTable.page.info();
            rolesTable.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1 + info.start;
            });
        });
        rolesTable.settings()[0]._searchFilterId = 'roles';
        IplatFilter.init('#rolesTable', {
            id: 'roles',
            table: rolesTable,
            columnMap: { name: 1, guard: 2 },
            customFilters: { hasPermissions: true, hasUsers: true, created_at_from: true, created_at_to: true },
            onStateChange: function(s) { window.filterState['roles'] = s; }
        });
    }

    function initPermissionsTable() {
        if (permissionsTable) { permissionsTable.ajax.reload(null, false); return; }
        permissionsTable = IplatDataTable.init('#permissionsTable', {
            ajax: {
                url: routes.permissions,
                dataSrc: 'data',
                complete: function () {
                    var guards = permissionsTable.column(2).data().unique().sort().toArray();
                    IplatFilter.populateSelect('filterBar-permissions', 'guard', guards);
                }
            },
            language: {
                loadingRecords: '<div class="spinner-border spinner-border-sm me-2" role="status"></div>{{ __("sysadmin/role-permissions/index.loading_data") }}'
            },
            columns: [
                { data: null, orderable: false, searchable: false, className: 'ps-3 text-muted', defaultContent: '' },
                { data: 'name', className: 'fw-semibold' },
                { data: 'guard_name', render: function(d) { return '<span class="badge bg-secondary">' + escHtml(d) + '</span>'; } },
                { data: 'roles_count', render: function(d, type, row) {
                    if (d === 0) return '<span class="text-muted">-</span>';
                    return '<a href="javascript:void(0)" class="badge bg-warning text-dark text-decoration-none" onclick="openBadgeList(\'roles\', \'' + row.ulid + '\', \'' + escHtml(row.name) + '\')">' + d + ' ' + escHtml(lang.roles) + '</a>';
                }},
                { data: 'created_at', render: function(d) { return formatDateTime(d); }},
                { data: null, orderable: false, className: 'text-center', render: function(d) {
                    return '<button class="btn btn-sm btn-outline-warning me-1" onclick="openPermissionModal(\'' + d.ulid + '\', \'' + escHtml(d.name) + '\', \'' + escHtml(d.guard_name) + '\')" title="' + lang.titleEdit + '"><i class="bi bi-pencil"></i></button>' +
                           '<button class="btn btn-sm btn-outline-danger" onclick="deletePermission(\'' + d.ulid + '\')" title="' + lang.titleDelete + '"><i class="bi bi-trash"></i></button>';
                }}
            ],
            fixedColumns: { start: 2, end: 0 },
            columnDefs: [
                { width: '20px', targets: 0 }
            ],
            order: [[1, 'asc']]
        });
        permissionsTable.on('draw.dt', function () {
            var info = permissionsTable.page.info();
            permissionsTable.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1 + info.start;
            });
        });
        permissionsTable.settings()[0]._searchFilterId = 'permissions';
        IplatFilter.init('#permissionsTable', {
            id: 'permissions',
            table: permissionsTable,
            columnMap: { name: 1, guard: 2 },
            customFilters: { inUse: true, created_at_from: true, created_at_to: true },
            onStateChange: function(s) { window.filterState['permissions'] = s; }
        });
    }

    // Load default tab
    initRolesTable();

    // ==================== EXPORT ====================
    window.exportData = function(tab, format) {
        var state = window.filterState[tab] || {};
        var clean = {};
        $.each(state, function(k, v) {
            if (v !== '' && v !== null && v !== undefined) clean[k] = v;
        });
        var url = tab === 'roles' ? routes.exportRoles : routes.exportPermissions;
        var params = $.param(clean);
        window.location.href = url + (params ? '?' + params : '');
    };

    // ==================== SEARCH FILTER ====================
    IplatFilter.registerCustomSearch('hasPermissions', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        if (val === '1') return rowData.permissions_count > 0;
        if (val === '0') return rowData.permissions_count === 0;
        return true;
    });

    IplatFilter.registerCustomSearch('hasUsers', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        if (val === '1') return rowData.users_count > 0;
        if (val === '0') return rowData.users_count === 0;
        return true;
    });

    IplatFilter.registerCustomSearch('inUse', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        if (val === '1') return rowData.roles_count > 0;
        if (val === '0') return rowData.roles_count === 0;
        return true;
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

    window.filterState = {};

    $(document).on('click', '.search-filter-toggle', function () {
        var target = $(this).data('target');
        var id = target.replace('filterBar-', '');
        IplatFilter.toggle(target);
        window.filterState[id] = IplatFilter.getState(id);
    });

    // Tab switch manual
    $('button[data-tab]').on('click', function(e) {
        e.preventDefault();
        var target = $(this).data('bs-target');
        $('button[data-tab]').removeClass('active');
        $(this).addClass('active');
        $('.tab-pane').removeClass('show active');
        $(target).addClass('show active');
        var tab = $(this).data('tab');
        if (tab === 'roles') initRolesTable();
        else if (tab === 'permissions') initPermissionsTable();
    });

    // ==================== ROLES ====================
    window.openRoleModal = function(id, name, guard) {
        Iplat.resetModal('#roleModal');
        $('#roleId').val('');
        $('#roleModalTitle').html('<i class="bi bi-shield-lock me-2"></i>' + (id ? lang.editRole : lang.addRole));
        if (id) {
            $('#roleId').val(id);
            $('#roleName').val(name);
            $('#roleGuard').val(guard);
        }
        new bootstrap.Modal('#roleModal').show();
    };

    // ==================== ROLE FORM SUBMIT ====================
    var originalRoleSubmitHtml = $('#btnRoleSubmit').html();
    $('#roleForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#roleId').val();
        var url = id ? routes.updateRole.replace(':id', id) : routes.storeRole;
        var method = id ? 'PUT' : 'POST';

        var $name = $('#roleName'), $guard = $('#roleGuard');
        $name.removeClass('is-invalid'); $guard.removeClass('is-invalid');
        $('#roleNameError, #roleGuardError').text('');

        $name.val($name.val().trim());
        $guard.val($guard.val().trim());

        var valid = true;
        if (!$name.val()) {
            $name.addClass('is-invalid');
            $('#roleNameError').text(lang.validationRequired.replace(':attribute', lang.attrName));
            valid = false;
        } else if ($name.val().length > 30) {
            $name.addClass('is-invalid');
            $('#roleNameError').text(lang.validationMaxString.replace(':attribute', lang.attrName).replace(':max', '30'));
            valid = false;
        }
        if (!$guard.val().trim()) {
            $guard.addClass('is-invalid');
            $('#roleGuardError').text(lang.validationRequired.replace(':attribute', lang.attrGuardName));
            valid = false;
        } else if ($guard.val().length > 20) {
            $guard.addClass('is-invalid');
            $('#roleGuardError').text(lang.validationMaxString.replace(':attribute', lang.attrGuardName).replace(':max', '20'));
            valid = false;
        }
        if (!valid) return;

        $('#btnRoleSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + window.iplatTranslations.saving);
        Iplat.ajax(url, method, $(this).serialize(), function(res) {
            Iplat.toast(res.message, 'success');
            bootstrap.Modal.getInstance('#roleModal').hide();
            if (!res.no_change && rolesTable) rolesTable.ajax.reload(null, false);
        }, function(xhr) {
            $('#btnRoleSubmit').prop('disabled', false).html(originalRoleSubmitHtml);
        });
    });

    window.deleteRole = function(id) {
        var row = rolesTable.row($('[onclick*="deleteRole(\'' + id + '\')"]').closest('tr'));
        var data = row.data();
        var cb = function() { if (rolesTable) rolesTable.ajax.reload(null, false); };
        if (data && data.users_count > 0) {
            var msg = lang.warningRoleInUse.replace(':name', data.name).replace(':count', data.users_count);
            Iplat.confirmWarning(msg, routes.deleteRole.replace(':id', id), cb, data.name);
        } else {
            Iplat.confirmDelete(routes.deleteRole.replace(':id', id), cb, data.name);
        }
    };

    // ==================== PERMISSIONS ====================
    window.openPermissionModal = function(id, name, guard) {
        Iplat.resetModal('#permissionModal');
        $('#permissionId').val('');
        $('#permissionModalTitle').html('<i class="bi bi-key me-2"></i>' + (id ? lang.editPermission : lang.addPermission));
        if (id) {
            $('#permissionId').val(id);
            $('#permissionName').val(name);
            $('#permissionGuard').val(guard);
        }
        new bootstrap.Modal('#permissionModal').show();
    };

    // ==================== PERMISSION FORM SUBMIT ====================
    var originalPermSubmitHtml = $('#btnPermSubmit').html();
    $('#permissionForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#permissionId').val();
        var url = id ? routes.updatePermission.replace(':id', id) : routes.storePermission;
        var method = id ? 'PUT' : 'POST';

        var $name = $('#permissionName'), $guard = $('#permissionGuard');
        $name.removeClass('is-invalid'); $guard.removeClass('is-invalid');
        $('#permissionNameError, #permissionGuardError').text('');

        $name.val($name.val().trim());
        $guard.val($guard.val().trim());

        var valid = true;
        if (!$name.val()) {
            $name.addClass('is-invalid');
            $('#permissionNameError').text(lang.validationRequired.replace(':attribute', lang.attrName));
            valid = false;
        } else if ($name.val().length > 30) {
            $name.addClass('is-invalid');
            $('#permissionNameError').text(lang.validationMaxString.replace(':attribute', lang.attrName).replace(':max', '30'));
            valid = false;
        }
        if (!$guard.val().trim()) {
            $guard.addClass('is-invalid');
            $('#permissionGuardError').text(lang.validationRequired.replace(':attribute', lang.attrGuardName));
            valid = false;
        } else if ($guard.val().length > 20) {
            $guard.addClass('is-invalid');
            $('#permissionGuardError').text(lang.validationMaxString.replace(':attribute', lang.attrGuardName).replace(':max', '20'));
            valid = false;
        }
        if (!valid) return;

        $('#btnPermSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + window.iplatTranslations.saving);
        Iplat.ajax(url, method, $(this).serialize(), function(res) {
            Iplat.toast(res.message, 'success');
            bootstrap.Modal.getInstance('#permissionModal').hide();
            if (!res.no_change) {
                if (permissionsTable) permissionsTable.ajax.reload(null, false);
                if (rolesTable) rolesTable.ajax.reload(null, false);
            }
        }, function(xhr) {
            $('#btnPermSubmit').prop('disabled', false).html(originalPermSubmitHtml);
        });
    });

    window.deletePermission = function(id) {
        var row = permissionsTable.row($('[onclick*="deletePermission(\'' + id + '\')"]').closest('tr'));
        var data = row.data();
        var cb = function() {
            if (permissionsTable) permissionsTable.ajax.reload(null, false);
            if (rolesTable) rolesTable.ajax.reload(null, false);
        };
        if (data && data.roles_count > 0) {
            var msg = lang.warningPermissionInUse.replace(':name', data.name).replace(':count', data.roles_count);
            Iplat.confirmWarning(msg, routes.deletePermission.replace(':id', id), cb, data.name);
        } else {
            Iplat.confirmDelete(routes.deletePermission.replace(':id', id), cb, data.name);
        }
    };

    // ==================== BADGE LIST ====================
    window.openBadgeList = function(type, ulid, name) {
        var cfg = {
            permissions: { url: routes.rolePermissions.replace(':id', ulid), title: lang.permissionsOf.replace(':name', name), empty: lang.noPermissions, itemKey: 'permissions', cols: 3 },
            users: { url: routes.roleUsers.replace(':id', ulid), title: lang.usersOf.replace(':name', name), empty: lang.noUsers, itemKey: 'items', cols: 2, box: true },
            roles: { url: routes.permissionRoles.replace(':id', ulid), title: lang.rolesOf.replace(':name', name), empty: lang.noRoles, itemKey: 'items' , cols:1, box: true}
        }[type];
        Iplat.showBadgeList(cfg.url, cfg.title, cfg.empty, cfg.itemKey, cfg.cols, cfg.box);
    };

    // ==================== ASSIGN PERMISSIONS ====================
    window.openAssignPermModal = function(roleId, roleName) {
        $('#assignRoleId').val(roleId);
        $('#assignPermModalTitle').html('<i class="bi bi-shield-check me-2"></i>' + lang.assignPermissionsTo.replace(':name', '<span class="text-decoration">' + escHtml(roleName) + '</span>'));

        var $list = $('#assignPermList').html('<div class="col-12 text-center py-3"><div class="spinner-border spinner-border-sm me-2"></div>' + lang.loading + '</div>');

        $.when(
            $.get(routes.permissions),
            $.get(routes.rolePermissions.replace(':id', roleId))
        ).done(function(permRes, rolePermRes) {
            var allPerms = permRes[0].data;
            var assignedPerms = rolePermRes[0].data.permissions;
            var html = '';

            $.each(allPerms, function(i, perm) {
                var checked = assignedPerms.indexOf(perm.name) !== -1 ? 'checked' : '';
                html += '<div class="col-md-6 col-lg-4 mb-2">' +
                    '<div class="form-check">' +
                    '<input class="form-check-input assign-perm-check" type="checkbox" value="' + escHtml(perm.name) + '" id="perm_' + perm.ulid + '" ' + checked + '>' +
                    '<label class="form-check-label small" for="perm_' + perm.ulid + '">' + escHtml(perm.name) + '</label>' +
                    '</div></div>';
            });

            $list.html(html || '<div class="col-12 text-center py-3 text-muted">' + lang.noPermissionsAvailable + '</div>');
        });

        new bootstrap.Modal('#assignPermModal').show();
    };

    // ==================== ASSIGN PERMISSIONS SUBMIT ====================
    var originalAssignSubmitHtml = $('#btnAssignPermSubmit').html();
    $('#btnAssignPermSubmit').on('click', function() {
        var roleId = $('#assignRoleId').val();
        var perms = [];
        $('.assign-perm-check:checked').each(function() { perms.push($(this).val()); });

        $('#btnAssignPermSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + window.iplatTranslations.saving);
        Iplat.ajax(routes.syncPermissions.replace(':id', roleId), 'PUT', { permissions: perms }, function(res) {
            Iplat.toast(res.message, 'success');
            bootstrap.Modal.getInstance('#assignPermModal').hide();
            if (!res.no_change && rolesTable) rolesTable.ajax.reload(null, false);
        }, function() {
            $('#btnAssignPermSubmit').prop('disabled', false).html(originalAssignSubmitHtml);
        });
    });

    // ==================== CLEAR ERRORS ON INPUT ====================
    $('#roleName').on('input', function() { $(this).removeClass('is-invalid'); $('#roleNameError').text(''); });
    $('#roleGuard').on('input', function() { $(this).removeClass('is-invalid'); $('#roleGuardError').text(''); });
    $('#permissionName').on('input', function() { $(this).removeClass('is-invalid'); $('#permissionNameError').text(''); });
    $('#permissionGuard').on('input', function() { $(this).removeClass('is-invalid'); $('#permissionGuardError').text(''); });

    // ==================== RESET BUTTON STATE ON MODAL CLOSE ====================
    $('#roleModal').on('hidden.bs.modal', function() {
        $('#btnRoleSubmit').prop('disabled', false).html(originalRoleSubmitHtml);
    });
    $('#permissionModal').on('hidden.bs.modal', function() {
        $('#btnPermSubmit').prop('disabled', false).html(originalPermSubmitHtml);
    });
    $('#assignPermModal').on('hidden.bs.modal', function() {
        $('#btnAssignPermSubmit').prop('disabled', false).html(originalAssignSubmitHtml);
    });

    // ==================== HELPERS ====================
    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
});
</script>
@endpush
@endsection
