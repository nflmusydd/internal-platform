# Guide to Building a New Menu (Internal Platform — iplat1)

This document guides you through building a new page/menu in Internal Platform using the
`iplat1` component system. Core principle: **the layout only loads the base; all other
assets are loaded per-page through blade components.**

## Brief Architecture

The layout `resources/views/layouts/iplat1_layout1.blade.php` only provides:
- **CSS**: Bootstrap, Bootstrap Icons, Poppins, `css/iplat1_base.css` (modal form
  styling, disabled buttons, draggable modal), and the `@stack('styles')` slot.
- **JS**: jQuery, Bootstrap bundle, `js/iplat1_modal.js` (draggable modal), layout
  sidebar/topbar logic, and the `@stack('scripts')` slot.

Everything else is pushed into `@stack('styles')` / `@stack('scripts')` through the
components in `resources/views/components/iplat1/`.

### Asset ownership

| Need | Include | Provides |
|---|---|---|
| AJAX / toast / CRUD / confirmation | `components.iplat1.crud` | `window.appLocale`, `window.iplatTranslations`, `Iplat.*` (`js/iplat1_crud.js`) |
| Tables + filters + date inputs | `components.iplat1.datatables` | `window.dtLangDefaults`, DataTables (+ FixedColumns), `IplatDataTable`, `IplatFilter`, flatpickr + theme |
| Dynamic breadcrumb (from `menus` table) | `components.iplat1.breadcrumb` | `group` param (parent menu slug) & `currentPage` |
| Filter bar | `components.iplat1.search-filter` | filter input grid `text` / `select` / `date` |
| Delete & warning confirmation modals | `components.iplat1.confirm-delete` | `#confirmDeleteModal`, `#confirmWarningModal` |
| Empty state | `components.iplat1.empty-state` | empty `<tr>` row for tables |
| Static toast container (optional) | `components.iplat1.toast` | `<div id="toast-container">` |

> `crud` must be included **before** `datatables` when both are used (the `@push` order
> determines their position in the `@stack`).

### Asset files

| File | Contents |
|---|---|
| `public/js/iplat1_crud.js` | `Iplat` namespace (ajax, toast, modal, confirmations) |
| `public/js/iplat1_datatable.js` | `IplatDataTable.init()` + DataTables defaults |
| `public/js/iplat1_filter.js` | `IplatFilter` (toggle, init, state, populate, custom search) |
| `public/js/iplat1_flatpickr.js` | Auto-init flatpickr for `.filter-date` |
| `public/js/iplat1_modal.js` | Draggable modal (base, loaded by layout) |
| `public/css/iplat1_base.css` | Modal form + disabled buttons + draggable cursor (loaded by layout) |
| `public/css/iplat1_datatable.css` | DataTables styling |
| `public/css/iplat1_filter.css` | Toolbar, filter bar, dropdown, export button |
| `public/css/iplat1_flatpickr.css` | Green flatpickr theme + custom month dropdown |

Naming convention: `iplat{version}_{function}.{ext}`, components under `components/iplat{version}/`,
layout `iplat{version}_layout{n}.blade.php`. When a new version ships, bump the version number
(`iplat2_*`).

---

## 1. Page Scaffold

```blade
@extends('layouts.iplat1_layout1')
@include('components.iplat1.crud')        {{-- only when using Iplat.* --}}
@include('components.iplat1.datatables')  {{-- only when using tables/filters/date --}}

@section('app-main-content')
<div class="p-4 p-md-5">

    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                @include('components.iplat1.breadcrumb', ['group' => 'sysadmin', 'currentPage' => ''])
            </nav>
            <h4 class="fw-bold text-primary-green mb-1" style="font-family:'Poppins',sans-serif;">
                {{ ucfirst(__('general.user')) }}
            </h4>
        </div>
    </div>

    {{-- page content --}}

</div>
@endsection

@push('scripts')
<script>
$(function() {
    var routes = {
        list:    '{{ route('sysadmin.users.ajax.users') }}',
        store:   '{{ route('sysadmin.users.ajax.store') }}',
        update:  '{{ route('sysadmin.users.ajax.update', ':id') }}',
        destroy: '{{ route('sysadmin.users.ajax.destroy', ':id') }}',
    };
    // page logic
});
</script>
@endpush
```

> `:id` in named routes is a **ULID**, not the numeric id.

---

## 2. Buttons

Pre-styled button patterns:

```blade
{{-- Primary (green) --}}
<button class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;">
    <i class="bi bi-plus-lg me-1"></i>{{ ucfirst(__('general.add')) }}
</button>

{{-- Filter bar toggle --}}
<button class="btn btn-sm btn-outline-secondary search-filter-toggle" type="button"
        data-target="filterBar-users" title="{{ ucfirst(__('general.search')) }}">
    <i class="bi bi-funnel me-1"></i>{{ ucfirst(__('general.search')) }}
</button>

{{-- Export button (dropdown) --}}
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

{{-- Table row actions --}}
<button class="btn btn-sm btn-outline-warning me-1" title="{{ ucfirst(__('general.edit')) }}">
    <i class="bi bi-pencil"></i>
</button>
<button class="btn btn-sm btn-outline-danger" title="{{ ucfirst(__('general.delete')) }}">
    <i class="bi bi-trash"></i>
</button>
```

While submitting: disable the button, show a spinner, then restore the original HTML:

```js
var originalHtml = $('#btnSubmit').html();
$('#btnSubmit').prop('disabled', true)
    .html('<span class="spinner-border spinner-border-sm me-1"></span>' + window.iplatTranslations.saving);
```

---

## 3. Modal & Form Input

CRUD modal pattern (example taken from the `users` menu):

```blade
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary-green" id="userModalTitle">
                    <i class="bi bi-person-plus me-2"></i>{{ __('...add_user') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="userId" name="id">
                    <div class="mb-3">
                        <label for="userName" class="form-label fw-semibold small">... <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="userName" name="name" required>
                        <div class="invalid-feedback" id="userNameError"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">{{ ucfirst(__('general.cancel')) }}</button>
                    <button type="submit" class="btn btn-sm fw-semibold"
                            style="background-color:var(--primary-green);color:#fff;" id="btnUserSubmit">
                        <i class="bi bi-check-lg me-1"></i>{{ ucfirst(__('general.save')) }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

**Rules & JS:**
- Every field carries a `<div class="invalid-feedback" id="{field}Error">` for validation messages.
- Open the modal: `new bootstrap.Modal('#userModal').show()`.
- Reset the state before opening: `Iplat.resetModal('#userModal')` (clears the form, removes `is-invalid`).
- **Trim all inputs except password** before sending.
- Submit through `Iplat.ajax(url, method, $data, onSuccess, onError)`; on success
  `Iplat.toast(res.message, 'success')` + `bootstrap.Modal.getInstance(...).hide()` + reload the table.
- Backend validation (`422`): `Iplat.showErrors('#userModal', errors)` fills each `#fieldError`.
  When there is no error handler, `Iplat.ajax` automatically shows the first message as a toast.
- The modal can be dragged by its header (`.modal-header { cursor: move }` from `iplat1_base.css`).
- Restore the submit button on `hidden.bs.modal` so it never stays disabled.

---

## 4. DataTables

Table markup:

```blade
<table class="table table-hover align-middle mb-0" id="usersTable" style="width:100%">
    <thead>
        <tr class="text-uppercase small fw-bold">
            <th class="ps-3">No.</th>
            <th>{{ ucfirst(__('general.name')) }}</th>
            <th class="text-center">{{ ucfirst(__('general.actions')) }}</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>
```

Initialization:

```js
var table = IplatDataTable.init('#usersTable', {
    ajax: { url: routes.list, dataSrc: 'data' },
    columns: [
        { data: null, orderable: false, searchable: false, className: 'ps-3 text-muted', defaultContent: '' },
        { data: 'name', className: 'fw-semibold' },
        { data: null, orderable: false, className: 'text-center', render: function(d) { /* actions + escHtml */ } }
    ],
    order: [[1, 'asc']]
});

// Sequential row numbering
table.on('draw.dt', function () {
    var info = table.page.info();
    table.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function (cell, i) {
        cell.innerHTML = i + 1 + info.start;
    });
});

// REQUIRED for IplatFilter
table.settings()[0]._searchFilterId = 'users';
```

**Defaults already set by `IplatDataTable`:** `scrollX`, `fixedColumns: { right: 1 }`,
`pageLength` 10, `lengthMenu [10,25,50,100]`, language from `window.dtLangDefaults`,
`info`, and pagination icons (bootstrap-icons chevrons).

Tips:
- Always escape data rendered into a column: `escHtml()` (write a local helper).
- Actions column: wired via `onclick="openModal('ulid')"` / `onclick="deleteRow('ulid')"`.

---

## 5. Search Filter

### Markup (`search-filter` component)

Filter types: `text`, `select`, `date`.

```blade
@include('components.iplat1.search-filter', [
    'id' => 'users',
    'maxCols' => 2,            // columns per row
    'filters' => [
        ['key' => 'name', 'type' => 'text',  'label' => __('...'), 'placeholder' => __('...')],
        ['key' => 'status', 'type' => 'select',
         'label' => __('...'),
         'placeholder' => ucfirst(__('general.all')),
         'options' => ['1' => __('...active'), '0' => __('...inactive')]],
        ['key' => 'created_at_from', 'type' => 'date', 'label' => __('...'), 'newRow' => true],
        ['key' => 'created_at_to',   'type' => 'date', 'label' => __('...')],
    ],
])
```

> `newRow` moves the next filter to a new row. `date` inputs automatically become
> Flatpickr (locale follows `window.appLocale`, with a localized custom month dropdown).
> `*_from` / `*_to` pairs are automatically linked with `min`/`max` (rule
> `endsWith('_from'/'_to')`).

### JS Initialization

```js
IplatFilter.init('#usersTable', {
    id: 'users',
    table: usersTable,
    columnMap: { name: 1, email: 2 },                       // columns to search
    customFilters: { status: true, created_at_from: true, created_at_to: true },
    onStateChange: function(s) { window.filterState['users'] = s; }
});
```

- `customFilters` is for keys that are **not** direct columns (handled by `registerCustomSearch`).
- `window.filterState[id]` stores the current filter values (used by export).

### Non-column filters

```js
IplatFilter.registerCustomSearch('status', function (val, settings, data, dataIndex) {
    var api = new $.fn.dataTable.Api(settings);
    var rowData = api.row(dataIndex).data();
    if (val === '1') return rowData.is_active === 1 || rowData.is_active === '1';
    if (val === '0') return rowData.is_active === 0 || rowData.is_active === '0';
    return true;
});

// Dates: convert dd/mm/yyyy; for 'to' set 23:59:59.999 so the whole day is included
function dateFilterLogic(val, rowDate, operator) {
    if (!val || !rowDate) return true;
    var parts = val.split('/');
    var filterDate = new Date(parts[2], parts[1] - 1, parts[0]);
    if (operator === 'to') filterDate.setHours(23, 59, 59, 999);
    var dataDate = new Date(rowDate);
    if (operator === 'from') return dataDate >= filterDate;
    if (operator === 'to')   return dataDate <= filterDate;
    return true;
}
```

> `registerCustomSearch` is safe to call on page reloads (it skips if already registered).

### Toggle the bar

```js
$(document).on('click', '.search-filter-toggle', function () {
    var target = $(this).data('target');
    var id = target.replace('filterBar-', '');
    IplatFilter.toggle(target);
    window.filterState[id] = IplatFilter.getState(id);
});
```

### Dynamic select dropdown

```js
IplatFilter.populateSelect('filterBar-roles', 'guard', guards);   // guards = array of unique values
```

### Export

The export button reads `window.filterState`, converting dates from `dd/mm/yyyy` to `yyyy-mm-dd`:

```js
window.exportData = function(format) {
    var state = window.filterState['users'] || {};
    var clean = {};
    $.each(state, function(k, v) {
        if (v !== '' && v !== null && v !== undefined) {
            clean[k] = (k.indexOf('_from') !== -1 || k.indexOf('_to') !== -1) ? toMySQLDate(v) : v;
        }
    });
    var params = $.param(clean);
    window.location.href = routes.exportUsers + (params ? '?' + params : '');
};

function toMySQLDate(val) {
    if (!val) return '';
    var parts = val.split('/');
    return parts[2] + '-' + parts[1] + '-' + parts[0];
}
```

> The export endpoint filters from the same query parameters.

---

## 6. Delete Confirmation

Include the component once:

```blade
@include('components.iplat1.confirm-delete')
```

It provides two modals: `#confirmDeleteModal` (red) and `#confirmWarningModal` (orange).

Usage:

```js
// 1 step: direct delete confirmation
Iplat.confirmDelete(routes.destroy.replace(':id', ulid), function() {
    if (table) table.ajax.reload(null, false);
}, 'Custom message (optional, defaults to general.confirm_delete)');

// 2 steps: warn first, then proceed to the delete modal
Iplat.confirmWarning(message, routes.destroy.replace(':id', ulid), callback);
```

Register the `#btnConfirmDelete` handler once on the page:

```js
var originalDeleteHtml = $('#btnConfirmDelete').html();
$('#btnConfirmDelete').on('click', function() {
    var url = $('#confirmDeleteModal').data('delete-url');
    var callback = $('#confirmDeleteModal').data('on-success');
    $('#btnConfirmDelete').prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm me-1"></span>' + window.iplatTranslations.deleting);
    Iplat.ajax(url, 'DELETE', {}, function(res) {
        Iplat.toast(res.message, 'success');
        bootstrap.Modal.getInstance('#confirmDeleteModal').hide();
        if (callback) callback();
    }, function() {
        $('#btnConfirmDelete').prop('disabled', false).html(originalDeleteHtml);
    });
});

// Restore the button when the modal closes
$('#confirmDeleteModal').on('hidden.bs.modal', function () {
    $('#btnConfirmDelete').prop('disabled', false).html(originalDeleteHtml);
});
```

---

## 7. Toast

```js
Iplat.toast('Data saved successfully', 'success');                                  // right
Iplat.toast('An error occurred', 'danger',  { position: 'center', autohide: false });
Iplat.toast('Info', 'info',                  { position: 'top-right', delay: 5000 });
```

- `type`: `success` | `danger` | `warning` | `info`
- `position`: `top-right` | `top-left` | `top-center` | `center` | `right` | `left`
- A container is created automatically per position. `components.iplat1.toast` only
  provides a static container (`#toast-container`) when the page needs one.

---

## 8. Empty State (optional)

```blade
@include('components.iplat1.empty-state', [
    'icon'        => 'bi-inbox',          // bootstrap icon
    'message'     => __('general.no_data'),
    'description' => 'Optional',
    'colspan'     => 5,
])
```

Renders a single `<tr>` inside `<tbody>` — for empty non-server-side tables.

---

## 9. Conventions & Checklist

- **ULID in URLs**: controllers use `where('ulid', $id)->firstOrFail()`. Models hide the
  numeric id so it never leaks into JSON/responses: `#[Hidden(['id'])]` (the int `id`
  stays as the primary key; the ULID is the public identity).
- **Translations**: add new keys to `lang/{en,id}/general.php` and per-module lang files
  (`lang/{en,id}/sysadmin/users/index.php`, etc.). DataTables uses `general.dt_*`;
  flatpickr uses `general.months` & `general.weekdays_short`.
- **Asset cache busting**: `?v={{ filemtime(public_path('...asset...')) }}`.
- **AJAX**: use `Iplat.ajax` (automatic CSRF token + error toast) — do not call `$.ajax`
  directly in CRUD pages.
- **Trim input** for every field except password; **min password length 8**; disable
  buttons while a request is in progress.
- **No-change detection**: the backend returns `{ no_change: true }` → just show a toast,
  do not reload the table.
- `window.iplatTranslations.saving/deleting` for button text while processing.
- New files follow the `iplat{version}_{function}.{ext}` naming convention.