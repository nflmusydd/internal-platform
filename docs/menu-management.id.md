# Manajemen Menu (Internal Platform)

Dokumen ini menjelaskan bagaimana menu sidebar aplikasi dikelola: katalog master, perintah
`menu:sync`, dan cara menambah atau mengubah menu. Fokusnya pada mekanisme sync dan
katalog. Untuk konvensi komponen halaman/UI, lihat
[`iplat1-components.id.md`](./iplat1-components.id.md).

## 1. Prinsip

Data menu **digerakkan oleh kode, bukan oleh UI**:

- Sumber kebenaran tunggal adalah array katalog di
  `App\Support\MenuSynchronizer::catalog()`.
- Tabel `menus` di database hanyalah proyeksi dari katalog tersebut.
- `php artisan menu:sync` adalah **satu-satunya** jalur yang menulis data menu.
- Halaman admin di `sysadmin/menus` bersifat **read-only** (tanpa create/update/delete).

Ini menghindari masalah desync yang akan terjadi jika menu bisa diedit dari UI sementara
juga dikelola oleh seeder: sync ulang akan diam-diam mengembalikan perubahan user.

## 2. Arsitektur

| Kebutuhan | Lokasi |
|---|---|
| Katalog master + logika sync + validasi | `app/Support/MenuSynchronizer.php` |
| Perintah Artisan (`menu:sync`) | `app/Console/Commands/MenuSyncCommand.php` |
| Model | `app/Models/Menu.php` |
| Tabel | `menus` (migrasi `..._create_menus_table.php`) |
| Endpoint admin | `app/Http/Controllers/Sysadmin/MenuController.php` |
| Export | `app/Exports/MenusExport.php` |
| View admin | `resources/views/sysadmin/menus/index.blade.php` |
| Route | `routes/web.php` → `sysadmin.menus.*` |
| Composer sidebar | `app/Providers/AppServiceProvider.php` |
| View sidebar | `resources/views/layouts/iplat1_layout1.blade.php` |

`MenuSynchronizer` dibuat sebagai service biasa agar logikanya tetap testable dan bisa
dipakai ulang (mis. oleh command lain), sedangkan command hanya lapisan presentasi tipis.

## 3. Tabel `menus`

| Kolom | Keterangan |
|---|---|
| `id` | PK auto-increment (internal, disembunyikan dari JSON via `#[Hidden(['id'])]`) |
| `ulid` | identifier publik |
| `slug` | unik, key stabil yang dipakai katalog |
| `parent_id` | self-FK nullable (`menus.id`, `onDelete: cascade`) |
| `name_en`, `name_id` | label dua bahasa |
| `route_name` | nama route Laravel; `null` untuk folder/parent |
| `icon` | path view blade, mis. `resources/views/layouts/menu_icons/sysadmin.blade.php` |
| `order` | urutan |
| `is_active` | boolean |
| `permission_name` | nama permission Spatie (disiapkan untuk filter berbasis permission) |
| `created_at`, `created_by`, `updated_at`, `updated_by` | kolom audit |

Constraint:

- `slug` unik.
- `unique(parent_id, order)` — `order` harus unik **di antara sibling** (parent yang sama).
- Menghapus parent akan cascade ke childs-nya.

## 4. Cara kerja sync

`MenuSynchronizer::sync()` membandingkan katalog dengan DB dan mengembalikan array hasil:

```php
[
    'inserted' => [...slug],
    'updated'  => [...slug],
    'skipped'  => [...slug],
    'deleted'  => [...slug],
]
```

Perilaku per entri katalog:

- **Insert** — slug belum ada di DB.
- **Update** — slug sudah ada tetapi ada field yang berbeda.
- **Skip** — slug sudah ada dan tidak ada perubahan.
- **Delete** — setiap baris DB yang slug-nya **tidak** ada di katalog akan dihapus.

Field yang dibandingkan: `parent_id`, `name_en`, `name_id`, `route_name`, `icon`, `order`,
`is_active`, `permission_name`.

Seluruh proses berjalan dalam transaksi; jika gagal akan rollback dan dicatat ke
`Log::error('MENU SYNC: Process Failed')`.

### 4.1 Validasi katalog

Sebelum menyentuh DB, `validateCatalog()` menegakkan:

1. **`parent_slug` harus ada di katalog.** Typo akan diam-diam membuat menu root jika tidak
   divalidasi.
2. **Menu yang punya child tidak boleh mendefinisikan `route_name`.** Menu seperti itu adalah
   folder/dropdown; sidebar memang mengabaikan `route_name` pada parent, sehingga aturan ini
   menjaga data dan rendering tetap konsisten.
3. **`order` harus integer dan unik di antara sibling.** Scope sibling adalah `parent_slug`
   (`root` untuk menu top-level), jadi dua parent berbeda boleh sama-sama memakai
   `order = 1`. Duplikat akan throw dengan pesan yang menyebut kedua slug.
4. **Rantai parent tidak boleh membentuk siklus (cycle).**

Katalog tidak valid akan throw dan sync tidak dijalankan.

### 4.2 Contoh output

```text
=========== MENU SYNC RESULT ===========
Inserted (1):
sysadmin/reports
Updated (0):

Skipped (8):
sysadmin, sysadmin/menus, sysadmin/users, ...
Deleted (0):

```

### 4.3 Parent baru dan pengurutan ulang

Ada dua perilaku yang perlu diketahui:

- **Parent baru diproses sebelum child-nya.** Katalog ditelusuri parent-dulu, dan map
  `slug → id` diperbarui saat baris di-insert. Jadi parent baru beserta childs-nya bisa
  ditambahkan dalam satu kali run, bahkan di database kosong.
- **Pengurutan ulang aman.** Karena `order` unik di antara sibling, menukar atau menyisipkan
  di antara sibling bisa sesaat bertabrakan dengan constraint. Sebelum nilai order final
  ditulis, order existing di setiap scope parent yang terdampak digeser dulu ke luar rentang
  katalog, baru nilai final diterapkan. Akibatnya, run kedua berturut-turut tidak mengubah
  apa pun (semua dilaporkan sebagai skipped).

## 5. Menjalankan sync

Host:

```bash
php artisan menu:sync
```

Docker (dari root proyek, file compose ada di `docker/`):

```bash
# (container_name: app)
docker exec app php artisan menu:sync
```

> Menjalankan `php artisan menu:sync` dari host ke database Docker akan gagal dengan
> `getaddrinfo for mysql failed`, karena nama host `mysql` hanya resolve di dalam network
> Docker. Selalu jalankan di dalam container `app`.

## 6. Menambah atau mengubah menu

1. **Edit katalog** di `App\Support\MenuSynchronizer::catalog()` lalu tambah/ubah entri:

   ```php
   [
       'slug' => 'sysadmin/reports',        // unik, key stabil
       'name_en' => 'Reports',
       'name_id' => 'Laporan',
       'route_name' => 'sysadmin.reports.index', // null jika berupa folder
       'icon' => 'resources/views/layouts/menu_icons/sysadmin_reports.blade.php',
       'parent_slug' => 'sysadmin',         // null untuk menu top-level
       'order' => 5,                        // unik di antara sibling
       'is_active' => true,
       'permission_name' => 'view_sysadmin_reports',
   ],
   ```

2. **Pastikan `route_name` adalah route yang terdaftar.** Katalog tidak memvalidasi
   keberadaan route; sidebar memanggil `route($menu->route_name)`, sehingga nama yang tidak
   dikenal akan error saat render.

3. **(Opsional) Tambah partial ikon** pada path yang ditulis di `icon`. Setiap file berisi
   satu tag ikon Bootstrap, mis.:

   ```blade
   {{-- resources/views/layouts/menu_icons/sysadmin_reports.blade.php --}}
   <i class="bi bi-bar-chart-fill"></i>
   ```

   `App\Helpers\LayoutHelper::convertToViewPath()` mengubah path tersimpan menjadi nama view.
   Jika partial tidak ada, sidebar memakai ikon default (`bi-square-fill` untuk parent,
   `bi-dash-lg` untuk child).

4. **Jalankan sync** (lihat bagian 5).

5. **Untuk menghapus menu**, hapus entrinya dari katalog (beserta childs-nya) lalu
   jalankan sync; menu akan dihapus dari DB.

### Catatan urutan

- `order` unik **per parent**, jadi parent dan child boleh sama-sama memakai `order = 1`.
- Jaga `order` tetap berurutan agar tidak membingungkan saat mengubah urutan.
- Menukar atau mengurutkan ulang `order` sibling di katalog didukung; sync akan menggeser
  scope terdampak sementara sehingga constraint unik tidak pernah dilanggar.

## 7. Cara menu dirender (singkat)

- View composer di `AppServiceProvider::boot()` memuat menu top-level aktif beserta child
  aktif yang terurut, lalu membagikannya ke layout sebagai `$menus`.
- Loop sidebar di `iplat1_layout1.blade.php` merender child sebagai dropdown jika menu punya
  child (sub-menu), selain itu sebagai link tunggal.
- Komponen breadcrumb (`components.iplat1.breadcrumb`) juga membaca tabel `menus`,
  berdasarkan slug group.
- Filter sidebar berbasis permission (Spatie `permission_name`) saat ini masih
  dikomentari; semua menu aktif ditampilkan.

## 8. Halaman admin (singkat)

`sysadmin/menus` adalah DataTable read-only ber-indentasi datar yang menampilkan seluruh
hierarki. Mendukung filter (nama, induk, status, rentang created-at dan updated-at) dan
export XLSX, tanpa create/edit/delete. Baris di-flatten di sisi server oleh
`MenuController::flattenTree()` (parent lalu `order`) dan tabel mempertahankan urutan
tersebut (ordering sisi klien dinonaktifkan).

## 9. Checklist

- [ ] Entri ditambah/diubah di `MenuSynchronizer::catalog()`.
- [ ] `slug` unik; `parent_slug` ada di katalog.
- [ ] Menu parent punya `route_name = null`; menu leaf menunjuk route yang terdaftar.
- [ ] `order` unik di antara sibling.
- [ ] Partial ikon dibuat (opsional).
- [ ] `php artisan menu:sync` dijalankan di dalam container `app`.
- [ ] Hasil menunjukkan Inserted/Updated/Skipped/Deleted sesuai harapan.
