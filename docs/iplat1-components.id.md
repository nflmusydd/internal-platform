# Panduan Membuat Menu Baru (Internal Platform — iplat1)

Dokumen ini memandu pembuatan halaman/menu baru pada Internal Platform dengan sistem
komponen `iplat1`. Prinsip utama: **layout hanya memuat base; semua aset lain dimuat
per-halaman melalui komponen blade.**

## Arsitektur Ringkas

Layout `resources/views/layouts/iplat1_layout1.blade.php` hanya menyediakan:
- **CSS**: Bootstrap, Bootstrap Icons, Poppins, `css/iplat1_base.css` (styling modal
  form, disabled buttons, draggable modal), dan slot `@stack('styles')`.
- **JS**: jQuery, Bootstrap bundle, `js/iplat1_modal.js` (draggable modal), logika
  sidebar/topbar layout, dan slot `@stack('scripts')`.

Semua aset lain di-push ke `@stack('styles')` / `@stack('scripts')` melalui komponen di
`resources/views/components/iplat1/`.

### Kepemilikan aset

| Kebutuhan | Include | Menyediakan |
|---|---|---|
| AJAX / toast / CRUD / konfirmasi | `components.iplat1.crud` | `window.appLocale`, `window.iplatTranslations`, `Iplat.*` (`js/iplat1_crud.js`) |
| Tabel + filter + input tanggal | `components.iplat1.datatables` | `window.dtLangDefaults`, DataTables (+ FixedColumns), `IplatDataTable`, `IplatFilter`, flatpickr + theme |
| Breadcrumb dinamis (dari tabel `menus`) | `components.iplat1.breadcrumb` | param `group` (slug menu parent) & `currentPage` |
| Filter bar | `components.iplat1.search-filter` | grid input filter `text` / `select` / `date` |
| Modal konfirmasi hapus & warning | `components.iplat1.confirm-delete` | `#confirmDeleteModal`, `#confirmWarningModal` |
| Modal badge list lazy | `components.iplat1.badge-list` | `#badgeListModal`, `Iplat.showBadgeList` |
| Empty state | `components.iplat1.empty-state` | baris `<tr>` kosong untuk tabel |
| Container toast statis (opsional) | `components.iplat1.toast` | `<div id="toast-container">` |

> `crud` harus di-include **sebelum** `datatables` bila keduanya dipakai (urutan `@push`
> menentukan kemunculan di `@stack`).

### File aset

| File | Isi |
|---|---|
| `public/js/iplat1_crud.js` | Namespace `Iplat` (ajax, toast, modal, confirmasi) |
| `public/js/iplat1_datatable.js` | `IplatDataTable.init()` + default DataTables |
| `public/js/iplat1_filter.js` | `IplatFilter` (toggle, init, state, populate, custom search) |
| `public/js/iplat1_flatpickr.js` | Auto-init flatpickr untuk `.filter-date` |
| `public/js/iplat1_modal.js` | Draggable modal (base, dimuat layout) |
| `public/css/iplat1_base.css` | Modal form + disabled buttons + checkbox bertema + draggable cursor (dimuat layout) |
| `public/css/iplat1_datatable.css` | Styling DataTables |
| `public/css/iplat1_filter.css` | Toolbar, filter bar, dropdown, tombol export |
| `public/css/iplat1_flatpickr.css` | Theme hijau flatpickr + custom month dropdown |

Konvensi penamaan: `iplat{versi}_{fungsi}.{ext}`, komponen di `components/iplat{versi}/`,
layout `iplat{versi}_layout{n}.blade.php`. Saat ada versi baru, angka versi naik (`iplat2_*`).

---

## 1. Kerangka Halaman

```blade
@extends('layouts.iplat1_layout1')
@include('components.iplat1.crud')        {{-- hanya jika pakai Iplat.* --}}
@include('components.iplat1.datatables')  {{-- hanya jika pakai tabel/filter/date --}}

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

    {{-- isi halaman --}}

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
    // logika halaman
});
</script>
@endpush
```

> `:id` pada named route adalah **ULID**, bukan id numerik.

---

## 2. Tombol

Pola tombol yang sudah distyling:

```blade
{{-- Primary (hijau) --}}
<button class="btn btn-sm fw-semibold" style="background-color:var(--primary-green);color:#fff;">
    <i class="bi bi-plus-lg me-1"></i>{{ ucfirst(__('general.add')) }}
</button>

{{-- Toggle filter bar --}}
<button class="btn btn-sm btn-outline-secondary search-filter-toggle" type="button"
        data-target="filterBar-users" title="{{ ucfirst(__('general.search')) }}">
    <i class="bi bi-funnel me-1"></i>{{ ucfirst(__('general.search')) }}
</button>

{{-- Tombol export (dropdown) --}}
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

{{-- Aksi baris tabel --}}
<button class="btn btn-sm btn-outline-warning me-1" title="{{ ucfirst(__('general.edit')) }}">
    <i class="bi bi-pencil"></i>
</button>
<button class="btn btn-sm btn-outline-danger" title="{{ ucfirst(__('general.delete')) }}">
    <i class="bi bi-trash"></i>
</button>
```

Saat proses submit: tombol di-disable + tampilkan spinner, lalu pulihkan HTML semula:

```js
var originalHtml = $('#btnSubmit').html();
$('#btnSubmit').prop('disabled', true)
    .html('<span class="spinner-border spinner-border-sm me-1"></span>' + window.iplatTranslations.saving);
```

---

## 3. Modal & Form Input

Pola modal CRUD (contoh di menu `users`):

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

**Aturan & JS:**
- Setiap field dibuka dengan `<div class="invalid-feedback" id="{field}Error">` untuk pesan validasi.
- Buka modal: `new bootstrap.Modal('#userModal').show()`.
- Reset kondisi sebelum buka: `Iplat.resetModal('#userModal')` (kosongkan form, hapus `is-invalid`).
- **Trim semua input kecuali password** sebelum dikirim.
- Kirim via `Iplat.ajax(url, method, $data, onSuccess, onError)`; pada sukses
  `Iplat.toast(res.message, 'success')` + `bootstrap.Modal.getInstance(...).hide()` + reload tabel.
- Validasi backend (`422`): `Iplat.showErrors('#userModal', errors)` mengisi tiap `#fieldError`.
  Bila tidak ada handler error, `Iplat.ajax` otomatis menampilkan pesan pertama sebagai toast.
- Modal bisa digeser kursor (`.modal-header { cursor: move }` dari `iplat1_base.css`).
- Pulihkan tombol submit pada `hidden.bs.modal` agar tidak stuck disabled.

---

## 4. DataTables

Markup tabel:

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

Inisialisasi:

```js
var table = IplatDataTable.init('#usersTable', {
    ajax: { url: routes.list, dataSrc: 'data' },
    columns: [
        { data: null, orderable: false, searchable: false, className: 'ps-3 text-muted', defaultContent: '' },
        { data: 'name', className: 'fw-semibold' },
        { data: null, orderable: false, className: 'text-center', render: function(d) { /* aksi + escHtml */ } }
    ],
    order: [[1, 'asc']]
});

// Nomor baris berurutan
table.on('draw.dt', function () {
    var info = table.page.info();
    table.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function (cell, i) {
        cell.innerHTML = i + 1 + info.start;
    });
});

// WAJIB untuk IplatFilter
table.settings()[0]._searchFilterId = 'users';
```

**Default yang sudah diatur `IplatDataTable`:** `scrollX`, `fixedColumns: { start: 1, end: 0 }`
(kolom pertama "No." terkunci di kiri; sisi kanan tanpa kolom terkunci),
`pageLength` 10, `lengthMenu [10,25,50,100]`, bahasa dari `window.dtLangDefaults`,
`info`, dan ikon pagination (chevron bootstrap-icons).

#### Kolom tetap (Fixed columns)

FixedColumns 5 menempelkan sebuah kolom sementara kolom lain bergeser horizontal. Default
mengunci kolom pertama (No.) saja. Untuk membuat kolom tetap sendiri, override
`fixedColumns` di config instance (halaman index):

```js
var table = IplatDataTable.init('#ordersTable', {
    fixedColumns: { start: 1, end: 1 },  // kunci No. di kiri DAN Actions di kanan
    // ...opsi lainnya
});
```

`start` = jumlah kolom yang dikunci dari kiri, `end` = jumlah kolom yang dikunci dari
kanan. Keduanya bernilai default `1` bila opsi dihilangkan, jadi hapus `fixedColumns`
seluruhnya jika tidak ingin ada kolom yang dikunci.

Ketentuan / catatan:

- Plugin mengandalkan `position: sticky`, jadi kontainer **tidak boleh** menjadi
  scroll-origin: `.dt-container` memakai `overflow: clip` (tetap memotong sudut
  membulat tanpa memblokir sticky). Jangan dikembalikan ke `overflow: hidden`, atau kolom
  tetap diam-diam berhenti bekerja (forum DataTables #76300).
- Sel yang diberi gaya ditandai FixedColumns dengan class `dtfc-fixed-start` /
  `dtfc-fixed-end`. Stylesheet default memberi sel tubuh yang dikunci latar putih solid
  agar konten yang bergeser tidak tembus dari balik sel transparan. Class `.dtfc-fixed-*`
  dihasilkan plugin, bukan ditulis manual per tabel.
- Sel header tetap mengikuti tema hijau karena rule `thead > tr > th` yang sudah ada
  menang berdasarkan specificity.

Tips:
- Data yang ditampilkan dari AJAX selalu di-escape: `escHtml()` (buat helper lokal).
- Kolom actions: tombol edit/hapus dipanggil via `onclick="openModal('ulid')"` /
  `onclick="deleteRow('ulid')"`.

---

## 5. Search Filter

### Markup (komponen `search-filter`)

Tipe filter: `text`, `select`, `date`.

```blade
@include('components.iplat1.search-filter', [
    'id' => 'users',
    'maxCols' => 2,            // kolom per baris
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

> `newRow` memindahkan filter berikutnya ke baris baru. Input `date` otomatis menjadi
> Flatpickr (locale mengikuti `window.appLocale`, ada custom month dropdown bahasa).
> Pasangan `*_from` / `*_to` otomatis terhubung `min`/`max` (aturan `endsWith('_from'/'_to')`).

### Inisialisasi JS

```js
IplatFilter.init('#usersTable', {
    id: 'users',
    table: usersTable,
    columnMap: { name: 1, email: 2 },                       // kolom yang di-search
    customFilters: { status: true, created_at_from: true, created_at_to: true },
    onStateChange: function(s) { window.filterState['users'] = s; }
});
```

- `customFilters` untuk key yang **bukan** kolom langsung (diproses `registerCustomSearch`).
- `window.filterState[id]` menyimpan nilai filter saat ini (dipakai export).

### Filter non-kolom

```js
IplatFilter.registerCustomSearch('status', function (val, settings, data, dataIndex) {
    var api = new $.fn.dataTable.Api(settings);
    var rowData = api.row(dataIndex).data();
    if (val === '1') return rowData.is_active === 1 || rowData.is_active === '1';
    if (val === '0') return rowData.is_active === 0 || rowData.is_active === '0';
    return true;
});

// Tanggal: konversi dd/mm/yyyy, dan untuk 'to' set 23:59:59.999 agar mencakup hari penuh
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

> `registerCustomSearch` aman dipanggil saat halaman dimuat ulang (skips bila sudah terdaftar).

### Toggle bar

```js
$(document).on('click', '.search-filter-toggle', function () {
    var target = $(this).data('target');
    var id = target.replace('filterBar-', '');
    IplatFilter.toggle(target);
    window.filterState[id] = IplatFilter.getState(id);
});
```

### Dropdown select dinamis

```js
IplatFilter.populateSelect('filterBar-roles', 'guard', guards);   // guards = array nilai unik
```

### Export

Tombol export membaca `window.filterState`, dan tanggal dikonversi `dd/mm/yyyy` → `yyyy-mm-dd`:

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

> Endpoint export memfilter dari parameter query yang sama.

---

## 6. Konfirmasi Hapus

Include komponen sekali:

```blade
@include('components.iplat1.confirm-delete')
```

Memberikan dua modal: `#confirmDeleteModal` (merah) dan `#confirmWarningModal` (oranye),
serta mengikat pengiriman hapus `#btnConfirmDelete` secara internal (dijaga dengan
keberadaan `Iplat`).

Panggilan:

```js
// 1 langkah: langsung konfirmasi hapus (nama disimpan & ditampilkan di pesan)
Iplat.confirmDelete(routes.destroy.replace(':id', ulid), function() {
    if (table) table.ajax.reload(null, false);
}, data.name);

// 1 langkah dengan pesan kustom penuh (arg ke-4 opsional; `:name` tetap diganti jika ada)
Iplat.confirmDelete(routes.destroy.replace(':id', ulid), callback, data.name, 'Teks kustom untuk <strong>:name</strong>');

// 1 langkah dengan judul modal kustom juga (arg ke-5 opsional; kembali ke judul bawaan jika kosong)
Iplat.confirmDelete(routes.destroy.replace(':id', ulid), callback, data.name, message, 'Hapus ' + data.name + '?');

// 2 langkah: warning dulu, lanjut ke modal hapus
//   confirmWarning(pesanWarning, url, onSuccess, name, deleteMessage?, title?)
//   - deleteMessage: pesan kustom untuk langkah hapus (diteruskan ke confirmDelete)
//   - title: judul kustom untuk KEDUA modal (warning dan hapus)
Iplat.confirmWarning(message, routes.destroy.replace(':id', ulid), callback, data.name,
    'Teks kustom untuk <strong>:name</strong>', 'Hapus ' + data.name + '?');
```

- Jika `name` diberikan, pesan dibangun dari `general.confirm_delete_with` (`:name`
  di-escape HTML dan ditampilkan tebal oleh template terjemahan, mis. `<strong>:name</strong>`)
  dan disimpan sebagai data `#confirmDeleteModal` (`delete-name`). Tanpa `name`, pesan
  default `general.confirm_delete` digunakan.
- Argumen ke-4 (`message`, opsional) sepenuhnya menimpa pesan; `:name` di dalamnya tetap
  diganti dengan nama yang sudah di-escape. Jika `name` dan `message` sama-sama kosong, pesan
  default `general.confirm_delete` yang ditampilkan.
- Argumen ke-5 (`title`, opsional) menimpa judul modal (`#confirmDeleteModalTitle` dan, untuk
  `confirmWarning`, `#confirmWarningModalTitle`); jika kosong, judul default terjemahan
  digunakan kembali. Title di-set sebagai teks biasa (bukan HTML).
- `confirmWarning(message, url, onSuccess, name, deleteMessage, title)` meneruskan dua argumen
  terakhir ke `confirmDelete` untuk langkah hapus.
- Perilaku `#btnConfirmDelete` (disable + spinner, `Iplat.ajax` DELETE, memanggil
  `on-success` tersimpan, pulihkan tombol saat modal ditutup) sudah ada di dalam
  komponen — tidak perlu mendaftarkan handler per halaman.

---

## 7. Toast

```js
Iplat.toast('Data berhasil disimpan', 'success');                                  // kanan
Iplat.toast('Terjadi kesalahan', 'danger',  { position: 'center', autohide: false });
Iplat.toast('Info', 'info',                 { position: 'top-right', delay: 5000 });
```

- `type`: `success` | `danger` | `warning` | `info`
- `position`: `top-right` | `top-left` | `top-center` | `center` | `right` | `left`
- Container dibuat otomatis per posisi. `components.iplat1.toast` hanya menyediakan
  container statis (`#toast-container`) bila halaman membutuhkannya.

---

## 8. Empty State (opsional)

```blade
@include('components.iplat1.empty-state', [
    'icon'        => 'bi-inbox',          // bootstrap icon
    'message'     => __('general.no_data'),
    'description' => 'Opsional',
    'colspan'     => 5,
])
```

Render satu baris `<tr>` dalam `<tbody>` — untuk tabel non-server-side yang kosong.

---

## 9. Badge List (modal lazy)

Menampilkan modal berisi list nama yang di-fetch saat modal dibuka (tidak ada yang di-load saat halaman render).

```blade
@include('components.iplat1.badge-list')
```

Menyediakan `#badgeListModal` (judul `#badgeListModalTitle`, isi scroll `#badgeListBody`).

```js
Iplat.showBadgeList(url, title, emptyText, itemKey, cols, box);
```

- `url` — endpoint yang mengembalikan `{ data: { <itemKey>: [...] } }`.
- `title` — judul modal teks biasa (fallback: judul sebelumnya).
- `emptyText` — ditampilkan bila list kosong.
- `itemKey` — array mana yang dibaca; default `items`.
- `cols` (opsional) — jumlah kolom grid Bootstrap (`col-md-{12/cols}`). Bila tidak di-set,
  tampilan flex-wrap badge tetap dipakai. Bila `cols` ada, tiap item jadi sel penuh ber-border:
  string ditampilkan sebagai teks; objek `{ name, email }` menampilkan nama
  (`fw-semibold`) dengan email kecil muted di bawahnya (`h-100` menjaga tinggi sel seragam).
- Item bisa berupa string biasa (dirender sebagai badge) atau objek
  `{ name, email }` (dirender sebagai badge dua baris dengan email di bawah nama).
- Menampilkan spinner saat loading; menampilkan `iplatTranslations.errorOccurred` bila gagal.
- Dialog memakai `modal-lg`. Sel grid membawa class `badge-list-cell` dan badge membawa
  `badge-list-item` (`overflow-wrap`/`word-break`) sehingga teks panjang wrap di dalam
  selnya sendiri tanpa menimpa tetangga.
- `box` (opsional, default `false`) — bila `true`, kotak `border rounded p-3` pada
  `#badgeListBody` tetap dipertahankan (sama seperti default blade). Bila `false`/tidak di-set,
  kotak dilepas sehingga konten memakai lebar penuh `modal-body` (gaya modal assign-permission;
  perilaku `max-height`/scroll tetap dipertahankan).

Contoh (dari `sysadmin.role-permissions`):

```js
// render badge → clickable
'<a href="javascript:void(0)" class="badge bg-warning text-dark text-decoration-none" ' +
 'onclick="openBadgeList(\'users\', \'' + row.ulid + '\', \'' + escHtml(row.name) + '\')">…</a>'

// handler
window.openBadgeList = function(type, ulid, name) {
    var cfg = {
        permissions: { url: routes.rolePermissions.replace(':id', ulid), title: lang.permissionsOf.replace(':name', name), empty: lang.noPermissions, itemKey: 'permissions', cols: 3 },
        users:       { url: routes.roleUsers.replace(':id', ulid),       title: lang.usersOf.replace(':name', name),       empty: lang.noUsers,       itemKey: 'items',       cols: 2,       box: true },
        roles:       { url: routes.permissionRoles.replace(':id', ulid), title: lang.rolesOf.replace(':name', name),       empty: lang.noRoles,       itemKey: 'items' }
    }[type];
    Iplat.showBadgeList(cfg.url, cfg.title, cfg.empty, cfg.itemKey, cfg.cols, cfg.box);
};
```

Endpoint backend cukup mengembalikan list nama kecil, mis. `RolePermissionController@getRoleUsers`
mengembalikan `{ data: { role, items: [{ name, email }] } }` (user diurutkan berdasarkan nama).
Request `$.get` read-only seperti ini tidak butuh CSRF, jadi `Iplat.ajax` tidak diperlukan di sini.

---

## 10. Konvensi & Checklist

- **ULID di URL**: controller menggunakan `where('ulid', $id)->firstOrFail()`. Model
  menyembunyikan id numerik agar tidak bocor ke JSON/response: `#[Hidden(['id'])]` (karena
  id int tetap ada sebagai primary key, ULID dipakai sebagai identitas publik).
- **Translasi**: key baru di `lang/{en,id}/general.php` dan file lang per modul
  (`lang/{en,id}/sysadmin/users/index.php` dst). DataTables memakai `general.dt_*`;
  flatpickr memakai `general.months` & `general.weekdays_short`.
- **Cache busting asset**: `?v={{ filemtime(public_path('...asset...')) }}`.
- **AJAX**: gunakan `Iplat.ajax` (CSRF token otomatis + toast error) — jangan `$.ajax`
  langsung di halaman CRUD.
- **Trim input** semua field kecuali password; **min password 8**; tombol `disabled` saat proses.
- Deteksi data tidak berubah: backend mengembalikan `{ no_change: true }` → cukup toast,
  jangan reload tabel.
- `window.iplatTranslations.saving/deleting` untuk teks tombol saat proses.
- File baru mengikuti penamaan `iplat{versi}_{fungsi}.{ext}`.