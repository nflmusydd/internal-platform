# Route Permission (Internal Platform)

Dokumen ini menjelaskan bagaimana kontrol akses bekerja di aplikasi. Aturan intinya:

> **Sebuah permission diturunkan dari route name melalui konvensi penamaan (modul + aksi).**

Route bernama `sysadmin.users.index` memetakan ke permission `sysadmin.users.view`; route
`sysadmin.users.store` dan `sysadmin.users.ajax.store` sama-sama memetakan ke
`sysadmin.users.create`. Middleware memblokir route bila user yang login tidak memiliki
permission turunan tersebut, dan sidebar hanya menampilkan menu yang boleh dibuka user. Untuk
mekanisme katalog/sync menu lihat [`menu-management.id.md`](./menu-management.id.md); untuk
konvensi komponen halaman lihat [`iplat1-components.id.md`](./iplat1-components.id.md).

## 1. Prinsip

- **Permission berorientasi aksi, bukan endpoint.** Beberapa route (sebuah halaman dan
  endpoint AJAX-nya) berbagi satu permission, mis. `sysadmin.users.view` mencakup halaman
  `index`, `show`, dan setiap endpoint AJAX baca pada modul tersebut.
- **Code-driven.** Himpunan permission diturunkan dari tabel route lewat konvensi penamaan.
  Menambah route otomatis menyediakan permission-nya setelah seeder dijalankan.
- **Sumber kebenaran tunggal.** Semuanya ada di `App\Support\RoutePermission`.
- **Fail-closed, dan pemulihannya satu perintah.** Route bernama yang permission-nya hilang
  akan *ditolak* untuk semua orang (kecuali Super Admin), sehingga penghapusan yang tidak
  sengaja tidak pernah diam-diam mengekspos sebuah modul. `php artisan permission:sync` membuat
  ulang baris yang hilang (bagian 9), dan sebuah coverage test otomatis menjamin setiap route
  aplikasi nyata punya permission.

## 2. Arsitektur

| Kebutuhan | Lokasi |
|---|---|
| Konfigurasi allowlist + guard + konvensi | `config/route_permissions.php` |
| Sumber kebenaran route → permission | `app/Support/RoutePermission.php` |
| Middleware penjaga route | `app/Http/Middleware/CheckRoutePermission.php` |
| Alias middleware | `bootstrap/app.php` → `check.route.permission` |
| Penerapan middleware | `routes/web.php` (grup auth) |
| Bypass Super Admin | `app/Providers/AppServiceProvider.php` → `Gate::before` |
| Filter sidebar | `app/Providers/AppServiceProvider.php` (view composer) |
| Seeding permission | `database/seeders/RolePermissionSeeder.php` |
| Role / bundle / user demo default | `app/Support/DefaultRolePermissions.php` |
| Perintah pemulihan permission | `app/Console/Commands/PermissionSyncCommand.php` (`permission:sync`) |
| Test | `tests/Feature/RouteAccessTest.php`, `tests/Feature/RoutePermissionCoverageTest.php` |

`RoutePermission` adalah helper statis sederhana sehingga seeder, middleware, composer sidebar,
dan test memakai logika yang sama persis.

## 3. Konfigurasi

`config/route_permissions.php`:

```php
'exclude' => ['login', 'register', 'logout', 'locale'],
'guard'   => 'web',

'ignore_segments'    => ['ajax'],
'action_map'         => [
    'index' => 'view',   'show'   => 'view',
    'create' => 'create', 'store'  => 'create',
    'edit' => 'edit',    'update' => 'edit',
    'destroy' => 'delete',
],
'action_prefix_map'  => ['export' => 'export', 'sync' => 'edit'],
'read_actions'       => ['all', 'users', 'roles', 'role_permissions', 'role_users',
    'user_roles', 'user_permissions', 'roles_for_user', 'permissions_for_user', 'permission_roles'],
'default_action'     => 'view',
```

- **`exclude`** — nama route yang tidak boleh menjadi permission dan selalu bisa diakses user
  yang login. Jaga sekecil mungkin; route bernama lain (termasuk endpoint AJAX) dijaga.
- **`guard`** — guard untuk permission yang dibuat (Spatie).
- **`ignore_segments`** — segmen route yang dibuang sebelum menurunkan permission. `ajax` hanya
  kontainer URL (`/ajax/...`), jadi bukan bagian identitas modul.
- **`action_map`** / **`action_prefix_map`** — pemetaan aksi → suffix permission (bagian 4).
- **`read_actions`** — segmen aksi read (endpoint AJAX list/detail) yang semuanya bermuara ke
  permission `view` modul. Dibuat eksplisit agar convention test gagal bila ada route baru yang
  belum diklasifikasikan secara sadar.
- **`default_action`** — suffix cadangan bila tidak ada yang cocok. Ini hanya jaring pengaman
  runtime; convention test menjamin setiap route nyata terklasifikasi.

## 4. Cara kerja

### 4.1 Konvensi penamaan

`RoutePermission::permissionFor($routeName)` menurunkan permission:

1. pecah nama route pada `.`;
2. buang `ignore_segments` (`ajax`);
3. segmen **terakhir** yang tersisa adalah *aksi*, sisanya adalah *modul*;
4. terjemahkan aksi: `action_map` → `action_prefix_map` → `read_actions` (`view`) →
   `default_action`;
5. permission-nya adalah `module.suffix`.

| Nama route | Permission |
|---|---|
| `sysadmin.users.index` | `sysadmin.users.view` |
| `sysadmin.users.show` | `sysadmin.users.view` |
| `sysadmin.users.create` | `sysadmin.users.create` |
| `sysadmin.users.store` | `sysadmin.users.create` |
| `sysadmin.users.ajax.users` (read) | `sysadmin.users.view` |
| `sysadmin.users.ajax.store` | `sysadmin.users.create` |
| `sysadmin.users.ajax.update` | `sysadmin.users.edit` |
| `sysadmin.users.ajax.destroy` | `sysadmin.users.delete` |
| `sysadmin.users.ajax.export_users` | `sysadmin.users.export` |
| `sysadmin.role_permissions.ajax.sync_permissions` | `sysadmin.role_permissions.edit` |
| `reports.index` | `reports.view` |

Karena halaman dan endpoint AJAX-nya melebur menjadi satu permission, proyek ini saat ini
menghasilkan **30** permission (turun dari 61 endpoint).

### 4.2 Route mana yang menjadi permission

`RoutePermission::routeNames()` menelusuri `Route::getRoutes()` dan mengembalikan setiap route
bernama kecuali:

- daftar **exclude** yang dikonfigurasi, dan
- **route vendor/framework** (lihat bagian 6).

`RoutePermission::permissions()` memetakan daftar itu melalui konvensi dan mengembalikan nama
permission yang unik dan terurut. Di proyek ini saat ini menghasilkan **30** permission.

### 4.3 Membuat permission

`RoutePermission::sync()` menyisipkan permission (di guard `web`) untuk setiap nama dari
`permissions()` yang belum ada, lalu mengembalikan `['created' => [...], 'existing' => [...]]`.
Method ini hanya **membuat**; tidak pernah menghapus permission. `RolePermissionSeeder`
memanggilnya, dan perintah `permission:sync` membungkusnya untuk pemulihan (lihat bagian 9).

Karena sync **hanya membuat**, menghapus baris permission adalah kesalahan yang tidak lagi
ditoleransi diam-diam: middleware bersifat **fail-closed** (bagian 5), sehingga route tetap
diblokir sampai barisnya dibuat ulang dengan `php artisan permission:sync`.

### 4.4 Menjaga route

Setiap route di dalam grup terautentikasi membawa middleware `check.route.permission`
(`routes/web.php`):

```php
Route::middleware(['auth', 'check.route.permission'])->group(function () {
    // ...
});
```

`CheckRoutePermission` mengambil nama route saat ini lalu memanggil
`RoutePermission::allows($user, $name)`. Bila hasilnya `false`, request dihentikan dengan
**403** (`general.unauthorized`, EN/ID).

### 4.5 Keputusan `allows()`

`RoutePermission::allows(?Authenticatable $user, ?string $routeName)`:

| Kondisi | Hasil |
|---|---|
| `$routeName` `null`/kosong (folder/placeholder) | diizinkan |
| `$routeName` ada di daftar **exclude** | diizinkan |
| Tidak ada user login | ditolak |
| Selain itu | `$user->can(permissionFor($routeName))` (Spatie: menelusuri role) |

Pemeriksaan bersifat **fail-closed**: tidak ada short-circuit "permission tidak ada". Bila
baris permission hilang, `$user->can()` mengembalikan `false` (Spatie menelan
`PermissionDoesNotExist`), sehingga route **ditolak** dan menunya menghilang. Satu-satunya
pengecualian adalah **SUPER ADMIN**: bypass `Gate::before` berada *di dalam* `$user->can()`,
jadi Super Admin tetap lolos walau baris permission-nya tidak ada. Singkatnya: *hapus
permission ⇒ 403 untuk semua kecuali Super Admin*.

### 4.6 Bypass Super Admin

`AppServiceProvider::boot()` mendaftarkan hook `Gate::before`:

```php
Gate::before(fn ($user, $ability) =>
    method_exists($user, 'hasRole') && $user->hasRole('SUPER ADMIN') ? true : null);
```

**SUPER ADMIN** lolos semua pemeriksaan tanpa perlu tiap permission di-assign satu per satu;
mengembalikan `null` untuk user lain agar handler `Gate::before` milik Spatie ikut berjalan.

### 4.7 Filter sidebar

Composer view yang sama yang memuat menu juga memfilternya dengan
`RoutePermission::allows()`:

- menu **daun** tampil hanya bila user boleh mengakses `route_name`-nya;
- menu **folder** tampil hanya bila minimal satu anaknya tampil.

Catatan: kolom legacy `menus.permission_name` sudah **dihapus**. Filter murni berdasarkan
`route_name`, yang dipetakan ke permission-nya lewat konvensi.

Karena filter memakai `allows()` yang sama dengan middleware, menghapus permission
menyembunyikan menunya sekaligus memblokir URL-nya — keduanya tidak bisa lagi berbeda.

## 5. Fail-closed + coverage test

Fail-closed berarti baris permission yang hilang **menolak** akses, bukan membuka route. Ini
disengaja: penghapusan yang tidak sengaja (atau snapshot DB yang basi) tidak boleh diam-diam
mengekspos sebuah modul. Pemulihannya hanya satu perintah — lihat bagian 9.

Konsekuensinya, DB memang harus ter-seed; jika tidak, setiap route terjaga mengembalikan
**403** untuk selain Super Admin. `tests/Feature/RoutePermissionCoverageTest.php` gagal bila ada
route dari `RoutePermission::routeNames()` tanpa permission yang cocok, atau bila route
allowlisted / vendor lolos ke `routeNames()`. Test ini juga gagal bila ada aksi route yang tidak
diklasifikasikan secara eksplisit oleh konvensi (lihat bagian 3 dan 4.1), sehingga endpoint
write baru tidak akan pernah diam-diam dipetakan ke `view`.

`tests/Feature/RouteAccessTest.php` menguji perilaku end-to-end:

- Super Admin dapat membuka setiap halaman modul.
- Role yang dibatasi ke satu modul mendapat **403** di modul lain.
- URL yang diketik manual untuk menu terhidden tetap diblokir di sisi server.
- Sidebar hanya menampilkan menu yang boleh diakses.
- Route allowlisted tetap bisa diakses.
- User tanpa permission tidak melihat menu modul apa pun.
- Menghapus permission sebuah route menutup route **dan** menyembunyikan menunya (fail-closed).
- `permission:sync` membuat ulang permission yang terhapus.
- Permission yang terikat route tidak bisa dihapus atau di-rename lewat endpoint (**422**).

## 6. Route vendor

`Route::getRoutes()` juga memuat route framework seperti `storage.local` dan
`storage.local.upload`. Route ini **tidak boleh** menjadi permission (menjaganya akan merusak
penyajian file). `RoutePermission::isVendorRoute()` mendeteksi route yang aksinya didefinisikan
di bawah `/vendor/` (via refleksi file method controller / closure), dan `routeNames()`
mengecualikannya.

Tidak ada **penghapusan otomatis** permission basi di mana pun dalam kode. Sync tidak pernah
menghapus. Bila permission vendor sisa (mis. `storage.local`) sempat muncul, ia tidak berbahaya —
tidak diturunkan oleh konvensi sehingga tidak ada yang mengeceknya — dan bisa dihapus manual.
Orphan yang **di-generate** hanya *dilaporkan* oleh `permission:sync` (bagian 9), tidak pernah
dihapus.

### 6.1 Permission generated vs manual

Setiap baris di tabel `permissions` memiliki boolean `is_generated`:

- **Generated (`is_generated = true`)** — dibuat oleh konvensi dari sebuah route. Baris ini
  dimiliki oleh kode: `permission:sync` membuatnya, menyembuhkan sendiri flag pada baris lama,
  dan melaporkannya sebagai orphan ketika route-nya hilang. Baris ini dilindungi dari
  hapus/rename di UI (bagian 9.1).
- **Manual (`is_generated = false`)** — dibuat admin lewat **Role & Permissions → Permission →
  Add** untuk permission yang tidak dipetakan route mana pun (mis. gate yang dipakai hanya di
  controller atau Blade). `permission:sync` membiarkannya dan tidak pernah melaporkannya
  sebagai orphan, karena keberadaannya memang disengaja.

Halaman Permission bisa difilter berdasarkan **Tipe** (Otomatis / Manual) dan ekspor XLSX
menyertakan kolom yang sama, sehingga baris generated mudah dibedakan dari yang dibuat tangan.
Saat menambah permission manual, ingat ia tidak terikat route mana pun — tetapkan ke role/user
dan cek secara eksplisit (mis. via `$user->can(...)` atau gate).

## 7. Role dan bundle

`DefaultRolePermissions` (dipakai baik oleh `RolePermissionSeeder` maupun `permission:sync
--restore-roles`) membuat role dasar dan menetapkan permission berdasarkan prefix:

| Role | Permission |
|---|---|
| `SUPER ADMIN` | **semua** permission web (`*`) |
| `DEVELOPER` | `development.components.%`, `development.command.%` |
| `MANAGER` | `work_calendar.%`, `reports.%` |
| `TEAM LEADER` | `work_calendar.%`, `reports.%` |
| `EMPLOYEE` | `work_calendar.%` |

User demo yang dibuat seeder (password `test1234`): `otoriterman@internal.com`
(SUPER ADMIN), `developer@internal.com` (DEVELOPER), `manager@internal.com` (MANAGER),
`teamleader@internal.com` (TEAM LEADER), `employee@internal.com` (EMPLOYEE).

Prefix bundle adalah titik awal yang realistis; UI aplikasi (Role & Permissions / User
Permissions) tetap menjadi tempat untuk memberi akses lebih rinci saat runtime.

## 8. Permission both-state (langsung + via role)

Modul User Permission memungkinkan sebuah permission diberikan **langsung** ke user *dan*
melalui role sekaligus. Di modal assignment, baris semacam ini tetap tercentang secara visual
dan tidak bisa di-uncheck (karena sudah diberikan lewat role). Di server,
`UserPermissionController::syncPermissions()` **memin** baris ini: permission both-state yang
tidak ada di payload yang dikirim akan dipertahankan alih-alih dilepas, sehingga grant langsung
beserta metadata `assigned_by`/`assigned_at`-nya tidak pernah hilang diam-diam. Pesan
`422 permission_in_role` tetap dikembalikan bila permission yang *hanya via role* dipaksa masuk.

## 9. Pemulihan — `permission:sync`

Karena pemeriksaan bersifat fail-closed, permission yang terhapus harus dibuat ulang. Perintah
khusus ini adalah perbaikan yang didukung:

```bash
docker exec dev_internal_platform-app-1 php artisan permission:sync
```

Perintah ini:

1. membuat setiap permission dari `permissions()` yang hilang (logika sama dengan seeder),
2. menampilkan nama yang dibuat,
3. menandai setiap permission turunan route yang sudah ada sebagai generated
   (`is_generated = true`) — ini menyembuhkan sendiri permission route yang dibuat manual atau
   sebelum flag ini ada,
4. **melaporkan** (tidak menghapus) setiap permission *generated* yang tidak lagi diturunkan
   konvensi ("orphan"). Permission manual memang disengaja dan **tidak** dilaporkan.

Untuk sekaligus menerapkan ulang bundle role default (membatalkan editan role manual), pakai:

```bash
docker exec dev_internal_platform-app-1 php artisan permission:sync --restore-roles
```

Sengaja **tidak ada `--prune`** dan tidak ada jalur kode yang menghapus permission.

### 9.1 Hapus/rename diblokir di UI

Menghapus permission juga menghapusnya dari semua role (dilepas Spatie), yang justru merupakan kesalahan
yang dapat secara diam-diam mengunci modul tersebut. Untuk mencegahnya, `PermissionController` menolak
menghapus atau me-rename permission yang namanya diturunkan dari route aktif
(`in_array($name, RoutePermission::permissions())`):

- `DELETE /sysadmin/permissions/ajax/{id}` dan `PUT /sysadmin/permissions/ajax/{id}`
  mengembalikan **422** dengan `permission_locked_route`;
- pesannya dimunculkan oleh toast error AJAX bersama;
- rename ke nama *berbeda* diblokir; update hanya `guard_name` (nama tidak berubah) diizinkan.

### 9.2 Menjalankan seeder

Docker (selalu di dalam container `app`, sesuai konvensi proyek):

```bash
docker exec dev_internal_platform-app-1 php artisan db:seed --class=RolePermissionSeeder
```

Setelah mengubah route, jalankan ulang untuk membuat permission baru. Untuk reset penuh:

```bash
docker exec dev_internal_platform-app-1 php artisan migrate:fresh --seed
```

## 10. Menambah modul / route baru — checklist

- [ ] Definisikan route dengan **nama** mengikuti konvensi
      (`module.section.action`, mis. `module.section.ajax.store`). Nama ini dipetakan ke
      permission-nya (bagian 4.1); pakai aksi baca (`index`, `show`, atau segmen
      `read_actions`) untuk read dan aksi tulis (`store`, `update`, `destroy`, `export`,
      `sync`) untuk write.
- [ ] Jangan tambahkan ke `config/route_permissions.exclude`.
- [ ] Jika route memperkenalkan segmen aksi baru, klasifikasikan di
      `config/route_permissions.php`; jika tidak, `RoutePermissionCoverageTest` gagal.
- [ ] Jalankan `RolePermissionSeeder` (atau `php artisan permission:sync`) agar baris permission
      dibuat.
- [ ] Assign permission baru ke role terkait (lewat aplikasi atau memperluas bundle di
      `DefaultRolePermissions`).
- [ ] Tambahkan entri menu di `MenuSynchronizer` dengan `route_name` yang cocok lalu jalankan
      `php artisan menu:sync`.
- [ ] Jalankan test — `RoutePermissionCoverageTest` membuktikan route sudah tercakup.

## 11. Test

```bash
docker exec dev_internal_platform-app-1 php artisan test tests/Feature/RouteAccessTest.php
docker exec dev_internal_platform-app-1 php artisan test tests/Feature/RoutePermissionCoverageTest.php
```
