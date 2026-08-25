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
                {{ ucfirst(__('general.user')) }}
            </h4>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary search-filter-toggle" type="button"
                    data-target="filterBar-users" title="{{ ucfirst(__('general.search')) }}">
                <i class="bi bi-funnel me-1"></i>{{ ucfirst(__('general.search')) }}
            </button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle filter-toolbar-btn" type="button"
                        data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="bi bi-download me-1"></i>{{ ucfirst(__('general.download')) }}
                </button>
                <ul class="dropdown-menu shadow-sm">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" onclick="exportData('xlsx')">
                            <i class="bi bi-file-earmark-excel text-success me-2"></i>XLSX
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <button class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;"
                onclick="openUserModal()">
            <i class="bi bi-plus-lg me-1"></i>{{ ucfirst(__('general.add')) }} {{ ucfirst(__('general.user')) }}
        </button>
    </div>

    @include('components.admin.search-filter', [
        'id' => 'users',
        'filters' => [
            [
                'key' => 'name',
                'type' => 'text',
                'label' => __('sysadmin/users/index.name_label'),
                'placeholder' => __('sysadmin/users/index.filter_placeholder_name')
            ],
            [
                'key' => 'email',
                'type' => 'text',
                'label' => __('sysadmin/users/index.email_label'),
                'placeholder' => __('sysadmin/users/index.filter_placeholder_email')
            ],
            [
                'key' => 'status',
                'type' => 'select',
                'label' => __('sysadmin/users/index.status_label'),
                'placeholder' => __('sysadmin/users/index.filter_status_all'),
                'options' => [
                    '1' => __('sysadmin/users/index.active'),
                    '0' => __('sysadmin/users/index.inactive'),
                ]
            ],
            [
                'key' => 'created_at_from',
                'type' => 'date',
                'label' => __('sysadmin/users/index.filter_label_created_from'),
                // 'newRow' => true,
            ],
            [
                'key' => 'created_at_to',
                'type' => 'date',
                'label' => __('sysadmin/users/index.filter_label_created_to'),
            ],
            [
                'key' => 'updated_at_from',
                'type' => 'date',
                'label' => __('sysadmin/users/index.filter_label_updated_from'),
                // 'newRow' => true,
            ],
            [
                'key' => 'updated_at_to',
                'type' => 'date',
                'label' => __('sysadmin/users/index.filter_label_updated_to'),
            ],
        ],
    ])

    <table class="table table-hover align-middle mb-0" id="usersTable" style="width:100%">
        <thead>
            <tr class="text-uppercase small fw-bold">
                <th class="ps-3">No.</th>
                <th>{{ ucfirst(__('general.name')) }}</th>
                <th>{{ ucfirst(__('sysadmin/users/index.email_label')) }}</th>
                <th>{{ ucfirst(__('sysadmin/users/index.status_label')) }}</th>
                <th>{{ ucfirst(__('sysadmin/users/index.created_at')) }}</th>
                <th>{{ ucfirst(__('sysadmin/users/index.updated_at')) }}</th>
                <th class="text-center">{{ ucfirst(__('general.actions')) }}</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

{{-- ========== MODAL: ADD/EDIT USER ========== --}}
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary-green" id="userModalTitle">
                    <i class="bi bi-person-plus me-2"></i>{{ __('sysadmin/users/index.add_user') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="userId" name="id">
                    <div class="mb-3">
                        <label for="userName" class="form-label fw-semibold small">{{ __('sysadmin/users/index.name_label') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="userName" name="name" required
                               placeholder="{{ __('sysadmin/users/index.example_name_placeholder') }}">
                        <div class="invalid-feedback" id="userNameError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="userEmail" class="form-label fw-semibold small">{{ __('sysadmin/users/index.email_label') }} <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="userEmail" name="email" required
                               placeholder="{{ __('sysadmin/users/index.example_email_placeholder') }}">
                        <div class="invalid-feedback" id="userEmailError"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">{{ __('sysadmin/users/index.status_label') }} <span class="text-danger">*</span></label>
                        <div class="dropdown custom-filter-dropdown">
                            <button class="filter-dropdown-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-chevron-down filter-dropdown-icon"></i>
                                <span class="filter-dropdown-text" id="userIsActiveText">{{ __('sysadmin/users/index.active') }}</span>
                            </button>
                            <ul class="dropdown-menu shadow-sm">
                                <li><a class="dropdown-item" href="javascript:void(0)" data-value="1">{{ __('sysadmin/users/index.active') }}</a></li>
                                <li><a class="dropdown-item" href="javascript:void(0)" data-value="0">{{ __('sysadmin/users/index.inactive') }}</a></li>
                            </ul>
                            <input type="hidden" name="is_active" id="userIsActive" value="1">
                        </div>
                        <div class="invalid-feedback" id="userIsActiveError"></div>
                    </div>
                    <hr class="my-3">
                    <div class="user-password-fields" style="display:none;">
                        <p class="text-muted small mb-3">{{ __('sysadmin/users/index.password_hint') }}</p>
                        <div class="mb-3">
                            <label for="userPassword" class="form-label fw-semibold small">{{ __('sysadmin/users/index.password_label') }} <span class="text-danger user-password-required" style="display:none;">*</span></label>
                            <input type="password" class="form-control" id="userPassword" name="password">
                            <div class="invalid-feedback" id="userPasswordError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="userPasswordConfirmation" class="form-label fw-semibold small">{{ __('sysadmin/users/index.confirm_password_label') }} <span class="text-danger user-password-required" style="display:none;">*</span></label>
                            <input type="password" class="form-control" id="userPasswordConfirmation" name="password_confirmation">
                            <div class="invalid-feedback" id="userPasswordConfirmationError"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">{{ ucfirst(__('general.cancel')) }}</button>
                    <button type="submit" class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;" id="btnUserSubmit">
                        <i class="bi bi-check-lg me-1"></i>{{ ucfirst(__('general.save')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmUserDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color:#e67700;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ __('sysadmin/users/index.delete_warning_title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">{!! __('sysadmin/users/index.delete_warning_message') !!}</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">{{ ucfirst(__('general.cancel')) }}</button>
                <button type="button" class="btn btn-sm fw-semibold" style="background-color:#e67700;color:#fff;" id="btnDeactivateInstead">
                    <i class="bi bi-toggle-off me-1"></i>{{ __('sysadmin/users/index.deactivate_instead') }}
                </button>
                <button type="button" class="btn btn-sm btn-danger fw-semibold" id="btnDeleteAnyway">
                    <i class="bi bi-trash me-1"></i>{{ __('sysadmin/users/index.delete_anyway') }}
                </button>
            </div>
        </div>
    </div>
</div>

@include('components.admin.confirm-delete')

@push('scripts')
<script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}"></script>
<script>
$(function() {
    var routes = {
        users: '{{ route("sysadmin.users.ajax.users") }}',
        exportUsers: '{{ route("sysadmin.users.ajax.export_users") }}',
        storeUser: '{{ route("sysadmin.users.ajax.store") }}',
        updateUser: '{{ route("sysadmin.users.ajax.update", ":id") }}',
        deleteUser: '{{ route("sysadmin.users.ajax.destroy", ":id") }}',
    };

    var currentUserId = '{{ Auth::user()->ulid }}';
    var currentUserIdInt = {{ Auth::user()->id }};
    var isSuperAdmin = {{ Auth::user()->hasRole('SUPER ADMIN') ? 'true' : 'false' }};

    var lang = {
        loadingData: @json(__('sysadmin/users/index.loading_data')),
        add_user: @json(__('sysadmin/users/index.add_user')),
        edit_user: @json(__('sysadmin/users/index.edit_user')),
        no_data_user: @json(__('sysadmin/users/index.no_data_user')),
        confirmDeleteWith: @json(__('general.confirm_delete_with')),
        title_edit: @json(__('sysadmin/users/index.title_edit')),
        title_delete: @json(__('sysadmin/users/index.title_delete')),
        cannot_delete_self: @json(__('sysadmin/users/index.cannot_delete_self')),
        cannot_deactivate_self: @json(__('sysadmin/users/index.cannot_deactivate_self')),
        active: @json(__('sysadmin/users/index.active')),
        inactive: @json(__('sysadmin/users/index.inactive')),
        validationRequired: @json(__('validation.required')),
        validationEmail: @json(__('validation.email')),
        validationMinString: @json(__('validation.min.string')),
        validationConfirmed: @json(__('validation.confirmed')),
        validationMaxString: @json(__('validation.max.string')),
        attrName: @json(__('validation.attributes.name')),
        attrEmail: @json(__('validation.attributes.email')),
        attrPassword: @json(__('validation.attributes.password')),
        attrPasswordConfirmation: @json(__('validation.attributes.password_confirmation')),
        attrStatus: @json(__('sysadmin/users/index.status_label')),
        deactivate_instead: @json(__('sysadmin/users/index.deactivate_instead')),
        delete_anyway: @json(__('sysadmin/users/index.delete_anyway')),
        delete_warning_title: @json(__('sysadmin/users/index.delete_warning_title')),
    };

    // ==================== DATATABLES INIT ====================
    var usersTable = null;

    function formatDateTime(d) {
        if (!d) return '-';
        var date = new Date(d);
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
               date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    }

    function initUsersTable() {
        if (usersTable) { usersTable.ajax.reload(null, false); return; }
        usersTable = AdminDataTable.init('#usersTable', {
            ajax: {
                url: routes.users,
                dataSrc: 'data',
            },
            language: {
                loadingRecords: '<div class="spinner-border spinner-border-sm me-2" role="status"></div>{{ __("sysadmin/users/index.loading_data") }}'
            },
            columns: [
                { data: null, orderable: false, searchable: false, className: 'ps-3 text-muted', defaultContent: '' },
                { data: 'name', className: 'fw-semibold' },
                { data: 'email', render: function(d) { return '<span class="text-muted">' + escHtml(d) + '</span>'; } },
                { data: 'is_active', render: function(d) {
                    return d
                        ? '<span class="badge bg-success">' + lang.active + '</span>'
                        : '<span class="badge bg-danger">' + lang.inactive + '</span>';
                }},
                { data: 'created_at', render: function(d) { return formatDateTime(d); }},
                { data: 'updated_at', render: function(d) { return formatDateTime(d); }},
                { data: null, orderable: false, className: 'text-center', render: function(d) {
                    var editBtn = '<button class="btn btn-sm btn-outline-warning me-1" onclick="openUserModal(\'' + d.ulid + '\')" title="' + lang.title_edit + '"><i class="bi bi-pencil"></i></button>';
                    if (d.ulid === currentUserId) {
                        return editBtn + '<button class="btn btn-sm btn-outline-secondary" disabled title="' + lang.cannot_delete_self + '"><i class="bi bi-trash"></i></button>';
                    }
                    return editBtn + '<button class="btn btn-sm btn-outline-danger" onclick="deleteUser(\'' + d.ulid + '\')" title="' + lang.title_delete + '"><i class="bi bi-trash"></i></button>';
                }}
            ],
            columnDefs: [
                { width: '20px', targets: 0 }
            ],
            order: [[1, 'asc']]
        });
        usersTable.on('draw.dt', function () {
            var info = usersTable.page.info();
            usersTable.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function (cell, i) {
                cell.innerHTML = i + 1 + info.start;
            });
        });
        usersTable.settings()[0]._searchFilterId = 'users';
        AdminSearchFilter.init('#usersTable', {
            id: 'users',
            table: usersTable,
            columnMap: { name: 1, email: 2 },
            customFilters: { status: true, created_at_from: true, created_at_to: true, updated_at_from: true, updated_at_to: true },
            onStateChange: function(s) { window.filterState['users'] = s; }
        });
    }

    initUsersTable();

    // ==================== EXPORT ====================
    window.exportData = function(format) {
        var state = window.filterState['users'] || {};
        var clean = {};
        $.each(state, function(k, v) {
            if (v !== '' && v !== null && v !== undefined) clean[k] = v;
        });
        var params = $.param(clean);
        window.location.href = routes.exportUsers + (params ? '?' + params : '');
    };

    // ==================== SEARCH FILTER ====================
    AdminSearchFilter.registerCustomSearch('status', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        if (val === '1') return rowData.is_active === 1 || rowData.is_active === '1';
        if (val === '0') return rowData.is_active === 0 || rowData.is_active === '0';
        return true;
    });

    function dateFilterLogic(val, rowDate, operator) {
        if (!val || !rowDate) return true;
        var filterDate = new Date(val + 'T00:00:00');
        var dataDate = new Date(rowDate);
        if (operator === 'from') return dataDate >= filterDate;
        if (operator === 'to') return dataDate <= filterDate;
        return true;
    }

    AdminSearchFilter.registerCustomSearch('created_at_from', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        return dateFilterLogic(val, rowData.created_at, 'from');
    });

    AdminSearchFilter.registerCustomSearch('created_at_to', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        return dateFilterLogic(val, rowData.created_at, 'to');
    });

    AdminSearchFilter.registerCustomSearch('updated_at_from', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        return dateFilterLogic(val, rowData.updated_at, 'from');
    });

    AdminSearchFilter.registerCustomSearch('updated_at_to', function (val, settings, data, dataIndex) {
        var api = new $.fn.dataTable.Api(settings);
        var rowData = api.row(dataIndex).data();
        return dateFilterLogic(val, rowData.updated_at, 'to');
    });

    window.filterState = {};

    $(document).on('click', '.search-filter-toggle', function () {
        var target = $(this).data('target');
        var id = target.replace('filterBar-', '');
        AdminSearchFilter.toggle(target);
        window.filterState[id] = AdminSearchFilter.getState(id);
    });

    // ==================== USERS ====================
    function setIsActiveDropdown(val) {
        $('#userIsActive').val(val);
        var text = val === '1' ? lang.active : lang.inactive;
        $('#userIsActiveText').text(text);
        $('#userIsActive').closest('.dropdown').find('.dropdown-item').removeClass('active');
        $('#userIsActive').closest('.dropdown').find('.dropdown-item[data-value="' + val + '"]').addClass('active');
    }

    window.openUserModal = function(ulid) {
        Admin.resetModal('#userModal');
        $('#userId').val('');
        var isEdit = !!ulid;

        $('#userModalTitle').html('<i class="bi bi-' + (isEdit ? 'pencil' : 'person-plus') + ' me-2"></i>' + (isEdit ? lang.edit_user : lang.add_user));

        if (isEdit) {
            $.get(routes.users, function(res) {
                var user = res.data.find(function(u) { return u.ulid === ulid; });
                if (user) {
                    var isSelf = (user.ulid === currentUserId);
                    var canChangePassword = isSuperAdmin || isSelf;

                    $('#userId').val(ulid);
                    $('#userName').val(user.name);
                    $('#userEmail').val(user.email);
                    setIsActiveDropdown(user.is_active ? '1' : '0');

                    if (canChangePassword) {
                        $('.user-password-fields').show();
                        $('.user-password-required').show();
                    } else {
                        $('.user-password-fields').hide();
                        $('.user-password-required').hide();
                    }

                    if (isSelf) {
                        $('#userIsActive').prop('disabled', true);
                        $('#userIsActive').closest('.dropdown').find('.filter-dropdown-toggle').prop('disabled', true);
                    } else {
                        $('#userIsActive').prop('disabled', false);
                        $('#userIsActive').closest('.dropdown').find('.filter-dropdown-toggle').prop('disabled', false);
                    }
                }
            });
        } else {
            setIsActiveDropdown('1');
            $('.user-password-fields').show();
            $('.user-password-required').show();
            $('#userIsActive').prop('disabled', false);
            $('#userIsActive').closest('.dropdown').find('.filter-dropdown-toggle').prop('disabled', false);
        }

        new bootstrap.Modal('#userModal').show();
    };

    // ==================== USER FORM SUBMIT ====================
    var originalUserSubmitHtml = $('#btnUserSubmit').html();
    $('#userForm').on('submit', function(e) {
        e.preventDefault();
        var id = $('#userId').val();
        var url = id ? routes.updateUser.replace(':id', id) : routes.storeUser;
        var method = id ? 'PUT' : 'POST';

        $('#userForm').find('input[type="text"], input[type="email"]').each(function() {
            $(this).val($(this).val().trim());
        });

        var $name = $('#userName'), $email = $('#userEmail'), $password = $('#userPassword'), $confirm = $('#userPasswordConfirmation'), $isActive = $('#userIsActive');
        $name.removeClass('is-invalid'); $email.removeClass('is-invalid'); $password.removeClass('is-invalid'); $confirm.removeClass('is-invalid'); $isActive.removeClass('is-invalid');
        $('#userNameError, #userEmailError, #userPasswordError, #userIsActiveError').text('');

        var valid = true;
        if (!$name.val().trim()) {
            $name.addClass('is-invalid');
            $('#userNameError').text(lang.validationRequired.replace(':attribute', lang.attrName));
            valid = false;
        } else if ($name.val().length > 255) {
            $name.addClass('is-invalid');
            $('#userNameError').text(lang.validationMaxString.replace(':attribute', lang.attrName).replace(':max', '255'));
            valid = false;
        }
        if (!$email.val().trim()) {
            $email.addClass('is-invalid');
            $('#userEmailError').text(lang.validationRequired.replace(':attribute', lang.attrEmail));
            valid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($email.val().trim())) {
            $email.addClass('is-invalid');
            $('#userEmailError').text(lang.validationEmail.replace(':attribute', lang.attrEmail));
            valid = false;
        } else if ($email.val().length > 255) {
            $email.addClass('is-invalid');
            $('#userEmailError').text(lang.validationMaxString.replace(':attribute', lang.attrEmail).replace(':max', '255'));
            valid = false;
        }
        if (!$isActive.prop('disabled') && !$isActive.val()) {
            $isActive.addClass('is-invalid');
            $('#userIsActiveError').text(lang.validationRequired.replace(':attribute', lang.attrStatus));
            valid = false;
        }
        if (!id && !$password.val()) {
            $password.addClass('is-invalid');
            $('#userPasswordError').text(lang.validationRequired.replace(':attribute', lang.attrPassword));
            valid = false;
        }
        if ($password.val() && $password.val().length < 8) {
            $password.addClass('is-invalid');
            $('#userPasswordError').text(lang.validationMinString.replace(':attribute', lang.attrPassword).replace(':min', '8'));
            valid = false;
        }
        if ($password.val() && $password.val() !== $confirm.val()) {
            $confirm.addClass('is-invalid');
            $('#userPasswordConfirmationError').text(lang.validationConfirmed.replace(':attribute', lang.attrPassword));
            valid = false;
        }
        if (!valid) return;

        // temporarily enabled so is_active is included
        var $disabled = $('#userForm').find(':disabled');
        $disabled.prop('disabled', false);
        var $data = $(this).serialize();
        $disabled.prop('disabled', true);

        if (!id && !$password.val()) {
            // add mode: password already validated above
        } else if (id && !$password.val()) {
            $data = $data.replace(/&password=[^&]*/g, '').replace(/&password_confirmation=[^&]*/g, '');
        }

        $('#btnUserSubmit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + window.adminTranslations.saving);
        Admin.ajax(url, method, $data, function(res) {
            Admin.toast(res.message, 'success');
            bootstrap.Modal.getInstance('#userModal').hide();
            if (!res.no_change && usersTable) usersTable.ajax.reload(null, false);
        }, function(xhr) {
            $('#btnUserSubmit').prop('disabled', false).html(originalUserSubmitHtml);
        });
    });

    window.deleteUser = function(ulid) {
        var row = usersTable.row($('[onclick*="deleteUser(\'' + ulid + '\')"]').closest('tr'));
        var data = row.data();
        if (data.ulid === currentUserId) {
            Admin.toast(lang.cannot_delete_self, 'warning');
            return;
        }
        $('#confirmUserDeleteModal').data('user-ulid', ulid).data('user-name', data.name);
        new bootstrap.Modal('#confirmUserDeleteModal').show();
    };

    $('#btnDeactivateInstead').on('click', function() {
        var ulid = $('#confirmUserDeleteModal').data('user-ulid');
        bootstrap.Modal.getInstance('#confirmUserDeleteModal').hide();
        openUserModal(ulid);
    });

    $('#btnDeleteAnyway').on('click', function() {
        var ulid = $('#confirmUserDeleteModal').data('user-ulid');
        var name = $('#confirmUserDeleteModal').data('user-name');
        bootstrap.Modal.getInstance('#confirmUserDeleteModal').hide();
        var msg = lang.confirmDeleteWith.replace(':name', name);
        Admin.confirmDelete(routes.deleteUser.replace(':id', ulid), function() {
            if (usersTable) usersTable.ajax.reload(null, false);
        }, msg);
    });

    // ==================== CONFIRM DELETE ====================
    var originalDeleteHtml = $('#btnConfirmDelete').html();
    $('#btnConfirmDelete').on('click', function() {
        var $modal = $('#confirmDeleteModal');
        var url = $modal.data('delete-url');
        var callback = $modal.data('on-success');
        $('#btnConfirmDelete').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>' + window.adminTranslations.deleting);
        Admin.ajax(url, 'DELETE', {}, function(res) {
            Admin.toast(res.message, 'success');
            bootstrap.Modal.getInstance('#confirmDeleteModal').hide();
            if (callback) callback();
        }, function() {
            $('#btnConfirmDelete').prop('disabled', false).html(originalDeleteHtml);
        });
    });

    // ==================== CLEAR ERRORS ON INPUT ====================
    $('#userName').on('input', function() { $(this).removeClass('is-invalid'); $('#userNameError').text(''); });
    $('#userEmail').on('input', function() { $(this).removeClass('is-invalid'); $('#userEmailError').text(''); });
    $('#userPassword').on('input', function() { $(this).removeClass('is-invalid'); $('#userPasswordError').text(''); });
    $('#userPasswordConfirmation').on('input', function() { $(this).removeClass('is-invalid'); });

    $('#userIsActive').closest('.dropdown').on('click', '.dropdown-item', function(e) {
        e.preventDefault();
        var val = $(this).data('value');
        setIsActiveDropdown(val);
        $('#userIsActive').removeClass('is-invalid');
        $('#userIsActiveError').text('');
    });

    // ==================== RESET BUTTON STATE ON MODAL CLOSE ====================
    $('#userModal').on('hidden.bs.modal', function() {
        $('#btnUserSubmit').prop('disabled', false).html(originalUserSubmitHtml);
        $('#userIsActive').prop('disabled', false);
        $('#userIsActive').closest('.dropdown').find('.filter-dropdown-toggle').prop('disabled', false);
    });
    $('#confirmDeleteModal').on('hidden.bs.modal', function() {
        $('#btnConfirmDelete').prop('disabled', false).html(originalDeleteHtml);
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
