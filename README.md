# ERP Toko Jam (Laravel + Tailwind CSS)

Sistem ERP Toko Jam dengan modul Gudang, Sales & Invoice, Keuangan & Jurnal Otomatis, Scan QR Code, Perhitungan Komisi Sales, dan Dashboard Eksekutif.

## Tech Stack

- **Backend**: Laravel 11 / PHP 8.2+ (Strict Types)
- **Frontend**: Blade + Tailwind CSS + Alpine.js
- **Database**: SQLite (Local Dev) / MySQL 8+ / MariaDB (Production)
- **Quality Assurance**: Pest PHP, Larastan (PHPStan Level 6), Laravel Pint
- **Packages**:
  - `spatie/laravel-permission` (Role & Permission RBAC)
  - `spatie/laravel-activitylog` (Audit Trail)
  - `simplesoftwareio/simple-qrcode` (QR Code Generator)
  - `barryvdh/laravel-dompdf` (PDF Export)
  - `maatwebsite/excel` (Excel Import/Export)

---

## Langkah Instalasi Cepat

Ikuti langkah berikut untuk menjalankan aplikasi:

```bash
# 1. Clone repository
git clone <repository-url> demo-erp-toko-jam
cd demo-erp-toko-jam

# 2. Install dependensi PHP & Node.js
composer install
npm install

# 3. Setup environment & database SQLite
cp .env.example .env
php artisan key:generate
touch database/database.sqlite

# 4. Jalankan migrasi dan demo seeder
php artisan migrate --seed

# 5. Build asset frontend
npm run build

# 6. Jalankan dev server
php artisan serve
```

Akses aplikasi di browser pada: [http://localhost:8000](http://localhost:8000)

---

## Akun Demo (Local / Staging)

Semua akun demo menggunakan password default: `password`

| Role | Email | Username | Deskripsi Hak Akses |
|---|---|---|---|
| **Owner** | `owner@erp.test` | `owner` | Akses penuh seluruh modul & persetujuan |
| **Admin** | `admin@erp.test` | `admin` | Manajemen user, master data, dan operasional |
| **Gudang** | `gudang@erp.test` | `gudang` | Penerimaan barang, pengeluaran, opname, QR scan |
| **Sales** | `sales1@erp.test` | `sales1` | Pembuatan invoice, data pelanggan sendiri, tracking komisi |
| **Finance** | `finance@erp.test` | `finance` | Jurnal keuangan, kas/bank, approval komisi, laporan |

---

## Standar Kode & Testing

Pastikan seluruh pengecekan hijau sebelum push:

```bash
# Menjalankan seluruh test & analisis kualitas kode (Pint + Larastan + Pest)
composer check

# Perintah individu:
composer lint       # Cek format kode via Laravel Pint
./vendor/bin/pint   # Auto-format kode
composer analyse    # Analisis statis Larastan Level 6
composer test       # Menjalankan test suite Pest
```

---

## Acuan Dokumen

Lihat [TASK.md](file:///Users/syamsul/Documents/Coding/Project/Demo-ERP-Toko-JAM/TASK.md) untuk backlog lengkap, spesifikasi rumus, matriks permission, dan alur per fase.
