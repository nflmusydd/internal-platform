@extends('layouts.green_layout')
@include('components.admin.datatables')

@section('app-main-content')
<div class="p-4 p-md-5">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                @include('components.admin.breadcrumb', ['group' => 'sysadmin', 'currentPage' => ' '])
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
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;"
                        onclick="openRoleModal()">
                    <i class="bi bi-plus-lg me-1"></i>{{ ucfirst(__('general.add')) }} {{ ucfirst(__('general.role')) }}
                </button>
            </div>
            <table class="table table-hover align-middle mb-0" id="rolesTable" style="width:100%">
                <thead>
                    <tr class="text-uppercase small fw-bold" style="background-color:var(--primary-green);color:#fff;">
                        <th class="ps-3">#</th>
                        <th>{{ ucfirst(__('general.name')) }}</th>
                        <th>{{ ucfirst(__('general.guard')) }}</th>
                        <th>{{ ucfirst(__('general.permissions')) }}</th>
                        <th class="text-center">{{ ucfirst(__('general.actions')) }}</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        {{-- ===================== TAB: PERMISSIONS ===================== --}}
        <div class="tab-pane fade" id="panel-permissions" role="tabpanel">
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;"
                        onclick="openPermissionModal()">
                    <i class="bi bi-plus-lg me-1"></i>{{ ucfirst(__('general.add')) }} {{ ucfirst(__('general.permission')) }}
                </button>
            </div>
            <table class="table table-hover align-middle mb-0" id="permissionsTable" style="width:100%">
                <thead>
                    <tr class="text-uppercase small fw-bold" style="background-color:var(--primary-green);color:#fff;">
                        <th class="ps-3">#</th>
                        <th>{{ ucfirst(__('general.name')) }}</th>
                        <th>{{ ucfirst(__('general.guard')) }}</th>
                        <th>{{ ucfirst(__('general.used_by')) }}</th>
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
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="roleGuard" class="form-label fw-semibold small">{{ __('sysadmin/role-permissions/index.guard_label') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="roleGuard" name="guard_name" value="web" required>
                        <div class="invalid-feedback"></div>
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
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="permissionGuard" class="form-label fw-semibold small">{{ __('sysadmin/role-permissions/index.guard_label') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="permissionGuard" name="guard_name" value="web" required>
                        <div class="invalid-feedback"></div>
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

@include('components.admin.confirm-delete')

@push('scripts')
<script src="{{ asset('js/admin.js') }}"></script>
<script>
$(function() {
    var routes = {
        roles: '{{ route("sysadmin.role_permissions.ajax.roles") }}',
        rolePermissions: '{{ route("sysadmin.role_permissions.ajax.role_permissions", ":id") }}',
        syncPermissions: '{{ route("sysadmin.role_permissions.ajax.sync_permissions", ":id") }}',
        permissions: '{{ route("sysadmin.permissions.ajax.all") }}',
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
        noPermissionsAvailable: @json(__('sysadmin/role-permissions/index.no_permissions_available')),
        permissionCount: @json(__('sysadmin/role-permissions/index.permission_count')),
        assignPermissionsTo: @json(__('sysadmin/role-permissions/index.assign_permissions_to')),
        titleEdit: @json(__('sysadmin/role-permissions/index.title_edit')),
        titleDelete: @json(__('sysadmin/role-permissions/index.title_delete')),
    };

    // ==================== DATATABLES INIT ====================
    var rolesTable = null;
    var permissionsTable = null;

    function initRolesTable() {
        if (rolesTable) { rolesTable.ajax.reload(null, false); return; }
        rolesTable = AdminDataTable.init('#rolesTable', {
            ajax: { url: routes.roles, dataSrc: 'data' },
            language: {
                loadingRecords: '<div class="spinner-border spinner-border-sm me-2" role="status"></div>{{ __("sysadmin/role-permissions/index.loading_data") }}'
            },
            columns: [
                { data: null, orderable: false, className: 'ps-3 text-muted', render: function(d,t,r,m) { return m.row + 1; } },
                { data: 'name', className: 'fw-semibold' },
                { data: 'guard_name', render: function(d) { return '<span class="badge bg-secondary">' + escHtml(d) + '</span>'; } },
                { data: 'permissions_count', render: function(d) {
                    var label = d !== 1 ? lang.permissionCount.split('|')[1] : lang.permissionCount.split('|')[0];
                    return '<span class="badge bg-info text-dark">' + label.replace(':count', d) + '</span>';
                }},
                { data: null, orderable: false, className: 'text-center', render: function(d) {
                    return '<button class="btn btn-sm btn-outline-primary me-1" onclick="openAssignPermModal(\'' + d.ulid + '\', \'' + escHtml(d.name) + '\')" title="' + lang.titleEdit + '"><i class="bi bi-shield-check"></i></button>' +
                           '<button class="btn btn-sm btn-outline-warning me-1" onclick="openRoleModal(\'' + d.ulid + '\', \'' + escHtml(d.name) + '\', \'' + escHtml(d.guard_name) + '\')" title="' + lang.titleEdit + '"><i class="bi bi-pencil"></i></button>' +
                           '<button class="btn btn-sm btn-outline-danger" onclick="deleteRole(\'' + d.ulid + '\')" title="' + lang.titleDelete + '"><i class="bi bi-trash"></i></button>';
                }}
            ],
            order: [[1, 'asc']]
        });
    }

    function initPermissionsTable() {
        if (permissionsTable) { permissionsTable.ajax.reload(null, false); return; }
        permissionsTable = AdminDataTable.init('#permissionsTable', {
            ajax: { url: routes.permissions, dataSrc: 'data' },
            language: {
                loadingRecords: '<div class="spinner-border spinner-border-sm me-2" role="status"></div>{{ __("sysadmin/role-permissions/index.loading_data") }}'
            },
            columns: [
                { data: null, orderable: false, className: 'ps-3 text-muted', render: function(d,t,r,m) { return m.row + 1; } },
                { data: 'name', className: 'fw-semibold' },
                { data: 'guard_name', render: function(d) { return '<span class="badge bg-secondary">' + escHtml(d) + '</span>'; } },
                { data: null, orderable: false, render: function() { return '<span class="text-muted">-</span>'; } },
                { data: null, orderable: false, className: 'text-center', render: function(d) {
                    return '<button class="btn btn-sm btn-outline-warning me-1" onclick="openPermissionModal(\'' + d.ulid + '\', \'' + escHtml(d.name) + '\', \'' + escHtml(d.guard_name) + '\')" title="' + lang.titleEdit + '"><i class="bi bi-pencil"></i></button>' +
                           '<button class="btn btn-sm btn-outline-danger" onclick="deletePermission(\'' + d.ulid + '\')" title="' + lang.titleDelete + '"><i class="bi bi-trash"></i></button>';
                }}
            ],
            order: [[1, 'asc']]
        });
    }

    // Load default tab
    initRolesTable();

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
        Admin.resetModal('#roleModal');
        $('#roleModalTitle').html('<i class="bi bi-shield-lock me-2"></i>' + (id ? lang.editRole : lang.addRole));
        if (id) {
            $('#roleId').val(id);
            $('#roleName').val(name);
            $('#roleGuard').val(guard);
        }
        new bootstrap.Modal('#roleModal').show();
    };

    $('#roleForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#roleId').val();
        var url = id ? routes.updateRole.replace(':id', id) : routes.storeRole;
        var method = id ? 'PUT' : 'POST';

        Admin.ajax(url, method, $(this).serialize(), function(res) {
            Admin.toast(res.message, 'success');
            bootstrap.Modal.getInstance('#roleModal').hide();
            if (rolesTable) rolesTable.ajax.reload(null, false);
        }, function(xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                Admin.showErrors('#roleModal', xhr.responseJSON.errors);
            }
        });
    });

    window.deleteRole = function(id) {
        Admin.confirmDelete(routes.deleteRole.replace(':id', id), function() {
            if (rolesTable) rolesTable.ajax.reload(null, false);
        });
    };

    // ==================== PERMISSIONS ====================
    window.openPermissionModal = function(id, name, guard) {
        Admin.resetModal('#permissionModal');
        $('#permissionModalTitle').html('<i class="bi bi-key me-2"></i>' + (id ? lang.editPermission : lang.addPermission));
        if (id) {
            $('#permissionId').val(id);
            $('#permissionName').val(name);
            $('#permissionGuard').val(guard);
        }
        new bootstrap.Modal('#permissionModal').show();
    };

    $('#permissionForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#permissionId').val();
        var url = id ? routes.updatePermission.replace(':id', id) : routes.storePermission;
        var method = id ? 'PUT' : 'POST';

        Admin.ajax(url, method, $(this).serialize(), function(res) {
            Admin.toast(res.message, 'success');
            bootstrap.Modal.getInstance('#permissionModal').hide();
            if (permissionsTable) permissionsTable.ajax.reload(null, false);
            if (rolesTable) rolesTable.ajax.reload(null, false);
        }, function(xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                Admin.showErrors('#permissionModal', xhr.responseJSON.errors);
            }
        });
    });

    window.deletePermission = function(id) {
        Admin.confirmDelete(routes.deletePermission.replace(':id', id), function() {
            if (permissionsTable) permissionsTable.ajax.reload(null, false);
            if (rolesTable) rolesTable.ajax.reload(null, false);
        });
    };

    // ==================== ASSIGN PERMISSIONS ====================
    window.openAssignPermModal = function(roleId, roleName) {
        $('#assignRoleId').val(roleId);
        $('#assignPermModalTitle').html('<i class="bi bi-shield-check me-2"></i>' + lang.assignPermissionsTo.replace(':name', '<span class="text-decoration-underline">' + escHtml(roleName) + '</span>'));

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

    $('#btnAssignPermSubmit').on('click', function() {
        var roleId = $('#assignRoleId').val();
        var perms = [];
        $('.assign-perm-check:checked').each(function() { perms.push($(this).val()); });

        Admin.ajax(routes.syncPermissions.replace(':id', roleId), 'PUT', { permissions: perms }, function(res) {
            Admin.toast(res.message, 'success');
            bootstrap.Modal.getInstance('#assignPermModal').hide();
            if (rolesTable) rolesTable.ajax.reload(null, false);
        });
    });

    // ==================== CONFIRM DELETE ====================
    $('#btnConfirmDelete').on('click', function() {
        var $modal = $('#confirmDeleteModal');
        var url = $modal.data('delete-url');
        var callback = $modal.data('on-success');
        Admin.ajax(url, 'DELETE', {}, function(res) {
            Admin.toast(res.message, 'success');
            bootstrap.Modal.getInstance('#confirmDeleteModal').hide();
            if (callback) callback();
        });
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
