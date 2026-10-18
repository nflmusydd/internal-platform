# Validasi Guard & Kasus Kebocoran Transaksi Octane

Dokumen ini mencatat sebuah insiden pada modul role/permission: baris yang tampil di UI
tetapi tidak pernah tersimpan di Database (MySQL), role yang bisa "dihapus" dan dibuat ulang
berulang-ulang, dan halaman yang menggantung sampai timeout 30 detik. Dokumen ini
menjelaskan dua akar penyebab, perbaikan yang diterapkan, dan aturan agar kejadian ini tidak
terulang.

Dokumen terkait: [`route-permissions.id.md`](./route-permissions.id.md) untuk cara permission
diturunkan dari route, [`menu-management.id.md`](./menu-management.id.md) untuk sisi
menu/sinkronisasi.

## 1. Gejala sebagaimana dilaporkan

| # | Gejala |
|---|---|
| A | Membuat/mengedit role mengembalikan HTTP 200 dengan data role ikut dikembalikan, DataTable langsung menampilkan barisnya, tetapi baris tersebut tidak pernah ada di MySQL. |
| B | Membuat ulang role dengan nama yang sama memicu error constraint `unique`, padahal pembuatan sebelumnya "gagal". |
| C | Menghapus role seperti itu mengembalikan 200 dan baris hilang dari DataTable, namun nama itu bisa dipakai lagi — berulang-ulang, tanpa henti. |
| D | Beberapa request berputar sampai muncul error `Maximum execution time of 30 seconds exceeded`. |
| E | Role/permission apa pun yang memakai guard selain `web` (misalnya `api`) mengembalikan 500 dengan `Error: Class name must be a valid object or a string`. |

Gejala A–C adalah bug yang sama dilihat dari tiga sudut. Kejadiannya tidak menentu
(intermittent): request yang sama bisa tersimpan dengan benar pada percobaan pertama lalu
menghilang pada percobaan berikutnya.

## 2. Fakta lingkungan (sebelum diperbaiki)

- Aplikasi berjalan di atas **Laravel Octane dengan FrankenPHP**
  (`docker/Dockerfile`, `CMD php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=8000`).
- Octane mempertahankan **worker jangka panjang (long-lived)**: proses PHP, container
  aplikasi yang sudah di-boot, dan koneksi PDO/MySQL yang terbuka terus hidup dari banyak
  request, alih-alih dihancurkan setelah tiap request.
- `config/octane.php` memiliki listener Octane `DisconnectFromDatabases` yang
  **di-comment**, sehingga koneksi PDO sengaja dipertahankan antar request.
- `config/auth.php` mendaftarkan tepat satu guard: `web`.

## 3. Akar penyebab 1 — guard non-`web` melempar `Error`, bukan `Exception`

Model `Role` pada Spatie menyelesaikan relasi `users()`-nya melalui guard:

```php
// vendor/spatie/laravel-permission/src/Guard.php
public static function getModelForGuard(string $guard): ?string   // mengembalikan null bila
                                                                  // guard tidak punya provider
```

Karena hanya `web` yang dikonfigurasi, `getModelForGuard('api')` mengembalikan `null`, dan
membangun relasi menghasilkan:

```
Error: Class name must be a valid object or a string
    at Illuminate\Database\Eloquent\Concerns\HasRelationships.php:1052
    at vendor/spatie/laravel-permission/src/Models/Role.php:94
    at vendor/spatie/laravel-permission/src/Traits/HasPermissions.php:46
```

Baris 46 pada `HasPermissions.php` adalah listener `deleting`:

```php
static::deleting(function ($model) {
    ...
    if ($model instanceof Role) {
        $model->users()->detach();   // ← memanggil users() → Error untuk guard tanpa provider
    }
});
```

Detail kritisnya adalah kelas error PHP tersebut:

- `GuardDoesNotMatch`, `PermissionDoesNotExist`, `ValidationException`, … semuanya
  mewarisi `Exception`.
- `Error: Class name must be a valid object or a string` adalah **`Error`** PHP, dan
  `Error` **tidak** mewarisi `Exception`.

## 4. Akar penyebab 2 — `catch (\Exception)` membiarkan `Error` lolos dari transaksi terbuka

Setiap operasi tulis di modul ini berbentuk:

```php
DB::beginTransaction();
try {
    $role->delete();          // ← listener deleting melempar Error seperti di atas
    DB::commit();
    return response()->json([...]);
} catch (\Exception $e) {     // ← menangkap Exception, BUKAN Error
    DB::rollBack();
    ...
}
```

Karena `Error` bukan `Exception`, blok `catch` tidak pernah dijalankan, sehingga
`DB::rollBack()` tidak pernah dijalankan. Transaksi tetap terbuka dan penghitung transaksi
(transaksi *level*) Laravel di memori untuk koneksi tersebut tetap terangkat.

### Mengapa hal itu menghasilkan tepat gejala A–C

Laravel hanya mengirim `COMMIT` yang sesungguhnya ketika *level* transaksinya turun kembali
ke `1`. Selama level terjebak di atas `1`:

| Aksi | Yang dilihat kode | Yang dilihat MySQL |
|---|---|---|
| `create` | sukses, baris ikut di response | `INSERT` dieksekusi, **tidak pernah di-commit** |
| Reload DataTable | baris dikembalikan | koneksi yang sama membaca datanya sendiri yang belum di-commit |
| Membuat ulang nama yang sama | pelanggaran `unique` | baris yang belum di-commit masih memegang nama itu |
| `delete` | sukses, baris hilang | `DELETE` dieksekusi pada baris yang belum di-commit |
| Koneksi lain apa pun | tidak ada apa-apa | **tidak ada baris sama sekali** |

Itulah sebabnya data tampak nyata bagi browser tetapi tidak pernah sampai ke MySQL, dan
mengapa kegagalannya tidak menentu — sebuah request baru membocorkan transaksi bila kebetulan
mendarat di worker yang koneksi-nya sudah tercemar.

Timeout 30 detik (gejala D) muncul di `storage/logs/laravel-2026-10-17.log` pada
`Illuminate\Database\MySqlConnection.php:53` / `Connection.php:612`, konsisten dengan
pekerjaan berikutnya menumpuk di belakang koneksi yang tertinggal di tengah transaksi.

## 5. Mengapa `optimize:clear` tampak memperbaikinya

`artisan optimize:clear` dijalankan dan sesudahnya semuanya tersimpan dengan benar. Ini
kebetulan yang perlu dipahami, karena perintah tersebut sendiri tidak memperbaiki apa pun:

```
$ php artisan tinker --execute="print_r(Laravel\Octane\ServiceProvider::\$optimizeClearCommands);"
Array ( )
$ php artisan tinker --execute="print_r(Laravel\Octane\ServiceProvider::\$reloadCommands);"
Array ( [octane] => octane:reload )
```

Octane mendaftarkan hook reload-nya lewat `reloads()` (`OctaneServiceProvider.php:196`),
yang **tidak** termasuk `$optimizeClearCommands`. Jadi `optimize:clear` membersihkan cache
bootstrap tetapi tidak mendaur ulang worker. Pemulihan terjadi karena worker kebetulan
didaur ulang pada waktu yang hampir bersamaan — itulah sebabnya terlihat efektif, padahal
seharusnya akan muncul lagi kemudian.

**Perintah pemulihan yang benar adalah `php artisan octane:reload`** (atau
`php artisan reload`, atau merestart container). Membersihkan cache saja tidak akan
melepaskan koneksi yang sudah tercemar.

## 6. Perbaikan yang diterapkan

| # | Perbaikan | File |
|---|---|---|
| 1 | Meng-override `Role::users()` agar mengembalikan relasi kosong yang aman ketika guard tidak punya provider model, sehingga listener `deleting` milik Spatie dan `getRoleUsers()` tidak lagi melempar `Error`. | `app/Models/Role.php` |
| 2 | Semua `catch (\Exception)` di modul diubah menjadi `catch (\Throwable)`, sehingga rollback selalu dijalankan dan transaksi tidak akan pernah bocor lagi. | `RolePermissionController.php`, `PermissionController.php` |
| 3 | `guard_name` divalidasi terhadap guard yang terdaftar: `Rule::in(array_keys(config('auth.guards')))`. Guard yang tidak terdaftar kini menjadi 422, bukan 500. | `RolePermissionController.php`, `PermissionController.php` |
| 4 | Field guard diubah menjadi dropdown dengan hidden input (pola `.custom-filter-dropdown` yang sama dengan field status pada modul user) yang diisi dari `config('auth.guards')` alih-alih teks bebas, sehingga guard yang tidak valid tidak bisa diketik sama sekali. | `role-permissions/index.blade.php` |
| 5 | Validasi sisi klien kini memeriksa keanggotaan dalam daftar guard terdaftar (dan di-reset pada event `change`), serta pemuatan modal assign-permission kini punya handler `.fail()` sehingga tidak berputar selamanya saat terjadi error. | `role-permissions/index.blade.php` |
| 6 | Memperbaiki header request pada helper AJAX bersama (`headers: headers` alih-alih `{'X-CSRF-TOKEN': headers}` yang salah format). | `public/js/iplat1_crud.js` |
| 7 | Mengaktifkan kembali listener `DisconnectFromDatabases` milik Octane sebagai jaring pengaman, sehingga tidak ada koneksi (dan tidak ada state transaksi yang bocor) yang terbawa ke request berikutnya. | `config/octane.php` |
| 8 | Menambahkan `permissions:prune-invalid-guards` (mendukung `--dry-run`) untuk mendaftar dan menghapus role/permission yang guard-nya tidak terdaftar. | `app/Console/Commands/PruneInvalidGuards.php` |
| 9 | Menambahkan test regresi untuk validasi guard dan alur create/update/delete. | `tests/Feature/RolePermissionTest.php` |
| 10 | Meng-override `Permission::users()` dengan cara yang sama seperti `Role::users()`, sehingga jumlah user–permission langsung tetap aman untuk guard apa pun. | `app/Models/Permission.php` |
| 11 | Daftar permission dan ekspornya kini menampilkan jumlah user, ditopang endpoint baru `GET /ajax/permissions/{id}/users` serta badge yang bisa diklik di tabel. | `PermissionController.php`, `PermissionsExport.php`, `routes/web.php`, `role-permissions/index.blade.php` |
| 12 | Mengonversi 8 sisa `catch (\Exception)` menjadi `catch (\Throwable)` di seluruh codebase (UserController, UserPermissionController, MenuSynchronizer, MenuSyncCommand, AuthController), sehingga transaksi tidak dapat bocor di mana pun. Lihat bagian 9. | lihat bagian 9 |

### Apa yang dilakukan `Role::users()`

```php
public function users(): BelongsToMany
{
    $guard = $this->guard_name ?? config('auth.defaults.guard');
    $model = Guard::getModelForGuard($guard);

    if ($model === null) {
        // Guard tidak punya model provider: kembalikan relasi kosong yang aman.
        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_roles'),
            app(PermissionRegistrar::class)->pivotRole,
            config('permission.column_names.model_morph_key')
        )->whereRaw('1 = 0');
    }

    return parent::users();
}
```

Operasi detach/count karenanya menjadi no-op yang sukses untuk guard yang tidak mungkin
punya user, alih-alih meledak. Relasi kosong dibangun di atas kelas nyata (`User`) sehingga
query-nya terbentuk dengan benar, dan `whereRaw('1 = 0')` menjamin tidak pernah cocok
dengan baris mana pun.

## 7. Pembersihan yang dilakukan

```bash
php artisan permissions:prune-invalid-guards --dry-run   # periksa dulu
php artisan permissions:prune-invalid-guards             # hapus
php artisan permission:cache-reset
```

Sebelum:

```
roles: SUPER ADMIN, DEVELOPER, MANAGER, TEAM LEADER, EMPLOYEE   (web)
       dummy, api                                               (api)  ← rusak
permissions: 30 generated + 1 manual, semua (web)
```

Sesudah:

```
roles: SUPER ADMIN, DEVELOPER, MANAGER, TEAM LEADER, EMPLOYEE,
       assignperm, web dari api2                                (semua web)
permissions: 30 generated + development.command.test            (semua web)
```

Tabel `roles` dan `role_has_permissions` di-dump ke
`agent-plan/backups/roles_backup.sql` sebelum penghapusan.

Dua baris adalah **data uji, bukan cacat**, dan dibiarkan untuk diputuskan pemiliknya:
`assignperm` (role) dan `development.command.test` (permission manual). Keduanya memakai
guard `web` yang valid, sehingga tidak berbahaya dan bisa dihapus lewat UI.

## 8. Cara verifikasi

```bash
# sintaks + gaya + test
php artisan test
./vendor/bin/pint --test

# pemeriksaan milik insiden ini
php artisan permissions:prune-invalid-guards --dry-run      # harapkan 0 role, 0 permission

# bila data tampak "nyangkut" atau hantu lagi, daur ulang worker — jangan hanya bersihkan cache
php artisan octane:status
php artisan octane:reload

# pantau koneksi yang terjebak di tengah transaksi
mysql -e "SELECT trx_id, trx_state, trx_started FROM information_schema.innodb_trx;"
```

`tests/Feature/RolePermissionTest.php` mencakup:

- membuat role dengan guard yang tidak terdaftar mengembalikan **422** (sebelumnya 500),
- create → update → delete role `web` mengembalikan **200** dan barisnya benar-benar
  tersimpan.

## 9. Kebijakan transaksi — selalu `catch (\Throwable)`

**Kebijakan (diadopsi setelah insiden ini).** Kode apa pun yang memanggil
`DB::beginTransaction()` harus menangkap `\Throwable`, bukan `\Exception`. Ke-14 situs
transaksi di codebase ini sudah mematuhinya.

```php
DB::beginTransaction();
try {
    // ...
    DB::commit();
    return response()->json([...]);
} catch (\Throwable $e) {
    DB::rollBack();                                   // tanpa syarat, pernyataan pertama
    Log::error('...', ['message' => $e->getMessage()]);
    return response()->json([...], 500);
}
```

### 9.1 Kenapa `\Throwable`

Tugas catch itu bukan mengklasifikasi error — melainkan mengembalikan state transaksi.
Rollback adalah *cleanup*, bukan keputusan, dan cleanup harus menutup semua jalur keluar.
Dengan `catch (\Exception)`, rollback menjadi **optional**, karena `Error` PHP (`TypeError`,
`ArgumentCountError`, `Class name must be a valid object or a string`) tidak mewarisi
`Exception`. Opsionalitas itulah persis cacat yang dijelaskan di bagian 4.

Laravel juga melakukan hal yang sama. `ManagesTransactions::transaction()` menangkap
`Throwable` dua kali — mengelilingi callback (baris 41) dan mengelilingi jalur commit
(baris 58) — dan subsistem exception-nya pun bertipe `Throwable`
(`shouldReport(Throwable $e)`).

### 9.2 Rollback dulu, tanpa syarat

Rollback adalah pernyataan pertama di dalam catch dan tidak pernah bergantung pada tipe
error. State dipulihkan apa pun yang salah. Baru *setelah* itu Anda boleh bercabang.

### 9.3 Jangan bercabang `Exception` vs `Error`

Itu biasanya sumbu yang salah. `Error` selalu berarti bug dan tidak pernah kondisi bisnis,
sementara `Exception` mencampur kegagalan yang benar-benar diharapkan (`QueryException` pada
pelanggaran constraint, `GuardDoesNotMatch`) dengan yang tidak terduga — sehingga
`instanceof \Exception` tidak banyak memberi informasi.

Yang Anda butuhkan sebenarnya adalah **kegagalan yang diharapkan vs bug**, dan itu
diekspresikan dengan *kelas exception spesifik*:

```php
} catch (\Throwable $e) {
    DB::rollBack();                                   // 1. tanpa syarat

    if ($e instanceof QueryException) {               // 2. hanya bila behaviour beda
        Log::warning('...', ['message' => $e->getMessage()]);
        return response()->json([...], 422);          //    pesan ramah, tidak men-paging dev
    }

    Log::error('...');                                // 3. fall-through
    report($e);                                       //    semua Error jatuh ke sini sendiri
    return response()->json([...], 500);
}
```

Setiap `Error` mencapai langkah 3 tanpa pemeriksaan `instanceof \Error` apa pun. Kalau Anda
tetap menginginkan cabang `Error` eksplisit, satu-satunya perbedaan yang layak adalah level
log dan apakah error itu dilaporkan — bukan rollback (selalu), dan bukan status HTTP
(keduanya 500).

Differensiasi berbasis kelas seperti itu tempatnya di exception handler Laravel: `$levels`,
`$dontReport`, dan `reportable()` yang didaftarkan di `withExceptions()` pada
`bootstrap/app.php` — bukan di catch transaksi.

### 9.4 Dua aturan yang menyertainya

- **Jangan pernah mengembalikan sukses dari `catch (\Throwable)`.** Sebuah 200 yang keluar
  dari tulisan yang gagal justru membuat rollback tidak terlihat oleh pemanggil, dan itulah
  yang memicu kebingungan pada awal penyelidikan ini.
- **Kalau Anda memakai `DB::transaction(fn () => ...)` sebagai gantinya, jangan memanggil
  `DB::rollBack()` lagi di catch Anda sendiri.** Helper itu sudah melakukan rollback; panggilan
  kedua akan melempar error atau mengganggu transaksi luar.

### 9.5 Alternatif terbaik: biarkan framework yang memiliki transaksi

`DB::transaction($callback, $attempts)` menangkap `Throwable` di dalamnya, melakukan rollback,
dan mencoba ulang deadlock. Tangkap di luar closure hanya untuk membentuk response:

```php
try {
    $result = DB::transaction(fn () => /* ... */);
    return response()->json([...]);
} catch (\Throwable $e) {
    return response()->json([...], 500);              // rollback sudah dilakukan
}
```

### 9.6 Status saat ini

- **14** lokasi pemanggilan `DB::beginTransaction()` — semuanya dijaga `catch (\Throwable)`.
- **15** `catch (\Throwable)` secara total; tambahan satu adalah `MenuSyncCommand`, yang tidak
  memiliki transaksi sendiri tetapi tidak boleh fatal pada `Error` yang lolos dari
  `MenuSynchronizer::sync()`.
- **1** pemakaian `DB::transaction()` (`permissions:prune-invalid-guards`).
- **0** `catch (\Exception)` tersisa di `app/`.

## 10. Aturan untuk pekerjaan berikutnya

1. **Jangan pernah menangkap hanya `\Exception` di sekitar tulisan database** — lihat
   kebijakan di bagian 9. Sebuah PHP `Error` yang lolos dari blok `try` yang memanggil
   `DB::beginTransaction()` akan membocorkan transaksi secara diam-diam selama umur seorang
   worker Octane.
2. **Asumsikan request ini bukan satu-satunya pemakai koneksi.** Di bawah Octane, sebuah
   variabel yang menyimpan state (penghitung transaksi, cache statis, handle terbuka)
   bertahan ke request berikutnya. Segala sesuatu yang bersifat request-scope harus di-reset.
3. **Validasi field tipe-enum terhadap konfigurasi, bukan daftar hardcode.** Guard berasal
   dari `config('auth.guards')`; menambahkan guard ke konfigurasi sudah cukup untuk
   mendukungnya.
4. **Sebuah "perbaikan" yang berhasil dengan membersihkan cache adalah kebetulan sampai Anda
   bisa menyebutkan mekanismenya.** `optimize:clear` tidak me-reload worker Octane;
   `octane:reload` melakukannya.
5. **Gejala "data ada di UI tetapi tidak ada di database" hampir selalu berarti transaksi
   yang belum di-commit, bukan database yang salah.** Periksa
   `information_schema.innodb_trx` dan log worker terlebih dahulu.

## 11. Ketidakpastian

- Bug #2/#5 (yang menggantung dan pembuatan hantu) tidak pernah bisa direproduksi sesuai
  permintaan. Diagnosis bertumpu pada jejak stack `Error` yang tercatat, jejak timeout 30
  detik, jalur-jalur kode di atas, dan fakta bahwa gejala A–C sepenuhnya dijelaskan oleh
  mekanisme transaksi yang bocor. Tidak mungkin mengamati baris `innodb_trx` yang terjebak
  pada saat pemeriksaan, karena worker sudah didaur ulang terlebih dahulu.
- Alasan pasti timeout menumpuk pada pukul 20:42–20:52 waktu setempat tidak dipastikan.
  Transaksi terbuka pada koneksi bersama sudah cukup untuk menjelaskannya, tetapi query
  pem-blocking yang spesifik tidak tertangkap.
