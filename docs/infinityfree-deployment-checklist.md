# InfinityFree Deployment Checklist

Panduan ini berisi langkah-langkah untuk mendeploy project Laravel ini (Ethikopia Stock Management) ke hosting InfinityFree.

## 1. Persiapan Database (Export dari Local)

Sebelum mendeploy, pastikan data yang sudah ada di lokal diekspor dengan benar:
- **JANGAN** langsung export database yang masih berisi data testing secara keseluruhan jika Anda ingin database bersih di produksi.
- Jika Anda ingin memulai dengan data kosong untuk transaksi tapi tetap mempertahankan **Master Bahan** (Kategori, Barang, Limit), lakukan export **hanya** pada tabel berikut beserta datanya:
  - `bahan`
  - `bahan_limit`
  - `kategori_kelompok`
  - `roles`
  - `users`
- Kosongkan tabel transaksi berikut sebelum diexport (Trancate), atau jangan centang "Export Data" pada phpMyAdmin:
  - `update_stok`
  - `stok_masuk`
  - `daily_cleanings`

> **Note**: Struktur kolom dinamis pada tabel `update_stok` dan `stok_masuk` akan otomatis terbentuk sesuai daftar bahan aktif saat ini. 

## 2. Persiapan File Project (Local)

1. Jalankan perintah optimasi untuk memastikan semua view/cache aman:
   ```bash
   php artisan optimize:clear
   php artisan view:clear
   ```
2. Pastikan file `.env.production.example` sudah Anda sesuaikan isinya dan ubah namanya menjadi `.env`.
   - Set `APP_ENV=production`
   - Set `APP_DEBUG=false`
   - Set `APP_URL=https://[DOMAIN-INFINITYFREE-ANDA].epizy.com`
   - Masukkan kredensial database InfinityFree (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
3. (Opsional) Hapus folder `node_modules` dan `.git` sebelum di-zip untuk memperkecil ukuran file.
4. Compress / Zip seluruh folder project `laravel-app`.

## 3. Upload ke InfinityFree

1. Login ke panel InfinityFree (Control Panel).
2. Buka **Online File Manager** (htdocs).
3. Hapus file `index2.html` (bawaan InfinityFree) di dalam `htdocs`.
4. Upload file zip `laravel-app.zip` ke dalam folder `htdocs`.
5. Ekstrak file zip tersebut di dalam `htdocs`.
   - Pastikan struktur direktori utama aplikasi berada langsung di dalam `htdocs` (contoh: `htdocs/app`, `htdocs/public`, `htdocs/.env`).
6. Secara default, InfinityFree membaca `htdocs/index.php`. Namun Laravel menggunakan `public/index.php`. 
   - File `.htaccess` khusus sudah disertakan di root project (saat ini sudah ada di dalam zip). File ini akan **meroute otomatis** semua request masuk ke dalam folder `public/`.
   - Anda **TIDAK PERLU** memindahkan isi folder `public/` ke root `htdocs`.

## 4. Konfigurasi Database InfinityFree

1. Buka menu **MySQL Databases** di InfinityFree Control Panel.
2. Buat database baru.
3. Buka **phpMyAdmin** dari panel InfinityFree.
4. Import file SQL (hasil export dari tahap 1) ke dalam database baru tersebut.

## 5. Finalisasi & Testing Production

1. Buka domain InfinityFree Anda di browser (`https://[DOMAIN-ANDA].epizy.com`).
2. Pastikan halaman Login muncul tanpa pesan error.
3. Login menggunakan akun Barista / Manager.
4. Lakukan pengetesan input `Stok Masuk` dan `Update Stok`.
5. Pastikan tidak ada pesan error SQL yang berkaitan dengan kolom dinamis (jika terjadi error kolom missing, cukup masuk sebagai Manager -> Master Barang -> Klik tombol "Sinkronisasi Skema Database").

---
**Status QA & Performance Audit:**
- Semua *N+1 query issues* aman karena arsitektur tabel dinamis membaca flat table.
- Logika `Stok Saat Ini` dan `Estimasi Penggunaan` sudah lolos regression test.
- Aplikasi sudah *production-ready*.
