# Ethikopia Coffeebay — Sistem Manajemen Stok Bahan Baku

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-^8.2-777BB4?logo=php&logoColor=white)](https://php.net)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-blue)](LICENSE)

**Ethikopia Coffeebay** adalah sistem manajemen stok bahan baku coffeeshop berbasis web yang dibangun menggunakan Laravel 12. Sistem ini dirancang untuk membantu operasional coffeeshop dalam memonitor dan mengelola persediaan bahan baku secara efisien, akurat, dan real-time.

---

## 📖 Tentang Project

Sistem ini dikembangkan untuk menjawab tantangan operasional coffeeshop dalam mengelola stok bahan baku yang beragam. Dengan antarmuka yang intuitif dan pembagian peran yang jelas, sistem memungkinkan:

- ✅ **Manager** memonitor ketersediaan stok secara real-time beserta analisis kebutuhan bahan.
- ✅ **Barista** melakukan input stok masuk, update stok, dan pencatatan operasional harian.
- ✅ **Mempermudah** operasional coffeeshop dengan pencatatan digital yang terstruktur.
- ✅ **Mengurangi** kesalahan pencatatan manual dengan validasi data otomatis dan notifikasi.

---

## ✨ Fitur

### 👑 Manager

Manager memiliki akses penuh ke seluruh fitur sistem:

| Fitur | Keterangan |
|-------|-----------|
| **Dashboard** | Ringkasan statistik stok (aman, tipis, habis), top barang sering habis, top barang hampir habis, dan aktivitas barista |
| **Data Barista** | Kelola data barista (Tambah, Lihat, Edit, Hapus) dengan validasi nomor telepon dan role |
| **Master Bahan** | Kelola data bahan baku (Tambah, Lihat, Edit, Hapus, Toggle Aktif/Nonaktif) dengan pengelompokan kategori & kelompok dinamis |
| **Pengaturan Limit Stok** | Atur batas stok *habis* dan *tipis* per bahan baku untuk monitoring stok otomatis |
| **Riwayat Stok Masuk** | Lihat, cari, filter (tanggal, shift, barista, barang), tambah, edit, hapus, dan ekspor ke Excel |
| **Riwayat Update Stok** | Lihat, cari, filter barang, detail, edit, hapus, dan ekspor ke Excel & PDF |
| **Riwayat Token Listrik** | Lihat, filter (tanggal, shift, barista), detail, hapus, hapus massal, dan ekspor ke Excel |
| **Riwayat Daily Clean** | Lihat, filter (tanggal, shift, barista), detail foto, hapus, hapus massal, dan ekspor ke Excel |
| **Forecast Kebutuhan Bahan** | Prediksi kebutuhan bahan baku mingguan berdasarkan riwayat pemakaian (dikelompokkan per kategori & kelompok) |
| **Estimasi Pembelian** | Estimasi jumlah pembelian yang diperlukan berdasarkan forecast kebutuhan |
| **Laporan** | Laporan mingguan lengkap dengan ringkasan statistik, top barang, aktivitas barista, forecast, dan ekspor PDF |
| **Edit Profil / Akun Saya** | Ubah nama, username, dan password akun Manager |

### 👤 Barista

Barista memiliki akses terbatas untuk pencatatan operasional harian:

| Fitur | Keterangan |
|-------|-----------|
| **Login** | Masuk menggunakan akun Barista yang telah didaftarkan oleh Manager |
| **Dashboard** | Ringkasan aktivitas pribadi (update stok, daily clean, token listrik hari ini & minggu ini) |
| **Input Stok Masuk** | Catat stok bahan baku yang baru datang (minimal satu item wajib diisi) |
| **Update Stok** | Catat stok terkini untuk seluruh bahan baku (semua item wajib diisi) |
| **Input Token Listrik** | Catat pemakaian token listrik (R17, R18, Mesin) per shift |
| **Daily Clean** | Upload foto dokumentasi kebersihan harian (minimal 4 foto) |
| **Logout** | Keluar dari sistem |

---

## 🛠️ Teknologi

| Teknologi | Kegunaan |
|-----------|----------|
| [Laravel 12](https://laravel.com) | Framework PHP untuk pengembangan aplikasi web |
| [PHP](https://php.net) ^8.2 | Bahasa pemrograman backend |
| [MySQL](https://mysql.com) | Database relasional |
| [Bootstrap 5](https://getbootstrap.com) | Framework CSS untuk antarmuka responsif |
| [Bootstrap Icons](https://icons.getbootstrap.com) | Ikon antarmuka |
| [Blade Template](https://laravel.com/docs/blade) | Engine templating Laravel |
| [HTML5](https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/HTML5) | Struktur halaman web |
| [CSS3](https://developer.mozilla.org/en-US/docs/Web/CSS) | Styling halaman web |
| [JavaScript](https://developer.mozilla.org/en-US/docs/Web/JavaScript) | Interaktivitas frontend |
| [SweetAlert2](https://sweetalert2.github.io) | Notifikasi dan dialog interaktif |
| [Vite](https://vitejs.dev) | Build tool untuk asset frontend |
| [Sass](https://sass-lang.com) | Preprocessor CSS |
| [Composer](https://getcomposer.org) | Dependency manager PHP |
| [DomPDF](https://github.com/dompdf/dompdf) | Generate dokumen PDF |
| [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io) | Generate file Excel (.xlsx) |

---

## 📂 Struktur Project

```
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Controller untuk Manager, Barista, Auth, dll
│   │   ├── Middleware/         # Middleware (SessionAuth, RoleMiddleware)
│   │   └── Requests/          # Form request validation
│   ├── Models/                # Model Eloquent (Bahan, Barista, StokMasuk, dll)
│   ├── Providers/             # Service Providers
│   └── Services/              # Business logic (StockAnalytics, ExportService)
├── resources/
│   └── views/                 # Blade template views
│       ├── auth/              # Halaman login
│       ├── barista/           # Halaman khusus barista
│       ├── layouts/           # Layout utama
│       ├── manager/           # Halaman khusus manager
│       └── partials/          # Komponen parsial (sidebar, footer)
├── routes/
│   ├── web.php                # Route utama
│   └── auth.php               # Route autentikasi
├── public/                    # Asset publik (CSS, JS, images)
├── database/
│   ├── migrations/            # Migration database
│   └── seeders/               # Data awal (seeder)
├── storage/                   # File upload, cache, logs
├── config/                    # Konfigurasi aplikasi
├── composer.json              # Dependency PHP
└── package.json               # Dependency frontend
```
## 📸 Screenshot

> Dokumentasi visual akan segera ditambahkan.

### Halaman Manager

| Dashboard | Data Barista |
|-----------|-------------|
| <img width="1356" height="634" alt="image" src="https://github.com/user-attachments/assets/2831cff6-1e17-441d-8ecf-9214f40af84f" />
 | <img width="1358" height="635" alt="image" src="https://github.com/user-attachments/assets/3c6d72ee-08d5-480b-afba-c11ab45b55c6" />
 |

| Master Bahan | Pengaturan Limit |
|-------------|-----------------|
| <img width="1356" height="631" alt="image" src="https://github.com/user-attachments/assets/02823214-3202-45f1-832b-0cd1e2d5dafb" />
 | <img width="1357" height="631" alt="image" src="https://github.com/user-attachments/assets/f2fccc53-0e13-4e66-8afd-c466e6c5e0e7" />
|

| Forecast | Laporan |
|----------|---------|
| <img width="1356" height="629" alt="image" src="https://github.com/user-attachments/assets/aa28a0cb-cf88-415c-b608-6ada85b7b62d" />
 | <img width="1359" height="635" alt="image" src="https://github.com/user-attachments/assets/6d4dc2e4-026e-437b-a302-0e9642d42dc6" />
 |

| Riwayat Stok Masuk | Riwayat Update Stok |
|--------------------|-------------------|
| <img width="1357" height="635" alt="image" src="https://github.com/user-attachments/assets/e0d6b136-b9aa-42b1-8255-6f06f33fc29a" />
 | <img width="1355" height="631" alt="image" src="https://github.com/user-attachments/assets/fcd38804-da54-4dfc-985c-f192ec536508" />
 |

| Riwayat Token Listrik | Riwayat Daily Clean |
|-----------------------|--------------------|
| <img width="1357" height="632" alt="image" src="https://github.com/user-attachments/assets/02b1dd61-1f04-409d-9a8e-02859e293ade" />
 | <img width="1351" height="635" alt="image" src="https://github.com/user-attachments/assets/9959c534-f448-4f78-96b9-8afab72763e4" />
 |

### Halaman Barista

| Login | Dashboard Barista |
|-------|------------------|
| <img width="1357" height="630" alt="image" src="https://github.com/user-attachments/assets/31933729-e586-4fc2-9d65-d1cf9995e9fa" />
 | <img width="1359" height="631" alt="image" src="https://github.com/user-attachments/assets/b282a686-9956-4e24-9e21-ec7e7beeb5f3" />
 |

| Input Stok Masuk | Update Stok |
|-----------------|-------------|
| <img width="1355" height="631" alt="image" src="https://github.com/user-attachments/assets/66213136-313a-4b90-a53e-3d5b80bd910d" />
 | <img width="1358" height="632" alt="image" src="https://github.com/user-attachments/assets/23b91e90-3f79-44ec-aa77-e84288c32afa" />
|

| Daily Clean | Token Listrik |
|-------------|---------------|
| <img width="1358" height="631" alt="image" src="https://github.com/user-attachments/assets/65c474e1-5017-422c-9b12-85ab2b664808" />
 | <img width="1357" height="636" alt="image" src="https://github.com/user-attachments/assets/ea0e9983-f744-42a1-83e2-40a261898847" />
 |

---

## 👨‍💻 Kontributor

Project ini dikembangkan oleh:

**Michael De Haan**  
Universitas Teknologi Yogyakarta  
Program Studi Sains Data

---

## 📄 Lisensi

Project ini dilisensikan di bawah **MIT License**. Silakan lihat file [LICENSE](LICENSE) untuk informasi lebih lanjut.

---

## 📝 Catatan Pengembangan

Sistem **Ethikopia Coffeebay** dikembangkan sebagai solusi manajemen operasional coffeeshop berbasis web menggunakan framework **Laravel 12**. Fokus utama pengembangan adalah:

- **Kemudahan penggunaan** — antarmuka yang intuitif dengan pembagian peran yang jelas.
- **Efisiensi operasional** — pencatatan digital yang menggantikan pencatatan manual.
- **Monitoring stok real-time** — dashboard dan notifikasi limit stok (*limit tipis* & *limit habis*) untuk pengambilan keputusan yang cepat.
- **Akurasi data** — validasi input otomatis, sinkronisasi skema dinamis, dan riwayat pencatatan yang lengkap.
- **Prediksi Kebutuhan (Forecast)** — algoritma perhitungan kebutuhan dan estimasi pembelian yang telah divalidasi sesuai *business logic* (tidak merekomendasikan *over-purchasing*).

Dibangun dengan arsitektur MVC Laravel, sistem ini mengimplementasikan konsep **Single Source of Truth** melalui service `StockAnalytics` untuk seluruh perhitungan analitik, serta **export service** yang menghasilkan laporan dalam format PDF dan Excel sesuai standar pelaporan profesional.

### Arsitektur Tabel Dinamis (Dynamic Columns)
Berbeda dengan sistem EAV (Entity-Attribute-Value) konvensional yang sering mengalami kendala performa (N+1 queries), sistem ini menggunakan arsitektur modifikasi skema tabel dinamis (ALTER TABLE). Setiap penambahan/perubahan Master Bahan akan otomatis menambah/menyesuaikan kolom pada tabel transaksi (`update_stok` & `stok_masuk`), menjamin pembacaan data stok secara instan dan hemat query.

---

## 🌐 Panduan Deployment (InfinityFree)

Aplikasi ini sudah dipersiapkan (*production-ready*) untuk di-deploy ke shared hosting gratis seperti **InfinityFree**.

1. **Persiapan:** `.htaccess` root sudah disediakan agar request otomatis diarahkan ke folder `public/`. Tidak perlu memodifikasi struktur inti Laravel.
2. **Export Database:** Pastikan melakukan export data khusus Master Data (`bahan`, `bahan_limit`, `roles`, `users`), dan truncate tabel transaksi jika ingin data operasional kosong di server live.
3. **Konfigurasi Lingkungan:** Atur `.env` menjadi `APP_ENV=production`, `APP_DEBUG=false`, dan sesuaikan kredensial database InfinityFree.
4. Upload semua file (tanpa `node_modules` dan `.git`) menggunakan form zip lalu ekstrak di folder `htdocs`.
*(Untuk langkah lebih detail, silakan merujuk pada file `docs/infinityfree-deployment-checklist.md`)*.
