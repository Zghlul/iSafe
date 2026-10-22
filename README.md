# iSafe

Aplikasi admin untuk mengelola unit stok iPhone per IMEI, mencatat penjualan, memantau omzet dan keuntungan, membuat laporan, serta mengekspor data ke Excel.

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

2. Isi `.env` lokal dengan `APP_KEY`, koneksi database MySQL, serta `ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD`. Set `SEED_DEMO_DATA=true` hanya jika ingin menambahkan data transaksi dan stok contoh. Jangan menyimpan atau membagikan nilai rahasia di Git.
3. Jalankan migrasi dan seeder:

    ```powershell
    php artisan migrate --seed
    ```

    Seeder menyiapkan akun admin dan daftar model. Data transaksi dan hingga 60 unit stok contoh hanya dibuat jika environment `local` dan `SEED_DEMO_DATA=true`. Untuk mengisi katalog website hosting dengan 15 unit data demo tanpa Faker atau transaksi demo, jalankan seeder khusus yang dijelaskan di bawah.

4. Jalankan aplikasi dan Vite pada dua terminal:

    ```powershell
    php artisan serve
    ```

    ```powershell
    npm run dev
    ```

    Buka alamat yang ditampilkan oleh `artisan serve`, lalu masuk menggunakan email dan kata sandi admin yang dikonfigurasi.

Untuk aset produksi, jalankan `npm run build`. Laporan stok mencakup ringkasan, stok menipis, stok lama, pergerakan, dan unit terjual. Ekspor transaksi, laporan, dan daftar stok tersedia sebagai berkas `.xlsx`.

## Database dan backup

### Katalog demo di hosting

Seeder berikut menambahkan 15 unit stok dengan sumber `Demo katalog`, tanpa membuat transaksi. Kartu dan pemberitahuan di landing page menandainya sebagai data dummy, bukan penawaran nyata. Jalankan hanya bila Anda memang ingin menampilkan katalog simulasi:

```bash
php artisan db:seed --class='Database\Seeders\StorefrontDemoStockSeeder' --force
```

Seeder ini idempoten dan tidak menghapus stok yang sudah ada.

Pastikan `.env` menunjuk ke database MySQL yang benar sebelum menjalankan perintah Artisan. Migrasi aplikasi bersifat aditif; jangan gunakan `migrate:fresh` atau `migrate:refresh` pada database yang berisi data.

Buat backup sebelum perubahan skema. Pada XAMPP, jalankan dari PowerShell atau Command Prompt dengan `mysqldump` tersedia di `PATH` (atau gunakan path `mysqldump.exe` milik XAMPP):

```powershell
mysqldump --host=127.0.0.1 --user=USERNAME --password --single-transaction DATABASE > bangaldi-backup.sql
```

Opsi `--password` meminta kata sandi secara interaktif. Simpan berkas backup di lokasi privat dan aman, bukan di repository.

### Persiapan migrasi modul stok

Sebelum memperbarui aplikasi produksi, buat backup database. Setelah rilis modul stok tersedia dan kode baru sudah terpasang, jalankan migrasi aditif:

```bash
php artisan migrate --force
```

Untuk menautkan transaksi aktif lama ke unit stok historis, jalankan perintah idempoten:

```bash
php artisan stock:backfill
```

Perintah ini membuat unit berstatus Terjual bagi transaksi aktif yang belum mempunyai unit stok; transaksi yang sudah dihapus tidak diubah. Jika proses menemukan IMEI yang sudah dipakai oleh unit stok lain, perintah berhenti dan me-rollback seluruh backfill agar datanya dapat diperiksa tanpa membuat tautan sebagian. Jangan jalankan `db:seed` pada produksi untuk mengisi contoh stok.

Setelah kode diperbarui di hosting, buat aset Vite dengan `npm run build` (atau unggah direktori `public/build` hasil build), lalu bersihkan cache Laravel:

```bash
php artisan optimize:clear
```

## Pengujian dan pemeriksaan

Perintah berikut menggunakan database pengujian SQLite dalam memori, bukan database aplikasi:

```powershell
php artisan test
vendor\bin\pint --test
npm run build
```
