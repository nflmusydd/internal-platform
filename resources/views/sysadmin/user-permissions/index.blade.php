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
                {{ ucfirst(__('sysadmin/user-permissions/index.user_permissions')) }}
            </h4>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary search-filter-toggle" type="button"
                    data-target="filterBar-userPerm" title="{{ ucfirst(__('general.search')) }}">
                <i class="bi bi-funnel me-1"></i>{{ ucfirst(__('general.search')) }}
            </button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle filter-toolbar-btn" type="button"
                        data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bi bi-download me-1"></i>{{ ucfirst(__('general.download')) }}
                </button>
                <ul id="exportUserPerm" class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item export-btn" href="javascript:void(0)" data-format="xlsx">
                            <i class="bi bi-file-earmark-excel text-success me-2"></i>XLSX
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    @include('components.iplat1.search-filter', [
        'id' => 'userPerm',
        'filters' => [
            [
                'key' => 'name',
                'type' => 'text',
                'label' => __('sysadmin/user-permissions/index.filter_label_name'),
                'placeholder' => __('sysadmin/user-permissions/index.filter_placeholder_name')
            ],
            [
                'key' => 'email',
                'type' => 'text',
                'label' => __('sysadmin/user-permissions/index.filter_label_email'),
                'placeholder' => __('sysadmin/user-permissions/index.filter_placeholder_email')
            ],
            [
                'key' => 'status',
                'type' => 'select',
                'label' => __('sysadmin/user-permissions/index.filter_label_status'),
                'placeholder' => __('sysadmin/user-permissions/index.filter_all_status'),
                'options' => [
                    '1' => __('sysadmin/user-permissions/index.status_active'),
                    '0' => __('sysadmin/user-permissions/index.status_inactive'),
                ],
            ],
            [
                'key' => 'hasRoles',
                'type' => 'select',
                'label' => __('sysadmin/user-permissions/index.filter_label_has_roles'),
                'placeholder' => ucfirst(__('general.yes')) . ' ' . __('general.and') . ' ' . ucfirst(__('general.no')),
                'options' => [
                    '1' => ucfirst(__('general.yes')),
                    '0' => ucfirst(__('general.no')),
                ],
                'newRow' => true,
            ],
            [
                'key' => 'hasPermissions',
                'type' => 'select',
                'label' => __('sysadmin/user-permissions/index.filter_label_has_permissions'),
                'placeholder' => ucfirst(__('general.yes')) . ' ' . __('general.and') . ' ' . ucfirst(__('general.no')),
                'options' => [
                    '1' => ucfirst(__('general.yes')),
                    '0' => ucfirst(__('general.no')),
                ]
            ],
        ],
    ])

    <table class="table table-hover align-middle mb-0" id="userPermTable" style="width:100%">
        <thead>
            <tr class="text-uppercase small fw-bold">
                <th class="ps-3">No.</th>
                <th>{{ ucfirst(__('general.name')) }}</th>
                <th>{{ ucfirst(__('general.email')) }}</th>
                <th>{{ ucfirst(__('sysadmin/user-permissions/index.column_status')) }}</th>
                <th>{{ ucfirst(__('sysadmin/user-permissions/index.column_roles')) }}</th>
                <th>{{ ucfirst(__('sysadmin/user-permissions/index.column_permissions')) }}</th>
                <th class="text-center">{{ ucfirst(__('general.actions')) }}</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

{{-- ========== MODAL: ASSIGN ROLES TO USER ========== --}}
<div class="modal fade" id="assignRoleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary-green d-flex align-items-start" id="assignRoleModalTitle">
                    <i class="bi bi-person-check me-2 shrink-0"></i>
                    <span>{{ __('sysadmin/user-permissions/index.assign_roles') }}</span>
                </h5>
                <button type="button" class="btn-close align-self-start" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assignUserId">
                <div id="assignRoleList" class="row" style="max-height:400px;overflow-y:auto;">
                    <div class="col-12 text-center py-3 text-muted">{{ __('sysadmin/user-permissions/index.loading_roles') }}</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">{{ ucfirst(__('general.cancel')) }}</button>
                <button type="button" class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;"
                        id="btnAssignRoleSubmit">
                    <i class="bi bi-check-lg me-1"></i>{{ ucfirst(__('general.save')) }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ========== MODAL: ASSIGN PERMISSIONS TO USER ========== --}}
<div class="modal fade" id="assignPermModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary-green d-flex align-items-start" id="assignPermModalTitle">
                    <i class="bi bi-key me-2 shrink-0"></i>
                    <span>{{ __('sysadmin/user-permissions/index.assign_permissions') }}</span>
                </h5>
                <button type="button" class="btn-close align-self-start" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assignPermUserId">
                <div id="assignPermNote" class="alert alert-info d-none py-2 px-3 small" role="alert"></div>
                <div id="assignPermList" class="row" style="max-height:400px;overflow-y:auto;">
                    <div class="col-12 text-center py-3 text-muted">{{ __('sysadmin/user-permissions/index.loading_permissions') }}</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">{{ ucfirst(__('general.cancel')) }}</button>
                <button type="button" class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;"
                        id="btnAssignPermSubmit">
                    <i class="bi bi-check-lg me-1"></i>{{ ucfirst(__('general.save')) }}
                </button>
            </div>
        </div>
    </div>
</div>

@include('components.iplat1.confirm-delete')
@include('components.iplat1.badge-list')

@push('styles')
<style>
    .form-check-input {
        border: 1px solid #6c757d;
    }
    .role-perm-toggle {
        color: var(--primary-green) !important;
    }
    .role-perm-toggle:hover,
    .role-perm-toggle:focus {
        color: var(--primary-green-hover) !important;
    }
    .role-perm-preview {
        overflow: hidden;
    }
    .role-perm-preview ul {
        margin-bottom: 0;
    }
</style>
@endpush

@push('scripts')
<script>
$(function() {
    var routes = {
        users: '{{ route("sysadmin.user_permissions.ajax.users") }}',
        userRoles: '{{ route("sysadmin.user_permissions.ajax.user_roles", ":id") }}',
        userPermissions: '{{ route("sysadmin.user_permissions.ajax.user_permissions", ":id") }}',
        rolesForUser: '{{ route("sysadmin.user_permissions.ajax.roles_for_user", ":id") }}',
        permissionsForUser: '{{ route("sysadmin.user_permissions.ajax.permissions_for_user", ":id") }}',
        exportUsers: '{{ route("sysadmin.user_permissions.ajax.export_users") }}',
        syncRoles: '{{ route("sysadmin.user_permissions.ajax.sync_roles", ":id") }}',
        syncPermissions: '{{ route("sysadmin.user_permissions.ajax.sync_permissions", ":id") }}',
        rolePermissions: '{{ route("sysadmin.role_permissions.ajax.role_permissions", ":id") }}',
    };

    var lang = {
        loadingData: @json(__('sysadmin/user-permissions/index.loading_data')),
        loadingRoles: @json(__('sysadmin/user-permissions/index.loading_roles')),
        loadingPermissions: @json(__('sysadmin/user-permissions/index.loading_permissions')),
        noRolesAvailable: @json(__('sysadmin/user-permissions/index.no_roles_available')),
        noPermissionsAvailable: @json(__('sysadmin/user-permissions/index.no_permissions_available')),
        rolesOf: @json(__('sysadmin/user-permissions/index.roles_of')),
        permissionsOf: @json(__('sysadmin/user-permissions/index.permissions_of')),
        noRoles: @json(__('sysadmin/user-permissions/index.no_roles')),
        noPermissions: @json(__('sysadmin/user-permissions/index.no_permissions')),
        assignRoles: @json(__('sysadmin/user-permissions/index.assign_roles')),
        assignRolesTo: @json(__('sysadmin/user-permissions/index.assign_roles_to')),
        assignPermissions: @json(__('sysadmin/user-permissions/index.assign_permissions')),
        assignPermissionsTo: @json(__('sysadmin/user-permissions/index.assign_permissions_to')),
        countRoles: @json(__('general.count_roles')),
        countPermissions: @json(__('general.count_permissions')),
        active: @json(__('sysadmin/user-permissions/index.status_active')),
        inactive: @json(__('sysadmin/user-permissions/index.status_inactive')),
        assignmentMeta: @json(__('sysadmin/user-permissions/index.assignment_meta')),
        assignedUnknown: @json(__('sysadmin/user-permissions/index.assigned_unknown')),
        viewRolePermissions: @json(__('sysadmin/user-permissions/index.view_role_permissions')),
        loadingRolePermissions: @json(__('sysadmin/user-permissions/index.loading_role_permissions')),
        noRolePermissions: @json(__('sysadmin/user-permissions/index.no_role_permissions')),
        rolePermissionsLabel: @json(__('sysadmin/user-permissions/index.role_permissions_label')),
        viaRoleNote: @json(__('sysadmin/user-permissions/index.via_role_note')),
    };

    function countLabel(tpl, count) {
        var parts = tpl.split('|');
        var label = count !== 1 ? parts[1] : parts[0];
        return escHtml(label.replace(':count', count));
    }

    // "Added by :name · :date" line, escaped (names may contain HTML).
    function assignmentMeta(name, date) {
        if (!date) return '';
        return escHtml(lang.assignmentMeta
            .replace(':name', name || lang.assignedUnknown)
            .replace(':date', date));
    }

    // ==================== DATATABLES INIT ====================
    var userPermTable = null;

    function initUserPermTable() {
        if (userPermTable) { userPermTable.ajax.reload(null, false); return; }
        var useFixedColumns = window.matchMedia('(min-width: 768px)').matches;

        userPermTable = IplatDataTable.init('#userPermTable', {
            ajax: {
                url: routes.users,
                dataSrc: 'data',
            },
            language: {
                loadingRecords: '<div class="spinner-border spinner-border-sm me-2" role="status"></div>{{ __("sysadmin/user-permissions/index.loading_data") }}'
            },
            columns: [
                { data: null, orderable: false, searchable: false, className: 'ps-3 text-muted', defaultContent: '' },
                { data: 'name', className: 'fw-semibold', render: function(d) { return escHtml(d) } },
                { data: 'email', render: function(d) { return '<span class="text-muted">' + escHtml(d) + '</span>'; } },
                { data: 'is_active', render: function(d) {
                    return d
                        ? '<span class="badge bg-success">' + escHtml(lang.active) + '</span>'
                        : '<span class="badge bg-danger">' + escHtml(lang.inactive) + '</span>';
                }},
                { data: 'roles_count', render: function(d, type, row) {
                    if (!d) return '<span class="text-muted">-</span>';
                    return '<a href="javascript:void(0)" class="badge bg-info text-dark text-decoration-none badge-list-btn" data-type="roles" data-id="' + row.ulid + '" data-name="' + escHtml(row.name) + '">' + countLabel(lang.countRoles, d) + '</a>';
                }},
                { data: 'permissions_count', render: function(d, type, row) {
                    if (!d) return '<span class="text-muted">-</span>';
                    return '<a href="javascript:void(0)" class="badge bg-warning text-dark text-decoration-none badge-list-btn" data-type="permissions" data-id="' + row.ulid + '" data-name="' + escHtml(row.name) + '">' + countLabel(lang.countPermissions, d) + '</a>';
                }},
                { data: null, orderable: false, className: 'text-center', render: function(d) {
                    return '<button class="btn btn-sm btn-outline-primary me-1 assign-role-btn" data-id="' + d.ulid + '" data-name="' + escHtml(d.name) + '" title="' + escHtml(lang.assignRoles) + '"><i class="bi bi-person-check"></i></button>' +
                            '<button class="btn btn-sm btn-outline-warning assign-perm-btn" data-id="' + d.ulid + '" data-name="' + escHtml(d.name) + '" title="' + escHtml(lang.assignPermissions) + '"><i class="bi bi-key"></i></button>';
                }}
            ],
            fixedColumns: useFixedColumns ? { start: 2, end: 0 } : { start: 1, end: 0 },
            columnDefs: [
                { width: '20px', targets: 0 }
            ],
            order: [[1, 'asc']]
        });
        userPermTable.on('draw.dt', function () {
            var info = userPermTable.page.info();
            userPermTable.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1 + info.start;
            });
        });
        userPermTable.settings()[0]._searchFilterId = 'userPerm';
        IplatFilter.init('#userPermTable', {
            id: 'userPerm',
            table: userPermTable,
            columnMap: { name: 1, email: 2 },
            customFilters: { status: true, hasRoles: true, hasPermissions: true },
            onStateChange: function(s) { window.filterState['userPerm'] = s; }
        });
    }

    initUserPermTable();

    // ==================== EXPORT ====================
    window.exportData = function(format) {
        var state = window.filterState['userPerm'] || {};
        var clean = {};
        $.each(state, function(k, v) {
            if (v !== '' && v !== null && v !== undefined) clean[k] = v;
        });
        var params = $.param(clean);
        window.location.href = routes.exportUsers + (params ? '?' + params : '');
    };

    // ==================== SEARCH FILTER ====================
    IplatFilter.registerCustomSearch('status', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        if (val === '1') return rowData.is_active === 1 || rowData.is_active === '1';
        if (val === '0') return rowData.is_active === 0 || rowData.is_active === '0';
        return true;
    });

    IplatFilter.registerCustomSearch('hasRoles', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        if (val === '1') return rowData.roles_count > 0;
        if (val === '0') return rowData.roles_count === 0;
        return true;
    });

    IplatFilter.registerCustomSearch('hasPermissions', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        if (val === '1') return rowData.permissions_count > 0;
        if (val === '0') return rowData.permissions_count === 0;
        return true;
    });

    window.filterState = {};

    $(document).on('click', '.search-filter-toggle', function () {
        var target = $(this).data('target');
        var id = target.replace('filterBar-', '');
        IplatFilter.toggle(target);
        window.filterState[id] = IplatFilter.getState(id);
    });

    $('#exportUserPerm').on('click', '.export-btn', function(e) {
        e.preventDefault();
        exportData($(this).data('format'));
    });

    $('#userPermTable').on('click', '.assign-role-btn', function() {
        openAssignRoleModal($(this).data('id'), $(this).data('name'));
    });

    $('#userPermTable').on('click', '.assign-perm-btn', function() {
        openAssignPermModal($(this).data('id'), $(this).data('name'));
    });

    // ==================== BADGE LIST ====================
    $('#userPermTable').on('click', '.badge-list-btn', function() {
        var type = $(this).data('type');
        var ulid = $(this).data('id');
        var name = $(this).data('name');
        var cfg = {
            roles: { url: routes.userRoles.replace(':id', ulid), title: lang.rolesOf.replace(':name', name), empty: lang.noRoles, itemKey: 'roles' },
            permissions: { url: routes.userPermissions.replace(':id', ulid), title: lang.permissionsOf.replace(':name', name), empty: lang.noPermissions, itemKey: 'permissions' }
        }[type];
        Iplat.showBadgeList(cfg.url, cfg.title, cfg.empty, cfg.itemKey);
    });

    // ==================== ASSIGN ROLES ====================
    window.openAssignRoleModal = function(ulid, name) {
        $('#assignUserId').val(ulid);
        $('#assignRoleModalTitle').html(
            '<i class="bi bi-person-check me-2 shrink-0"></i>' + 
            '<span>' + lang.assignRolesTo.replace(':name', escHtml(name)) + '</span>'
        );

        var $list = $('#assignRoleList').html('<div class="col-12 text-center py-3"><div class="spinner-border spinner-border-sm me-2"></div>' + escHtml(lang.loadingRoles) + '</div>');

        $.get(routes.rolesForUser.replace(':id', ulid), function(res) {
            var roles = (res.data && res.data.roles) || [];
            var html = '';
            $.each(roles, function(i, role) {
                var meta = role.assigned ? assignmentMeta(role.assigned_by_name, role.assigned_at) : '';
                html += '<div class="col-md-6 col-lg-6 mb-2">' +
                    '<div class="d-flex align-items-center">' +
                    '<div class="form-check flex-grow-1">' +
                    '<input class="form-check-input assign-role-check" type="checkbox" value="' + escHtml(role.name) + '" id="role_' + escHtml(role.ulid) + '"' + (role.assigned ? ' checked' : '') + '>' +
                    '<label class="form-check-label small" for="role_' + escHtml(role.ulid) + '">' + escHtml(role.name) + '</label>' +
                    '</div>' +
                    '<button type="button" class="btn btn-link btn-sm p-0 ms-1 role-perm-toggle" data-role-id="' + escHtml(role.ulid) + '" aria-expanded="false" title="' + escHtml(lang.viewRolePermissions) + '">' +
                    '<i class="bi bi-chevron-down"></i>' +
                    '</button>' +
                    '</div>' +
                    // (meta ? '<div class="small text-muted ps-4">' + meta + '</div>' : '') +
                    '<div class="role-perm-preview d-none small text-muted ps-4" data-role-id="' + escHtml(role.ulid) + '"></div>' +
                    '</div>';
                    
            });
            $list.html(html || '<div class="col-12 text-center py-3 text-muted">' + escHtml(lang.noRolesAvailable) + '</div>');
        });

        new bootstrap.Modal('#assignRoleModal').show();
    };

    // Lazy-load a role's permissions for the inline preview inside the role modal.
    $('#assignRoleList').on('click', '.role-perm-toggle', function() {
        var $btn = $(this);
        var $preview = $('#assignRoleList .role-perm-preview[data-role-id="' + $btn.data('role-id') + '"]');
        var $icon = $btn.find('i');
        var expanded = $btn.attr('aria-expanded') === 'true';

        if (expanded) {
            $btn.attr('aria-expanded', 'false');
            $icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
            $preview.stop(true, true).slideUp(160);
            return;
        }

        $btn.attr('aria-expanded', 'true');
        $icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');

        if ($preview.data('loaded')) {
            $preview.stop(true, true).slideDown(160);
            return;
        }

        $preview.removeClass('d-none').html('<span class="spinner-border spinner-border-sm me-1"></span>' + escHtml(lang.loadingRolePermissions)).hide().slideDown(160);

        $.get(routes.rolePermissions.replace(':id', $btn.data('role-id')), function(res) {
            var perms = (res.data && res.data.permissions) || [];
            $preview.data('loaded', true);
            if (!perms.length) {
                $preview.html(escHtml(lang.noRolePermissions));
                return;
            }
            // $preview.html('<div class="fw-semibold">' + escHtml(lang.rolePermissionsLabel) + '</div>' +
            //     '<ul class="ps-3 mb-0">' + perms.map(function(p) { return '<li>' + escHtml(p) + '</li>'; }).join('') + '</ul>');
            $preview.html('<ul class="ps-3 mb-0">' + perms.map(function(p) { return '<li>' + escHtml(p) + '</li>'; }).join('') + '</ul>');
        }).fail(function() {
            $preview.html(escHtml(lang.noRolePermissions));
        });
    });

    // ==================== ASSIGN ROLES SUBMIT ====================
    var originalAssignRoleSubmitHtml = $('#btnAssignRoleSubmit').html();
    $('#btnAssignRoleSubmit').on('click', function() {
        var ulid = $('#assignUserId').val();
        var roles = [];
        $('.assign-role-check:checked').each(function() { roles.push($(this).val()); });

        $('#btnAssignRoleSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + window.iplatTranslations.saving);
        Iplat.ajax(routes.syncRoles.replace(':id', ulid), 'PUT', { roles: roles }, function(res) {
            Iplat.toast(res.message, 'success');
            bootstrap.Modal.getInstance('#assignRoleModal').hide();
            if (!res.no_change && userPermTable) userPermTable.ajax.reload(null, false);
        }, function() {
            $('#btnAssignRoleSubmit').prop('disabled', false).html(originalAssignRoleSubmitHtml);
        });
    });

    // ==================== ASSIGN PERMISSIONS ====================
    window.openAssignPermModal = function(ulid, name) {
        $('#assignPermUserId').val(ulid);
        $('#assignPermModalTitle').html(
            '<i class="bi bi-key me-2"></i>' + 
            '<span>' + lang.assignPermissionsTo.replace(':name', escHtml(name)) + '</span>'
        );
        $('#assignPermNote').addClass('d-none').text('');

        var $list = $('#assignPermList').html('<div class="col-12 text-center py-3"><div class="spinner-border spinner-border-sm me-2"></div>' + escHtml(lang.loadingPermissions) + '</div>');

        $.get(routes.permissionsForUser.replace(':id', ulid), function(res) {
            var permissions = (res.data && res.data.permissions) || [];
            var html = '';
            var hasViaRole = false;
            var disabledNames = [];
            $.each(permissions, function(i, perm) {
                var disabled = perm.via_role;
                var isChecked = perm.assigned || perm.via_role;     // aktifkan checkbox perm.via_role

                if (perm.via_role) hasViaRole = true;
                if (disabled) disabledNames.push(perm.name);
                var meta = perm.assigned ? assignmentMeta(perm.assigned_by_name, perm.assigned_at) : '';
                html += '<div class="col-md-6 col-lg-6 mb-2">' +
                    '<div class="form-check">' +
                    '<input class="form-check-input assign-perm-check" type="checkbox" value="' + escHtml(perm.name) + '" id="uperm_' + escHtml(perm.ulid) + '"' + (isChecked ? ' checked' : '') + (disabled ? ' disabled' : '') + '>' +
                    '<label class="form-check-label small' + (disabled ? ' text-muted' : '') + '" for="uperm_' + escHtml(perm.ulid) + '">' + escHtml(perm.name) + '</label>' +
                    '</div>' +
                    // (meta ? '<div class="small text-muted ps-4">' + meta + '</div>' : '') +
                    '</div>';
            });
            $list.html(html || '<div class="col-12 text-center py-3 text-muted">' + escHtml(lang.noPermissionsAvailable) + '</div>');

            $('#assignPermModal').data('disabledPermNames', disabledNames);

            var $note = $('#assignPermNote');
            if (hasViaRole) {
                $note.text(lang.viaRoleNote).removeClass('d-none');
            } else {
                $note.addClass('d-none').text('');
            }
        });

        new bootstrap.Modal('#assignPermModal').show();
    };

    // ==================== ASSIGN PERMISSIONS SUBMIT ====================
    var originalAssignPermSubmitHtml = $('#btnAssignPermSubmit').html();
    $('#btnAssignPermSubmit').on('click', function() {
        var ulid = $('#assignPermUserId').val();
        var blocked = $('#assignPermModal').data('disabledPermNames') || [];
        var permissions = [];
        $('.assign-perm-check:checked').not(':disabled').each(function() {
            var name = $(this).val();
            if (blocked.indexOf(name) === -1) permissions.push(name);
        });

        $('#btnAssignPermSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + window.iplatTranslations.saving);
        Iplat.ajax(routes.syncPermissions.replace(':id', ulid), 'PUT', { permissions: permissions }, function(res) {
            Iplat.toast(res.message, 'success');
            bootstrap.Modal.getInstance('#assignPermModal').hide();
            if (!res.no_change && userPermTable) userPermTable.ajax.reload(null, false);
        }, function() {
            $('#btnAssignPermSubmit').prop('disabled', false).html(originalAssignPermSubmitHtml);
        });
    });

    // ==================== RESET BUTTON STATE ON MODAL CLOSE ====================
    $('#assignRoleModal').on('hidden.bs.modal', function() {
        $('#btnAssignRoleSubmit').prop('disabled', false).html(originalAssignRoleSubmitHtml);
    });
    $('#assignPermModal').on('hidden.bs.modal', function() {
        $('#btnAssignPermSubmit').prop('disabled', false).html(originalAssignPermSubmitHtml);
    });
});
</script>
@endpush
@endsection
