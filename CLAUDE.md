# CLAUDE.md — Apotek Website

Dokumen ini adalah panduan kerja untuk Claude Code saat mengembangkan proyek **Website-Apotek**. Baca seluruh dokumen ini sebelum mengubah kode. Tujuan utamanya adalah mengembangkan proyek yang sudah ada secara bertahap menjadi website apotek yang modern, rapi, aman, responsif, dan benar-benar berfungsi — bukan sekadar mengganti tampilan.

---

## 1. Konteks proyek saat ini

Proyek saat ini adalah aplikasi Laravel dengan:

- **Backend:** PHP `^8.3`, Laravel `^13.8`.
- **Frontend:** Blade templates, Tailwind CSS `^4`, Vite `^8`, JavaScript ES modules.
- **Database default:** SQLite (lihat `.env.example`).
- **Autentikasi:** Laravel session authentication buatan sendiri melalui `AuthController`.
- **Entitas utama:** `Medicine` dengan kolom `name`, `price`, `description`, `expired`, `medicine_image`.
- **Halaman yang sudah ada:** beranda (`/`), about (`/about`), halaman obat (`/obat`), detail obat, tambah/edit/hapus obat, login, register, serta halaman `/page2`.

Struktur penting:

```text
app/Http/Controllers/
  AuthController.php
  FirstController.php
  MedicineController.php
app/Models/
  User.php
  Medicine.php
  Obat.php
  Category.php
database/migrations/
resources/views/
  auth/
  components/
  medicine/
  index.blade.php
  about.blade.php
resources/css/app.css
resources/js/app.js
routes/web.php
```

**Jangan menganggap fitur yang terlihat di UI sudah berfungsi.** Contohnya, tombol `Add to cart` di halaman detail saat ini hanya tombol visual dan belum memiliki backend keranjang.

---

## 2. Visi produk

Bangun website apotek yang terasa seperti produk sungguhan dengan dua area utama:

1. **Etalase publik / pelanggan:** beranda, katalog obat, pencarian, filter, detail produk, informasi apotek, serta alur menghubungi apotek.
2. **Dashboard pengelola / admin:** ringkasan data, CRUD obat, pengelolaan kategori, stok, dan informasi produk.

Prioritaskan pengalaman katalog dan pengelolaan inventaris terlebih dahulu. Jangan membuat checkout, pembayaran online, konsultasi medis, resep digital, atau klaim layanan kesehatan tanpa kebutuhan dan aturan bisnis yang jelas. Website ini bukan alat diagnosis dan tidak boleh memberi rekomendasi dosis atau pengobatan.

### Prinsip produk

- Bahasa antarmuka utama: **Bahasa Indonesia**.
- Mata uang: **Rupiah (IDR)** dengan format `Rp 25.000`.
- Format tanggal: `dd MMM yyyy` atau format Indonesia yang konsisten.
- Desain mobile-first, mudah digunakan di ponsel maupun desktop.
- Konten harus jelas, realistis, dan tidak menggunakan lorem ipsum.
- Jangan membuat data stok, harga, rating, diskon, alamat, jam operasional, atau klaim apotek fiktif seolah-olah fakta. Jika belum tersedia, gunakan placeholder yang jelas atau minta konfirmasi.

---

## 3. Temuan penting dari kode yang ada

Periksa dan bereskan masalah berikut sebelum menambahkan banyak fitur. Verifikasi ulang kondisi file sebelum mengedit karena beberapa hal mungkin sudah berubah.

### 3.1 Routing dan otorisasi

- Di `routes/web.php`, route CRUD `/obat` saat ini dimasukkan ke middleware `guest`. Ini terbalik untuk area pengelolaan: pengunjung yang belum login justru dapat mengakses route tersebut, sementara pengguna yang login dapat ditolak.
- Pisahkan route publik dan route pengelolaan. Katalog dan detail publik boleh diakses pengunjung. Operasi create, store, edit, update, dan delete harus memerlukan autentikasi dan otorisasi yang sesuai.
- Jangan menganggap semua user yang login otomatis admin. Terapkan role/authorization yang eksplisit untuk tindakan pengelolaan. Gunakan kolom `role` yang memang sudah ada pada migration `users`, atau buat perubahan migration yang aman dan konsisten.
- `GET /obat/delete/{id}` saat ini menghapus data. Ubah menjadi `DELETE` route dengan CSRF protection, misalnya melalui `Route::resource` atau route bernama yang konsisten.
- Gunakan route names dan helper `route()` di Blade, bukan URL hard-coded yang tersebar.
- Tambahkan `auth`, `guest`, dan middleware role sesuai kebutuhan. Jangan membuat middleware custom jika policy/gate atau middleware bawaan sudah cukup.

### 3.2 Model User dan autentikasi

- `User` memakai attribute `#[Fillable(['name', 'email', 'password', 'user'])]`, sedangkan controller mengirim field `role`. Ini tidak konsisten. Selaraskan mass assignment dengan kolom yang benar; jangan membuka mass assignment untuk field sensitif secara berlebihan.
- Periksa `AuthController`: setelah login lakukan session regeneration; setelah logout lakukan logout, invalidasi session, dan regenerate CSRF token.
- Validasi input register dan login dengan pesan Bahasa Indonesia yang jelas. Gunakan password hashing Laravel; jangan menyimpan password plaintext atau melakukan hash ganda.
- Pertahankan old input untuk field non-sensitif, tampilkan error per field, dan jangan pernah mengisi ulang password.
- Hindari memberikan role admin melalui form registrasi publik. Role admin hanya boleh ditetapkan melalui seeder atau proses administratif yang aman.
- Pastikan redirect setelah login sesuai role dan intended URL; hindari redirect yang tidak tervalidasi.

### 3.3 MedicineController dan data obat

- `MedicineController::store()` saat ini menganggap file gambar selalu ada, memindahkan file dengan nama asli ke `public/gambars`, dan tidak memvalidasi file. Ini rawan error, benturan nama, dan upload berbahaya.
- Validasi semua field di server: nama, deskripsi, harga, tanggal kedaluwarsa, dan gambar. Batasi tipe gambar yang diterima (misalnya JPEG, PNG, WebP) dan ukuran file.
- Gunakan Laravel Storage disk `public` dan `storage:link`, bukan nama file asli langsung di folder publik. Simpan path relatif yang dihasilkan aplikasi.
- Gunakan `findOrFail()` atau route model binding agar ID tidak ditemukan menghasilkan 404 yang benar.
- Saat update, dukung gambar opsional. Hapus gambar lama hanya setelah file baru berhasil disimpan dan perubahan database berhasil.
- Saat delete, hapus file terkait secara aman setelah record dihapus/transaction berhasil. Jangan pernah menerima path file arbitrer dari request.
- Gunakan nama field yang jelas (`name`, `description`, `price`, `expired`, `image`) dan hentikan pola nama input `a`, `b`, `c`, `d`.
- Gunakan `Request` validation dan `$validated`, bukan mengambil semua input mentah.
- Tambahkan pagination pada katalog/dashboard; hindari `Medicine::all()` saat data membesar.
- Pastikan harga berupa angka positif dan tipe kolom database sesuai kebutuhan. Jangan mengasumsikan nilai harga valid hanya karena input HTML `number`.

### 3.4 Database dan model

- Terdapat dua tabel yang tampak merepresentasikan obat: `obats` dan `medicines`. Aplikasi utama saat ini menggunakan model `Medicine` dan tabel `medicines`. Jangan menggabungkan atau menghapus tabel lama tanpa memeriksa apakah ada data atau dependensi.
- Migration `create_categories_table.php` saat ini membuat kolom `name`, `price`, `description`, `expired`, `medicine_image`, dan `category_id` yang menjadi foreign key ke tabel `categories` itu sendiri. Ini tidak sesuai untuk tabel kategori normal dan berpotensi gagal karena foreign key wajib tanpa nilai default.
- Rancang tabel `categories` secara benar (misalnya `id`, `name` unik, `slug` opsional, timestamps), lalu relasikan `medicines.category_id` ke `categories.id` melalui migration baru yang aman.
- Jangan mengedit migration lama yang sudah mungkin dijalankan pada database pengguna. Untuk perubahan skema yang sudah pernah diterapkan, buat migration baru. Migration lama boleh diperbaiki hanya jika dipastikan belum pernah dipakai pada lingkungan mana pun.
- Tambahkan foreign key, index, unique constraint, dan nullable/default yang tepat.
- Definisikan relasi Eloquent `Medicine belongsTo Category` dan `Category hasMany Medicine` bila kategori diterapkan.
- Buat seeder kategori dan data demo yang jelas ditandai sebagai data contoh. Jangan seed akun admin dengan password lemah atau kredensial yang ditampilkan di UI.

### 3.5 Blade dan tampilan

- `index.blade.php` memiliki struktur `@foreach` yang tampak menutup link/card secara tidak tepat. Rapikan struktur HTML agar setiap item menjadi satu card mandiri.
- Halaman detail memiliki JavaScript yang mencari elemen seperti `nav-toggle-label` dan elemen tab/reviews yang belum tentu ada. Hapus script yang tidak relevan atau lindungi akses elemen agar tidak menyebabkan error.
- Navbar memiliki link login `href="#"`, link halaman demo `/page2`, dan menu mobile yang belum berfungsi. Perbaiki navigasi, tampilkan status auth, dan buat menu mobile benar-benar interaktif serta accessible.
- Halaman login/register masih memakai teks template “Get in touch” dan tombol “Send Message”. Ganti dengan copy yang sesuai.
- Terapkan satu layout Blade utama dan komponen reusable untuk navbar, footer, alert, form field, badge, dan card bila memang mengurangi duplikasi.
- Gunakan `@csrf`, `@method('PUT')` / `@method('DELETE')`, escaping Blade `{{ }}`, dan jangan gunakan `{!! !!}` untuk input user kecuali sudah disanitasi secara tepat.
- Pastikan semua gambar memiliki alt text yang bermakna; gambar dekoratif menggunakan alt kosong.
- Pastikan semua halaman memiliki title, meta description yang relevan, landmark HTML, label form, focus state, dan kontras warna memadai.

### 3.6 Asset dan konfigurasi

- `index.blade.php` merujuk `/images/doodles.png`, tetapi file yang terlihat di arsip adalah `public/images/image.png`. Verifikasi asset dan gunakan path yang benar atau sediakan fallback.
- Pastikan asset CSS/JS dimuat konsisten melalui Vite. Hindari memuat Vite CSS berulang kali dari komponen navbar jika layout sudah memuatnya.
- Hindari ketergantungan CDN yang tidak perlu. Jika memakai library pihak ketiga, tambahkan melalui package manager dan jelaskan alasannya.

---

## 4. Arah desain UI/UX

Buat visual yang profesional, bersih, modern, dan relevan untuk apotek. Hindari tampilan template generik yang penuh gradient, efek blur, animasi berlebihan, atau card yang semuanya memiliki gaya sama.

### Identitas visual

- Nuansa utama: putih bersih, hijau/teal sebagai warna kesehatan, dan slate/abu-abu untuk teks.
- Gunakan satu warna primer dan satu warna aksen secara konsisten. Pastikan kontras teks memenuhi aksesibilitas.
- Tipografi modern dan mudah dibaca; skala heading/body konsisten.
- Radius, shadow, spacing, border, dan ukuran kontrol harus konsisten melalui design tokens Tailwind.
- Animasi sederhana dan singkat; hormati `prefers-reduced-motion`.

### Beranda publik

Pertimbangkan susunan berikut, tanpa memaksakan semua bagian jika kontennya tidak tersedia:

1. Navbar dengan logo/nama apotek, link katalog, tentang, dan tombol login/dashboard sesuai status pengguna.
2. Hero section dengan headline yang jelas, deskripsi singkat, CTA ke katalog, serta visual produk/farmasi yang relevan.
3. Search bar katalog yang mudah ditemukan.
4. Kategori obat (hanya jika kategori sudah memiliki data).
5. Produk pilihan/produk terbaru dari database — bukan data hard-coded.
6. Section keunggulan layanan yang hanya berisi klaim yang dapat dibuktikan.
7. Informasi kontak/lokasi/jam operasional hanya jika datanya telah diberikan.
8. Footer yang ringkas dengan navigasi dan informasi legal.

### Katalog dan detail obat

- Grid responsif, card konsisten, gambar rasio tetap dengan `object-cover` atau `object-contain` sesuai jenis gambar.
- Tampilkan nama, harga, kategori, dan informasi ringkas yang tersedia.
- Tambahkan pencarian nama/deskripsi, filter kategori, sorting yang berguna, dan pagination server-side.
- Sediakan empty state, loading state jika ada interaksi asynchronous, dan error state.
- Detail obat menampilkan informasi yang memang tersimpan. Tanggal kedaluwarsa harus diformat dengan benar dan tidak boleh disembunyikan jika relevan.
- Jangan menambahkan tombol beli/keranjang yang tidak memiliki alur backend nyata. Jika belum dibangun, jangan tampilkan sebagai fitur aktif.

### Dashboard admin

- Layout dashboard terpisah dari etalase publik.
- Sidebar atau navigation yang responsif, header dengan identitas pengguna, breadcrumb, dan tombol aksi yang jelas.
- Ringkasan jumlah obat, kategori, dan indikator stok hanya jika data pendukung sudah ada.
- Tabel responsif atau tampilan list di layar kecil; sediakan pencarian, filter, pagination, empty state, dan konfirmasi sebelum penghapusan.
- Form create/edit memiliki validasi inline, preview gambar, helper text, dan feedback sukses/gagal.
- Jangan membuat angka statistik palsu. Hitung dari database.

### Responsive dan accessibility

- Uji lebar layar sekitar 360px, 768px, 1024px, dan desktop lebar.
- Navigasi dapat dioperasikan dengan keyboard dan memiliki `aria-label` / `aria-expanded` yang benar.
- Jangan mengandalkan warna saja untuk menyampaikan status.
- Tombol memiliki state hover, focus-visible, active, disabled, dan ukuran tap target yang nyaman.
- Gunakan semantic HTML dan urutan heading yang benar.

---

## 5. Fitur yang direkomendasikan

Kerjakan sesuai prioritas dan jangan menambahkan semua fitur sekaligus.

### Prioritas P0 — fondasi dan keamanan

- Benahi route publik vs route yang memerlukan login/role.
- Benahi autentikasi, authorization admin, logout, dan validasi.
- Amankan CRUD obat, upload gambar, dan delete.
- Rapikan migration/model yang bermasalah.
- Tambahkan tests untuk akses route, validasi, CRUD, dan autentikasi.

### Prioritas P1 — pengalaman inti

- Redesign beranda dan navbar.
- Katalog obat publik dengan search, filter kategori, sorting, dan pagination.
- Halaman detail obat yang informatif.
- Dashboard pengelolaan obat dengan CRUD lengkap.
- Flash messages dan validation errors yang konsisten.
- Kategori obat dengan relasi database yang benar.

### Prioritas P2 — inventaris yang lebih matang

Sebelum implementasi, tentukan aturan bisnis dan struktur database:

- Stok obat (`stock_quantity`) dan status ketersediaan.
- Minimum stock alert.
- Kode/SKU obat yang unik bila diperlukan.
- Riwayat perubahan stok (stock movements) jika perlu audit.
- Filter obat mendekati kedaluwarsa dan obat kedaluwarsa.
- Dashboard statistik berdasarkan query database.

Jangan sekadar menambah field stok tanpa aturan bagaimana stok berubah dan siapa yang boleh mengubahnya.

### Prioritas P3 — fitur tambahan opsional

- Profil pengguna dan pengaturan akun.
- Activity log untuk tindakan admin penting.
- Export CSV untuk data inventaris jika diperlukan.
- Notifikasi internal untuk stok rendah/kedaluwarsa.
- SEO dasar, sitemap, robots, dan metadata halaman.

Fitur transaksi, pembayaran, resep, dan data kesehatan pribadi memerlukan analisis keamanan, privasi, regulasi, serta alur bisnis tersendiri. Jangan mengimplementasikannya sebagai tambahan kecil.

---

## 6. Arsitektur dan standar implementasi

- Pertahankan Laravel + Blade + Tailwind + Vite. Jangan migrasi ke React, Vue, Next.js, Inertia, Livewire, atau framework lain tanpa alasan kuat dan persetujuan eksplisit.
- Gunakan pola Laravel yang idiomatis: named routes, route model binding, Form Request bila validasi mulai kompleks, Eloquent relationships, policies/gates, pagination, storage, dan database transactions bila diperlukan.
- Controller harus tipis. Pindahkan validasi kompleks ke Form Request dan logika domain yang benar-benar kompleks ke service/action class. Jangan membuat lapisan abstraksi untuk operasi sederhana.
- Gunakan dependency yang sudah ada sebelum menambah package baru.
- Jangan menambahkan API, SPA, queue, Redis, atau service eksternal kecuali ada kebutuhan nyata.
- Gunakan konfigurasi dari `.env`; jangan commit secret, token, credential, atau file `.env`.
- Jangan mengubah versi dependency utama hanya untuk mengejar versi terbaru. Periksa kompatibilitas PHP/Laravel/Node terlebih dahulu.
- Gunakan transaksi database untuk operasi multi-step yang harus atomik.
- Gunakan eager loading untuk menghindari N+1 query.
- Gunakan `Carbon`/casts untuk tanggal, dan helper format harga terpusat bila dipakai di banyak tempat.
- Jangan menaruh business logic penting hanya di JavaScript atau validasi browser.

### Konvensi kode

- Ikuti PSR-12 dan Laravel Pint.
- Nama class `PascalCase`, method/variable `camelCase`, konstanta `UPPER_SNAKE_CASE`.
- Nama route jelas dan konsisten, misalnya `medicines.index`, `medicines.show`, `admin.medicines.create`.
- Gunakan nama input yang sama dengan atribut domain, bukan `a`, `b`, `c`, `d`.
- Hindari duplikasi markup dan logic, tetapi jangan membuat komponen terlalu kecil tanpa manfaat.
- Komentar menjelaskan alasan/aturan yang tidak jelas, bukan mengulang kode.

---

## 7. Keamanan minimum

- Semua operasi mutasi harus menggunakan POST/PUT/PATCH/DELETE dengan CSRF protection.
- Otorisasi dilakukan di server pada setiap aksi; menyembunyikan tombol di UI bukan kontrol keamanan.
- Validasi dan normalisasi seluruh input di server.
- Gunakan mass-assignment allowlist yang ketat.
- Batasi upload berdasarkan MIME/type dan ukuran; jangan percaya ekstensi atau nama file dari browser.
- Simpan upload melalui Laravel Storage; jangan mengeksekusi file upload.
- Jangan menampilkan stack trace, SQL error, atau detail internal pada production.
- Hindari menyisipkan data session ke JavaScript tanpa escaping yang aman. Gunakan `@js()` jika memang perlu memasukkan nilai ke script.
- Gunakan rate limiting untuk login/register bila sesuai.
- Jangan mencatat password, token, atau data sensitif ke log.
- Pastikan akun biasa tidak dapat mengubah role dirinya sendiri atau pengguna lain.
- Jika ada data nyata pengguna/pelanggan, minimalkan data yang dikumpulkan dan jangan menampilkan data pribadi tanpa kebutuhan.

---

## 8. Testing dan quality gate

Sebelum menyatakan pekerjaan selesai:

1. Jalankan pemeriksaan syntax dan formatting yang tersedia.
2. Jalankan test suite (`php artisan test`).
3. Jalankan build frontend (`npm run build`).
4. Tambahkan/ubah test untuk setiap fitur atau bug penting.
5. Periksa route dengan `php artisan route:list`.
6. Pastikan migration dapat dijalankan pada database development yang bersih. Jangan menjalankan `migrate:fresh` pada database yang berisi data penting.
7. Uji alur manual: guest membuka katalog, guest ditolak dari dashboard, user biasa tidak dapat melakukan aksi admin, admin CRUD obat, upload gambar valid/invalid, pencarian, filter, pagination, dan logout.
8. Periksa responsive layout, console browser, broken images, link mati, dan error log.

Minimal test cases:

- Guest dapat melihat katalog dan detail produk.
- Guest tidak dapat mengakses halaman admin.
- User non-admin tidak dapat membuat/mengubah/menghapus obat.
- Admin dapat membuat, melihat, mengubah, dan menghapus obat.
- Field wajib dan harga tidak valid ditolak.
- Upload file non-gambar atau terlalu besar ditolak.
- Gambar lama tidak hilang jika update gagal.
- ID yang tidak ada menghasilkan 404.
- Search/filter/pagination menghasilkan data yang benar.
- Registrasi tidak dapat menetapkan role admin.

Jika command tidak dapat dijalankan karena dependency/environment belum tersedia, laporkan apa yang belum bisa diverifikasi. Jangan mengklaim test/build berhasil jika belum benar-benar dijalankan.

---

## 9. Cara bekerja untuk Claude Code

Ikuti urutan ini setiap kali mengerjakan proyek:

1. Baca `CLAUDE.md`, `README.md`, `composer.json`, `package.json`, `routes/web.php`, migration, model, controller, dan view terkait.
2. Periksa `git status` dan perubahan yang sudah dibuat pengguna. Jangan menimpa perubahan yang belum di-commit.
3. Petakan alur halaman dan data sebelum mengedit.
4. Sampaikan rencana singkat berisi file yang akan diubah dan risiko migration.
5. Kerjakan satu kelompok fitur yang selesai secara end-to-end, bukan mengubah banyak halaman setengah jadi.
6. Pertahankan kompatibilitas dengan struktur proyek yang ada.
7. Jalankan tests/build yang relevan setelah perubahan.
8. Periksa diff akhir dan hapus kode, import, route, asset, atau script yang tidak lagi digunakan.
9. Berikan ringkasan perubahan, file utama yang diubah, command yang dijalankan, hasil test, dan pekerjaan yang masih tertunda.

### Aturan penting

- Jangan menghapus fitur/file hanya karena terlihat tidak dipakai sebelum menelusuri referensinya.
- Jangan mengubah atau menghapus data database pengguna.
- Jangan menjalankan destructive command (`migrate:fresh`, `db:wipe`, `rm -rf`, reset git, dan sejenisnya) tanpa persetujuan eksplisit.
- Jangan membuat migration yang merusak data lama. Gunakan migration baru dan jelaskan langkah deployment.
- Jangan mengarang kredensial, data bisnis, identitas apotek, atau konten medis.
- Jangan mengklaim fitur selesai jika tombol/route/backend-nya belum berfungsi.
- Jika menemukan konflik requirement, bug pada desain awal, atau solusi yang berisiko, jelaskan secara kritis dan usulkan opsi yang lebih aman.
- Utamakan perubahan minimal yang benar, maintainable, dan mudah dipahami daripada kompleksitas yang tidak dibutuhkan.

---

## 10. Rencana implementasi yang disarankan

Kerjakan dalam milestone berikut. Selesaikan dan verifikasi milestone sebelumnya sebelum melanjutkan.

### Milestone 1 — Audit dan stabilisasi

- Periksa seluruh route, controller, model, migration, view, asset, dan test.
- Perbaiki otorisasi, autentikasi, validasi, upload, delete, dan bug database.
- Tambahkan tests dasar.
- Dokumentasikan keputusan migration dan cara menjalankan proyek.

**Definition of done:** route aman, CRUD bekerja, validasi berjalan, upload aman, test dan build lulus.

### Milestone 2 — UI foundation dan public storefront

- Buat layout Blade utama dan design tokens.
- Redesign navbar/footer dan beranda.
- Buat katalog responsif, pencarian, filter, sorting, pagination, detail produk.
- Rapikan login/register.

**Definition of done:** seluruh halaman publik konsisten, responsive, accessible, dan mengambil data dari database.

### Milestone 3 — Admin dashboard

- Buat area admin yang terlindungi role/policy.
- Buat dashboard ringkasan dari data nyata.
- Buat manajemen obat dan kategori dengan form lengkap.
- Tambahkan feedback, empty state, konfirmasi delete, dan pagination.

**Definition of done:** admin dapat mengelola data; pengguna tanpa hak tidak dapat melewati otorisasi melalui URL atau request langsung.

### Milestone 4 — Inventaris lanjutan

- Sepakati model stok, riwayat stok, dan aturan kedaluwarsa.
- Implementasikan fitur inventaris hanya setelah aturan bisnis jelas.
- Tambahkan tests untuk perhitungan dan perubahan stok.

**Definition of done:** perubahan inventaris dapat diaudit dan tidak menghasilkan stok negatif atau data yang tidak konsisten.

---

## 11. Perintah development

Periksa README dan environment aktual sebelum menjalankan perintah. Secara umum proyek menyediakan:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
php artisan serve
```

Build production frontend:

```bash
npm run build
```

Test:

```bash
php artisan test
```

Format PHP:

```bash
vendor/bin/pint
```

Jangan menyalin `.env` atau secret ke commit. Untuk database SQLite, pastikan file database tersedia dan konfigurasi `.env` sesuai.

---

## 12. Prioritas keputusan

Jika harus memilih antara dua pendekatan, gunakan urutan prioritas ini:

1. Keamanan dan integritas data.
2. Fungsionalitas end-to-end.
3. Kemudahan penggunaan dan aksesibilitas.
4. Maintainability dan konsistensi Laravel.
5. Performa.
6. Visual polish dan animasi.

**Target akhir:** website apotek berbasis Laravel yang terlihat profesional, memiliki katalog publik dan dashboard admin yang benar-benar berfungsi, aman untuk dikembangkan, dan tidak berpura-pura memiliki fitur bisnis atau medis yang belum dibangun.
