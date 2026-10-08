# Bangaldi

Aplikasi admin untuk mencatat penjualan iPhone, memantau omzet dan keuntungan, membuat laporan, serta mengekspor transaksi ke Excel.

## Persyaratan

- PHP 8.2 atau lebih baru dengan ekstensi yang dibutuhkan Laravel dan MySQL.
- Composer.
- Node.js dan npm.
- Database MySQL/MariaDB yang sudah dibuat.

## Menjalankan secara lokal

1. Pasang dependensi:

   ```powershell
   composer install
   npm install
   ```

2. Isi `.env` lokal dengan `APP_KEY`, koneksi database MySQL, serta `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD`. Jangan menyimpan atau membagikan nilai rahasia di Git.
3. Jalankan migrasi dan seeder:

   ```powershell
   php artisan migrate --seed
   ```

   Seeder menyiapkan akun admin dan daftar model. Data transaksi contoh hanya dibuat di environment `local`, jika database belum mempunyai transaksi.
4. Jalankan aplikasi dan Vite pada dua terminal:

   ```powershell
   php artisan serve
   ```

   ```powershell
   npm run dev
   ```

   Buka alamat yang ditampilkan oleh `artisan serve`, lalu masuk menggunakan email dan kata sandi admin yang dikonfigurasi.

Untuk aset produksi, jalankan `npm run build`. Ekspor transaksi dan laporan tersedia sebagai berkas `.xlsx`.

## Database dan backup

Pastikan `.env` menunjuk ke database MySQL yang benar sebelum menjalankan perintah Artisan. Migrasi aplikasi bersifat aditif; jangan gunakan `migrate:fresh` atau `migrate:refresh` pada database yang berisi data.

Buat backup sebelum perubahan skema. Pada XAMPP, jalankan dari PowerShell atau Command Prompt dengan `mysqldump` tersedia di `PATH` (atau gunakan path `mysqldump.exe` milik XAMPP):

```powershell
mysqldump --host=127.0.0.1 --user=USERNAME --password --single-transaction DATABASE > bangaldi-backup.sql
```

Opsi `--password` meminta kata sandi secara interaktif. Simpan berkas backup di lokasi privat dan aman, bukan di repository.

## Pengujian dan pemeriksaan

Perintah berikut menggunakan database pengujian SQLite dalam memori, bukan database aplikasi:

```powershell
php artisan test
vendor\bin\pint --test
npm run build
```
