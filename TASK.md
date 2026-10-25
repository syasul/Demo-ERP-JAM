# TASK.md — Proyek ERP (Laravel + Tailwind CSS)

> **Versi 2.0 (detail).** Acuan kerja lengkap: backlog bernomor, spesifikasi teknis, skema database, route, permission, kerangka kode, query dashboard, rencana sprint, dan kriteria penerimaan.
> Modul: **Gudang**, **Sales/Invoice**, **Keuangan**, **QR Code barang masuk/keluar**, **Perbaikan rumus**, **Komisi sales**, dan **3 Dashboard**.

## Cara Membaca Dokumen Ini

| Simbol | Arti |
|---|---|
| `T-x.y.z` | ID task (Fase.Bagian.Nomor). Dipakai di nama branch & commit: `feat/T-2.3.4-scan-masuk` |
| `[ ]` / `[x]` | Belum / selesai |
| 🔴 Must · 🟠 Should · 🟡 Could | Prioritas (MoSCoW) |
| **Output** | File/artefak yang harus ada setelah task selesai |
| **AC** | Acceptance Criteria — syarat task dianggap selesai |
| **Est.** | Estimasi jam kerja (1 developer) |

**Konvensi Git:** branch `feat/`, `fix/`, `chore/` · commit mengikuti Conventional Commits (`feat(inventory): add goods receipt posting`) · PR wajib 1 reviewer + CI hijau.

---

## DAFTAR ISI

0. Ringkasan, Asumsi & Keputusan Terbuka
1. Prinsip Teknis & Standar Kode
2. Arsitektur & Struktur Folder
3. Matriks Role & Permission
4. Rencana Sprint
5. Fase 1 — Fondasi
6. Fase 2 — Gudang & QR Code
7. Fase 3 — Sales & Invoice
8. Fase 4 — Perbaikan Rumus & Komisi
9. Fase 5 — Keuangan & Rekap
10. Fase 6 — Dashboard
11. Fase 7 — QA, Keamanan, Deployment
12. Lampiran: Rumus, Kasus Uji, Route, Enum, Config, Seeder, Glosarium

---

# 0. RINGKASAN, ASUMSI & KEPUTUSAN TERBUKA

## 0.1 Stack

| Lapisan | Teknologi | Catatan |
|---|---|---|
| Backend | Laravel 11, PHP 8.2+ | Strict types di semua file baru |
| Frontend | Blade + Tailwind CSS + Alpine.js | Livewire 3 opsional untuk tabel interaktif |
| DB | MySQL 8 / MariaDB 10.6+ | Engine InnoDB, utf8mb4 |
| Chart | Chart.js 4 / ApexCharts | Pilih satu, jangan campur |
| QR generate | `simplesoftwareio/simple-qrcode` | Output SVG (tajam saat cetak) |
| QR scan | `html5-qrcode` (kamera) + keyboard-wedge (scanner USB/BT) | Fallback input manual |
| Auth/Role | Laravel Breeze + `spatie/laravel-permission` | |
| Export | `maatwebsite/excel`, `barryvdh/laravel-dompdf` | PDF besar via queue |
| Audit | `spatie/laravel-activitylog` | |
| Cache/Queue | Redis (disarankan) / database | |
| QA | Pest, Larastan, Pint, Laravel Debugbar/Telescope (dev) | |

## 0.2 Asumsi Default (ubah di §0.3 bila berbeda)

| # | Asumsi | Disimpan di |
|---|---|---|
| A1 | Mata uang IDR, tanpa desimal, disimpan `unsignedBigInteger` | — |
| A2 | PPN 11 % (1100 basis points), bisa dimatikan per invoice | `settings.tax_rate_bp` |
| A3 | Valuasi stok: Moving Average | `settings.valuation_method` |
| A4 | Komisi dihitung saat pembayaran diterima (cash basis) | `settings.commission_basis` |
| A5 | Dasar komisi = DPP (setelah diskon, sebelum PPN, tanpa ongkir) | — |
| A6 | Multi-gudang didukung sejak awal | — |
| A7 | Stok minus **tidak** diizinkan | `settings.allow_negative_stock = false` |
| A8 | Timezone `Asia/Jakarta`; kolom tanggal bisnis bertipe `DATE`, timestamp disimpan UTC | `config/app.php` |
| A9 | Satu invoice = satu sales (tidak ada split komisi) | — |

## 0.3 Keputusan Terbuka (isi sebelum Sprint 1 berakhir)

| # | Pertanyaan | Opsi | Keputusan | PIC | Tgl |
|---|---|---|---|---|---|
| Q1 | Basis komisi | Invoice terbit / Uang diterima | _(default: uang diterima)_ | | |
| Q2 | Potong komisi bila telat bayar? | Tidak / Ya (N hari, X %) | | | |
| Q3 | Split komisi >1 sales per invoice? | Tidak / Ya | | | |
| Q4 | Metode HPP | Moving Average / FIFO | | | |
| Q5 | Perlu Sales Order/Quotation? | Ya / Tidak | | | |
| Q6 | Ukuran label QR & printer | A4 grid / thermal 38×25 mm / lainnya | | | |
| Q7 | Perangkat scan | Kamera HP / Scanner USB-BT / keduanya | | | |
| Q8 | Pre-order boleh stok minus? | Tidak / Ya | | | |
| Q9 | Faktur pajak / e-Faktur | Tidak / Laporan PPN saja / Integrasi | | | |
| Q10 | Pembayaran lebih (overpayment) | Tolak / Jadi deposit pelanggan | | | |
| Q11 | Komisi atas invoice tanpa PPN (non-PKP) | Sama / berbeda | | | |
| Q12 | Siapa yang approve komisi? | Finance / Owner / Keduanya | | | |

---

# 1. PRINSIP TEKNIS & STANDAR KODE

## 1.1 Aturan Wajib

1. **Uang = integer rupiah.** Dilarang `float`/`double` untuk uang. Kuantitas pakai `decimal(15,3)` dan dihitung sebagai integer milli-unit (qty × 1000) di Calculator.
2. **Satu sumber kebenaran rumus.** Semua hitungan hanya di `app/Services/**` dan `app/Actions/**`. Dilarang menghitung di Blade, Controller, atau JS (JS hanya memanggil endpoint *preview*).
3. **Stok = ledger append-only** (`stock_movements`). `stock_balances` hanya cache yang selalu bisa dibangun ulang.
4. **Setiap aksi posting dibungkus `DB::transaction()`** dan memakai `lockForUpdate()` pada baris saldo stok / nomor urut.
5. **Dokumen *posted* immutable.** Koreksi lewat Void/Reversal/Credit Note, bukan edit.
6. **Otorisasi berlapis:** route middleware → Policy → pengecekan *ownership* (sales hanya data sendiri).
7. **Validasi di FormRequest**, bukan di controller.
8. **Query agregasi di database**, bukan loop PHP. Dashboard di-cache.
9. **Semua string UI via `lang/id/*.php`** (siap multi-bahasa).
10. **Tidak ada magic number/string**: gunakan Enum & `config/erp.php`.

## 1.2 Standar Kode

- PSR-12 via **Laravel Pint** (`pint.json` preset `laravel`).
- **Larastan level 6** (naik ke 8 bertahap). Baseline file diperbolehkan hanya untuk kode lama.
- `declare(strict_types=1);` di semua file PHP baru.
- Controller ≤ 7 method publik, tipis: validasi → panggil Action → return response.
- 1 Action = 1 use-case, method publik `handle()`; tidak boleh memanggil Action lain secara melingkar.
- Model: `$fillable` eksplisit, `casts()` untuk enum/date/json, relasi bertipe return.
- Nama tabel plural snake_case, FK `xxx_id`, index diberi nama jelas.
- Tailwind: tidak ada CSS kustom kecuali di `resources/css/app.css` (`@layer components`); ekstrak komponen Blade bila class berulang >3×.

## 1.3 Checklist Review PR

- [ ] Tidak ada hitungan uang di luar Service/Action
- [ ] Tidak ada N+1 (cek Debugbar)
- [ ] Ada test untuk jalur sukses **dan** gagal
- [ ] Policy/permission dicek
- [ ] Migrasi bisa di-rollback (`down()` benar)
- [ ] Tidak ada `dd()`, `dump()`, `console.log`
- [ ] Teks UI dari file lang
- [ ] Aksi sensitif tercatat di activity log

---

# 2. ARSITEKTUR & STRUKTUR FOLDER

```
app/
├── Actions/
│   ├── Inventory/   ReceiveGoods, PostGoodsReceipt, IssueGoods, PostGoodsIssue,
│   │                TransferStock, ReceiveTransfer, PostStockOpname, VoidGoodsReceipt
│   ├── Sales/       CreateInvoice, UpdateDraftInvoice, PostInvoice, VoidInvoice,
│   │                RecordPayment, VoidPayment, CreateCreditNote
│   ├── Commission/  AccrueCommission, ReverseCommission, ApproveCommission, CreatePayout
│   └── Finance/     PostJournal, ReverseJournal, CloseAccountingPeriod
├── Console/Commands/ StockRebuild, StockAudit, CommissionRecalculate, DashboardWarmCache
├── DTO/             InvoiceTotals, InvoiceLineInput, DiscountInput, JournalData, QrPayload
├── Enums/           InvoiceStatus, MovementType, DocumentStatus, CommissionBasis,
│                    CommissionStatus, CommissionRuleType, PaymentMethod, AccountType, QrMode
├── Exceptions/      InsufficientStockException, PeriodClosedException,
│                    UnbalancedJournalException, InvalidQrException, CreditLimitExceededException
├── Http/
│   ├── Controllers/ (per modul, subfolder)
│   ├── Middleware/  EnsurePeriodOpen, SetLocale
│   ├── Requests/    (per modul)
│   └── Resources/   (JSON untuk endpoint scan & preview)
├── Models/
├── Policies/
├── Services/
│   ├── Inventory/   StockService, ValuationService, DocumentNumberService
│   ├── Sales/       InvoiceCalculator, CreditLimitService
│   ├── Commission/  CommissionCalculator, CommissionRuleResolver
│   ├── Finance/     LedgerService, ReportService, AgingService
│   ├── Dashboard/   WarehouseDashboard, SalesDashboard, FinanceDashboard
│   └── Qr/          QrGeneratorService, QrPayloadService, QrScanService, LabelSheetService
├── Support/         Money.php, helpers.php, Period.php
config/erp.php       (default setting, prefix nomor dokumen, ukuran label)
database/
├── migrations/      (urut per fase)
├── seeders/         RolePermissionSeeder, CoaSeeder, DemoDataSeeder
└── factories/
lang/id/             erp.php, validation.php, inventory.php, sales.php, finance.php
resources/views/
├── layouts/         app.blade.php, guest.blade.php, print.blade.php
├── components/      ui/ (card, stat, table, modal, badge, alert, empty, money-input, date-range, …)
├── dashboard/       warehouse, sales, finance
├── inventory/ sales/ finance/ commission/ qr/ settings/ pdf/
resources/js/        app.js, qr-scanner.js, charts.js, invoice-form.js
tests/
├── Unit/            InvoiceCalculatorTest, CommissionCalculatorTest, MoneyTest, ValuationServiceTest
└── Feature/         (alur per modul + E2E + Authorization + Concurrency)
```

### Alur Data Utama

```
Supplier → [Goods Receipt] → stock_movements(IN) → stock_balances ↑ → Journal (Persediaan/Hutang)
Customer → [Invoice POST] → Goods Issue otomatis → stock_movements(OUT) → stock_balances ↓
                          → Journal (Piutang/Penjualan/PPN) + Journal (HPP/Persediaan)
        → [Payment] → Journal (Kas/Piutang) → AccrueCommission (jika basis = payment)
        → [Void/Credit Note] → Reversal stok + jurnal + komisi
Dashboard & Laporan ← membaca dari invoices, payments, stock_movements, journals, commissions
```

---

# 3. MATRIKS ROLE & PERMISSION

**Role:** `owner`, `admin`, `gudang`, `sales`, `finance`. (✔ = diizinkan, 👤 = hanya data milik sendiri, ✖ = tidak)

| Permission | owner | admin | gudang | sales | finance |
|---|:--:|:--:|:--:|:--:|:--:|
| `user.manage`, `role.manage` | ✔ | ✔ | ✖ | ✖ | ✖ |
| `settings.manage` | ✔ | ✔ | ✖ | ✖ | ✖ |
| `product.view` | ✔ | ✔ | ✔ | ✔ | ✔ |
| `product.manage` (CRUD, harga) | ✔ | ✔ | ✖ | ✖ | ✖ |
| `customer.view` / `customer.manage` | ✔ | ✔ | ✖ | 👤 | ✔ |
| `supplier.manage` | ✔ | ✔ | ✔ | ✖ | ✔ |
| `stock.view` | ✔ | ✔ | ✔ | ✔ | ✔ |
| `stock.in.create` / `stock.in.post` | ✔ | ✔ | ✔ | ✖ | ✖ |
| `stock.out.create` / `stock.out.post` | ✔ | ✔ | ✔ | ✖ | ✖ |
| `stock.transfer` | ✔ | ✔ | ✔ | ✖ | ✖ |
| `stock.opname.create` / `stock.opname.approve` | ✔/✔ | ✔/✔ | ✔/✖ | ✖ | ✖ |
| `qr.scan` | ✔ | ✔ | ✔ | ✔ (mode info) | ✖ |
| `qr.label.print` | ✔ | ✔ | ✔ | ✖ | ✖ |
| `invoice.view` | ✔ | ✔ | ✖ | 👤 | ✔ |
| `invoice.create` / `invoice.post` | ✔ | ✔ | ✖ | 👤 | ✖ |
| `invoice.void` | ✔ | ✖ | ✖ | ✖ | ✔ |
| `payment.record` | ✔ | ✔ | ✖ | ✖ | ✔ |
| `creditnote.create` | ✔ | ✔ | ✖ | 👤 (draft) | ✔ |
| `commission.view` | ✔ | ✔ | ✖ | 👤 | ✔ |
| `commission.rule.manage` | ✔ | ✖ | ✖ | ✖ | ✔ |
| `commission.approve` / `commission.pay` | ✔ | ✖ | ✖ | ✖ | ✔ |
| `finance.journal.create` / `approve` | ✔ | ✖ | ✖ | ✖ | ✔ |
| `finance.period.close` | ✔ | ✖ | ✖ | ✖ | ✔ |
| `report.sales.view` | ✔ | ✔ | ✖ | 👤 | ✔ |
| `report.finance.view` | ✔ | ✖ | ✖ | ✖ | ✔ |
| `dashboard.warehouse` | ✔ | ✔ | ✔ | ✖ | ✖ |
| `dashboard.sales` | ✔ | ✔ | ✖ | 👤 | ✔ |
| `dashboard.finance` | ✔ | ✖ | ✖ | ✖ | ✔ |
| `auditlog.view` | ✔ | ✔ | ✖ | ✖ | ✖ |

- [ ] **T-0.3.1** Implementasi di `RolePermissionSeeder` (idempotent: `firstOrCreate`). **AC:** menjalankan seeder 2× tidak menggandakan data.
- [ ] **T-0.3.2** Test `AuthorizationMatrixTest` — dataset berisi baris di atas, tiap role di-assert terhadap endpoint representatif. **AC:** 100 % baris matriks tercakup.

---

# 4. RENCANA SPRINT (2 minggu/sprint)

| Sprint | Fokus | Task Utama | Demo / Exit Criteria |
|---|---|---|---|
| **S1** | Fondasi | Fase 1 (setup, auth, layout, master data) | Login per role, CRUD produk/pelanggan, layout responsif |
| **S2** | Gudang I | 2.1–2.4 (skema, StockService, GR, GI) | Barang masuk & keluar manual, kartu stok benar |
| **S3** | QR + Gudang II | 2.5–2.7 (QR, transfer, opname) | Scan masuk/keluar dari HP, cetak label QR |
| **S4** | Sales | Fase 3 (invoice, payment, credit note, PDF) | Invoice end-to-end memotong stok & bisa dibayar |
| **S5** | Rumus + Komisi | Fase 4 | Semua kasus uji §12.2 hijau, komisi muncul & bisa di-approve |
| **S6** | Keuangan | Fase 5 | Laba rugi & arus kas cocok dengan data operasional |
| **S7** | Dashboard | Fase 6 | 3 dashboard live, rekonsiliasi angka lolos |
| **S8** | Hardening | Fase 7 (QA, performa, UAT, deploy) | Sign-off UAT, go-live checklist lengkap |

**Total estimasi:** ± 16 minggu (1 dev) atau ± 9–10 minggu (2 dev, paralel Gudang ‖ Sales setelah S1).

**Definition of Ready (task masuk sprint):** AC jelas · keputusan terbuka terkait sudah dijawab · desain/wireframe tersedia · data uji tersedia.

---

# 5. FASE 1 — FONDASI (Sprint 1)

## 5.1 Setup Project

- [ ] **T-1.1.1 🔴 Inisialisasi Laravel & environment** · Est. 3 j
  - `composer create-project laravel/laravel erp`; set `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id`, `APP_FAKER_LOCALE=id_ID`.
  - Buat `.env.example` lengkap (DB, mail, queue, cache, `ERP_*`).
  - **Output:** repo ter-push, `README.md` berisi langkah instalasi.
  - **AC:** `git clone` → `composer install` → `php artisan migrate --seed` → aplikasi jalan tanpa langkah manual tambahan.

- [ ] **T-1.1.2 🔴 Tailwind, Vite, Alpine** · Est. 3 j
  - `npm i -D tailwindcss @tailwindcss/forms @tailwindcss/typography postcss autoprefixer`; `npm i alpinejs chart.js html5-qrcode`.
  - `tailwind.config.js`: definisikan palet brand (`primary`, `success`, `warning`, `danger`), font (Inter), `content` mencakup `resources/**/*.blade.php`.
  - **AC:** `npm run build` tanpa warning; ukuran CSS produksi < 60 KB gzip.

- [ ] **T-1.1.3 🔴 Paket & kualitas kode** · Est. 3 j
  - Install: `spatie/laravel-permission`, `spatie/laravel-activitylog`, `maatwebsite/excel`, `barryvdh/laravel-dompdf`, `simplesoftwareio/simple-qrcode`, `pestphp/pest`, `larastan/larastan`, `laravel/pint`.
  - Script composer: `"lint": "pint --test"`, `"analyse": "phpstan analyse"`, `"test": "pest"`, `"check": ["@lint","@analyse","@test"]`.
  - **AC:** `composer check` hijau di mesin bersih.

- [ ] **T-1.1.4 🟠 CI/CD** · Est. 3 j
  - GitHub Actions: job `lint`, `analyse`, `test` (service MySQL 8), cache composer & npm, job `build-assets`.
  - **AC:** PR gagal merge jika salah satu job merah (branch protection aktif).

- [ ] **T-1.1.5 🔴 Helper `Money` & `config/erp.php`** · Est. 4 j
  - `App\Support\Money`:
    - `percentOf(int $amount, int $bp): int` → `intdiv($amount * $bp + 5000, 10000)` (half-up, nilai non-negatif; untuk negatif gunakan sign-aware).
    - `mulQty(int $qtyMilli, int $unitPrice): int` → `intdiv($qtyMilli * $unitPrice + 500, 1000)`.
    - `format(int $v): string` → `Rp 1.250.000`; `parse(string $s): int` (terima `1.250.000`, `1250000`, `Rp 1.250.000`).
  - Blade directive `@rupiah($value)`.
  - **AC:** `MoneyTest` ≥ 15 kasus (0, negatif, batas half-up, angka besar 10¹²).

## 5.2 Auth, Role & Permission

- [ ] **T-1.2.1 🔴 Autentikasi** · Est. 6 j
  - Breeze (Blade). Login by email/username, "ingat saya", reset password, throttle 5 percobaan/menit.
  - Kolom `users`: `name, email, username, phone, is_active, last_login_at, last_login_ip`.
  - User nonaktif tidak bisa login (pesan jelas).
  - **AC:** test login sukses, gagal, user nonaktif, throttle.

- [ ] **T-1.2.2 🔴 Role & permission** · Est. 6 j
  - `RolePermissionSeeder` sesuai matriks §3.
  - Middleware `permission:` di group route; `@can` di Blade untuk menu.
  - **AC:** `AuthorizationMatrixTest` hijau.

- [ ] **T-1.2.3 🟠 Manajemen user & role (UI)** · Est. 8 j
  - Halaman: daftar user (filter role/status), tambah/edit, reset password, aktif/nonaktif; matriks centang permission per role (khusus owner).
  - **AC:** admin tidak bisa mengubah role owner; user tidak bisa menonaktifkan dirinya sendiri.

- [ ] **T-1.2.4 🔴 Scope data sales** · Est. 4 j
  - Trait `BelongsToSales` + global scope: jika user role `sales` → otomatis `where sales_id = user->salesProfile->id`.
  - **AC:** test: sales A tidak bisa melihat/mengubah invoice sales B (404, bukan 403, agar ID tidak bocor).

## 5.3 Layout & Design System

- [ ] **T-1.3.1 🔴 Layout aplikasi** · Est. 8 j
  - Sidebar collapsible (state di Alpine + `localStorage`), grup menu: Dashboard · Gudang · Sales · Keuangan · Komisi · Master Data · Pengaturan. Menu tampil sesuai permission.
  - Topbar: pencarian global (produk/invoice/pelanggan), notifikasi, profil, tombol **Scan QR** (pintasan).
  - Breadcrumb otomatis dari nama route.
  - **AC:** nyaman di lebar 360 px; sidebar jadi drawer di mobile.

- [ ] **T-1.3.2 🔴 Komponen UI Blade** · Est. 12 j
  - `x-ui.card`, `x-ui.stat` (judul, nilai, delta ▲▼, ikon), `x-ui.table` (header sticky, sort, empty slot), `x-ui.pagination`, `x-ui.modal`, `x-ui.badge` (warna per status), `x-ui.alert`, `x-ui.empty`, `x-ui.money-input` (format ribuan live, kirim integer), `x-ui.date-range` (preset: hari ini, 7 hari, bulan ini, bulan lalu, tahun ini), `x-ui.select-search` (async), `x-ui.confirm` (dialog konfirmasi aksi destruktif), `x-ui.skeleton`.
  - Halaman katalog komponen `/dev/ui` (hanya `APP_ENV=local`).
  - **AC:** semua komponen punya varian error/disabled/loading.

- [ ] **T-1.3.3 🟠 Notifikasi toast & flash** · Est. 3 j — **AC:** flash `success/error/warning` otomatis hilang 5 dtk, bisa ditutup, aksesibel (`role="status"`).

- [ ] **T-1.3.4 🟡 Dark mode** · Est. 4 j — `darkMode: 'class'`, preferensi tersimpan per user.

## 5.4 Master Data

- [ ] **T-1.4.1 🔴 Migrasi master** · Est. 6 j

  ```
  categories     id, name, parent_id nullable, timestamps
  units          id, code(unique), name
  products       id, sku(unique), barcode nullable(unique), name, category_id, unit_id,
                 cost_price bigint default 0, sell_price bigint default 0,
                 min_stock decimal(15,3) default 0, track_batch bool default false,
                 is_active bool default true, image_path nullable, description text nullable,
                 softDeletes, timestamps
                 INDEX(name), INDEX(category_id), INDEX(is_active)
  warehouses     id, code(unique), name, address nullable, is_active
  locations      id, warehouse_id, code, name nullable, unique(warehouse_id, code)
  customers      id, code(unique), name, phone, email, address, city,
                 payment_term_days smallint default 0, credit_limit bigint default 0,
                 default_sales_id nullable, npwp nullable, is_active, softDeletes
  suppliers      id, code(unique), name, phone, email, address, payment_term_days, is_active
  sales_profiles id, user_id(unique), employee_code(unique), phone, join_date, is_active
  settings       key(pk), value text, type[string|int|bool|json], group
  ```
  - **AC:** `migrate:fresh --seed` & `migrate:rollback` sukses; index terverifikasi lewat `EXPLAIN` pada pencarian produk.

- [ ] **T-1.4.2 🔴 CRUD Produk** · Est. 12 j
  - Daftar: search (nama/SKU/barcode), filter kategori/status/stok-menipis, tampilkan stok total, aksi massal (aktif/nonaktif).
  - Form: validasi SKU unik (abaikan diri sendiri saat edit), harga ≥ 0, upload foto (maks 2 MB, jpg/png/webp, disimpan di disk `public`, resize 800 px).
  - Auto-generate SKU bila kosong: `{KAT3}-{000001}`.
  - Produk yang sudah punya transaksi **tidak bisa dihapus** (hanya nonaktif).
  - Riwayat perubahan harga tercatat di activity log (harga lama → baru).
  - **AC:** test CRUD, validasi, larangan hapus produk bertransaksi.

- [ ] **T-1.4.3 🔴 CRUD Pelanggan, Supplier, Kategori, Satuan, Gudang, Lokasi** · Est. 14 j
  - Pelanggan: tampilkan ringkasan piutang & total belanja di halaman detail.
  - Lokasi: format kode rak `{ZONA}-{RAK}-{LEVEL}` (mis. `A-01-03`), validasi regex `^[A-Z0-9]+(-[A-Z0-9]+){0,3}$`.
  - **AC:** relasi tidak bisa dihapus bila dipakai (pesan "dipakai oleh N data").

- [ ] **T-1.4.4 🟠 Import/Export Excel** · Est. 10 j
  - Template unduhan; import produk & pelanggan dengan `WithValidation` + `SkipsOnFailure`; hasil: ringkasan *berhasil/gagal* + unduh file error (nomor baris + alasan).
  - Import berjalan di queue bila > 500 baris.
  - **AC:** file 5.000 baris selesai < 60 dtk; baris duplikat SKU ditandai, tidak menimpa tanpa opsi "update".

- [ ] **T-1.4.5 🔴 Pengaturan (Settings)** · Est. 5 j
  - Halaman untuk: profil perusahaan (nama, alamat, NPWP, logo, rekening bank), `tax_rate_bp`, `commission_basis`, `valuation_method`, `allow_negative_stock`, prefix & format nomor dokumen, ukuran label QR.
  - Akses via `Setting::get('key', default)` ber-cache; `Setting::set()` meng-invalidate.
  - **AC:** perubahan tercatat di audit log.

- [ ] **T-1.4.6 🟠 Seeder demo** · Est. 6 j — 50 produk, 5 kategori, 2 gudang (masing-masing 10 rak), 20 pelanggan, 5 supplier, 4 sales, transaksi 3 bulan terakhir; **idempotent** dan hanya jalan di `local/staging`.

---

# 6. FASE 2 — GUDANG & QR CODE (Sprint 2–3)

## 6.1 Skema Database Gudang

- [ ] **T-2.1.1 🔴 Migrasi stok** · Est. 8 j

  ```
  stock_balances      id, product_id, warehouse_id, location_id nullable,
                      qty decimal(15,3) default 0, avg_cost bigint default 0,
                      version int default 0, timestamps
                      UNIQUE(product_id, warehouse_id, location_id)
                      INDEX(warehouse_id, qty)

  stock_movements     id, product_id, warehouse_id, location_id nullable, batch_id nullable,
                      type (enum MovementType), direction enum('in','out'),
                      qty decimal(15,3)  CHECK(qty > 0),
                      unit_cost bigint, total_cost bigint,
                      balance_after decimal(15,3),          -- saldo gudang setelah movement
                      reference_type, reference_id,         -- polymorphic ke dokumen
                      reversal_of nullable (fk stock_movements.id),
                      qr_scan_id nullable, note nullable,
                      created_by, created_at               -- TANPA updated_at (immutable)
                      INDEX(product_id, warehouse_id, created_at)
                      INDEX(reference_type, reference_id)

  product_batches     id, product_id, batch_no, expired_at nullable, UNIQUE(product_id, batch_no)

  goods_receipts      id, number(unique), supplier_id, warehouse_id, receipt_date date,
                      status enum(draft|posted|void), supplier_ref nullable, note,
                      total_cost bigint, posted_by/at, voided_by/at/reason, softDeletes
  goods_receipt_items id, goods_receipt_id, product_id, location_id nullable, batch_id nullable,
                      qty decimal(15,3), unit_cost bigint, line_total bigint

  goods_issues        id, number(unique), warehouse_id, issue_date date,
                      type enum(sale|transfer|adjustment|damaged|return_supplier),
                      reference_type/id nullable, status enum(draft|packed|shipped|delivered|void),
                      recipient_name, shipping_address, note, posted_by/at, voided_*
  goods_issue_items   id, goods_issue_id, product_id, location_id, batch_id nullable,
                      qty decimal(15,3), unit_cost_snapshot bigint

  stock_transfers     id, number(unique), from_warehouse_id, to_warehouse_id, status
                      enum(draft|in_transit|received|cancelled), sent_at, received_at, note
  stock_transfer_items id, stock_transfer_id, product_id, qty_sent, qty_received nullable, ...

  stock_opnames       id, number(unique), warehouse_id, opname_date, status
                      enum(draft|counting|submitted|approved|rejected), approved_by/at
  stock_opname_items  id, stock_opname_id, product_id, location_id, system_qty, counted_qty,
                      diff_qty (generated), note

  qr_scans            id, user_id, mode(in|out|opname|info), payload, qr_type, product_id nullable,
                      result enum(ok|invalid|duplicate|not_found|forbidden), message nullable,
                      device nullable, ip, document_type/id nullable, created_at
                      INDEX(user_id, created_at)

  document_sequences  id, doc_type, period (YYYYMM), last_number, UNIQUE(doc_type, period)
  ```
  - **AC:** semua migrasi punya `down()`; `stock_movements` tidak punya jalur UPDATE/DELETE di kode (cek via test arsitektur Pest `arch()`).

- [ ] **T-2.1.2 🔴 Enum & Model** · Est. 4 j
  - `MovementType` (+ method `direction()`, `label()`): `IN_PURCHASE, IN_RETURN_SALE, IN_TRANSFER, IN_ADJUSTMENT, IN_OPENING, OUT_SALE, OUT_TRANSFER, OUT_ADJUSTMENT, OUT_DAMAGED, OUT_RETURN_SUPPLIER`.
  - Model `StockMovement` melempar exception pada `updating`/`deleting` (immutability guard).

- [ ] **T-2.1.3 🔴 `DocumentNumberService`** · Est. 5 j
  - Format dikonfigurasi (`{PREFIX}/{YYYY}/{MM}/{SEQ:4}`), contoh `GR/2026/10/0001`.
  - Aman concurrent: `DB::transaction` + `lockForUpdate` pada `document_sequences`.
  - Reset nomor per bulan (atau tahun, via setting).
  - **AC:** test paralel (20 proses) menghasilkan 20 nomor unik berurutan tanpa celah.

## 6.2 `StockService` (jantung modul)

- [ ] **T-2.2.1 🔴 Implementasi `StockService`** · Est. 20 j

  ```php
  final class StockService
  {
      public function increase(StockChange $c): StockMovement;   // masuk + hitung avg_cost baru
      public function decrease(StockChange $c): StockMovement;   // keluar; cek stok; ambil avg_cost saat ini
      public function transfer(TransferChange $c): array;        // [out, in] satu transaksi
      public function reverse(StockMovement $m, string $reason): StockMovement;
      public function available(int $productId, int $warehouseId, ?int $locationId = null): string;
  }
  ```
  - `StockChange` (DTO): `productId, warehouseId, locationId, batchId, qty (string decimal), unitCost (int), type, reference (Model), note, qrScanId`.
  - Langkah `decrease`: (1) `StockBalance::lockForUpdate()` → (2) jika `qty > saldo` & `!allow_negative` → `InsufficientStockException` (bawa info produk & sisa) → (3) tulis movement dengan `unit_cost = avg_cost` → (4) update saldo & `version++` → (5) isi `balance_after`.
  - Langkah `increase`: moving average `new_avg = (old_qty×old_avg + in_qty×in_cost) ÷ (old_qty + in_qty)` dibulatkan half-up ke rupiah; bila `old_qty ≤ 0` → `new_avg = in_cost`.
  - `reverse`: membuat movement arah berlawanan, `reversal_of` terisi, dengan `unit_cost` = nilai movement asal (agar nilai persediaan kembali persis).
  - **AC:**
    - Test: masuk 10@100 + masuk 10@200 ⇒ avg 150; keluar 5 ⇒ HPP 150 ×5 = 750.
    - Test: stok 1, dua request keluar bersamaan ⇒ tepat 1 sukses.
    - Test: reverse mengembalikan saldo & nilai persediaan seperti semula.
    - Tidak ada `qty` negatif di `stock_balances` kecuali setting mengizinkan.

- [ ] **T-2.2.2 🔴 Command `stock:rebuild` & `stock:audit`** · Est. 6 j
  - `stock:audit` membandingkan `stock_balances` vs `SUM(IN) − SUM(OUT)` per produk/gudang; keluarkan tabel selisih; exit code ≠ 0 bila ada selisih.
  - `stock:rebuild {--product=} {--warehouse=} {--dry-run}` membangun ulang saldo & avg_cost dari ledger.
  - Jadwalkan `stock:audit` harian 01:00, kirim notifikasi ke admin bila ada selisih.
  - **AC:** setelah merusak saldo manual di test, `stock:rebuild` memulihkannya.

## 6.3 Barang Masuk (Goods Receipt)

- [ ] **T-2.3.1 🔴 CRUD & posting GR** · Est. 20 j
  - **Halaman daftar** `/inventory/receipts`: kolom Nomor, Tanggal, Supplier, Gudang, Total, Status; filter tanggal/supplier/status; export.
  - **Form**: header (supplier, gudang, tanggal, no. surat jalan supplier) + tabel item dinamis (Alpine): cari produk (nama/SKU/scan), qty, harga beli, rak tujuan, batch & expired (jika `track_batch`), subtotal otomatis (hanya tampilan; angka final dari server).
  - **State:** `draft` (bebas edit/hapus) → `posted` (immutable) → `void` (reversal).
  - **`PostGoodsReceipt` (Action):** transaksi → validasi periode terbuka → per item `StockService::increase` → update `products.cost_price` (opsional via setting) → buat jurnal (§9.2 J3) → set `posted_at/by` → activity log.
  - **`VoidGoodsReceipt`:** hanya jika stok hasil GR belum terpakai (qty tersedia ≥ qty GR); jika tidak → tolak dengan pesan produk mana yang sudah keluar.
  - Cetak PDF **Tanda Terima Barang** (logo, daftar item, kolom tanda tangan, QR dokumen).
  - **AC:** post → saldo & kartu stok & jurnal benar; edit setelah post ditolak (403 + pesan); void mengembalikan stok.

- [ ] **T-2.3.2 🟠 Batch & kedaluwarsa** · Est. 10 j — wajib isi batch untuk produk `track_batch`; pengeluaran memakai **FEFO** (expired terdekat dulu) sebagai saran urutan picking.

## 6.4 Barang Keluar (Goods Issue)

- [ ] **T-2.4.1 🔴 CRUD & posting GI manual** · Est. 16 j
  - Tipe: `adjustment`, `damaged`, `return_supplier`, `transfer` (dibuat dari modul transfer). Tipe `sale` dibuat otomatis oleh `PostInvoice`.
  - Validasi stok real-time (endpoint `GET /api/stock/available?product=&warehouse=`) + validasi ulang di server saat post.
  - **`PostGoodsIssue`:** `StockService::decrease` per item, simpan `unit_cost_snapshot`, jurnal sesuai tipe (rusak → Beban Barang Rusak/Persediaan).
  - **AC:** keluar > stok ⇒ error jelas per baris; post berhasil mengurangi stok & jurnal seimbang.

- [ ] **T-2.4.2 🟠 Surat Jalan / Delivery Order** · Est. 8 j
  - Alur status: `draft → packed → shipped → delivered`; perubahan status mencatat waktu & user.
  - PDF Surat Jalan: nomor, pelanggan, alamat kirim, daftar barang (tanpa harga), tanda tangan 3 pihak, **QR dokumen bertanda tangan** (verifikasi §6.7).
  - **AC:** `shipped` tidak bisa kembali ke `draft`; `delivered` memerlukan nama penerima.

## 6.5 Transfer & Opname

- [ ] **T-2.5.1 🟠 Transfer antar gudang** · Est. 14 j
  - Alur: `draft → in_transit` (stok keluar dari asal, masuk akun *Barang Dalam Perjalanan*) → `received` (stok masuk ke tujuan; qty diterima boleh ≠ qty kirim, selisih dicatat sebagai adjustment dengan alasan wajib).
  - **AC:** total nilai persediaan sebelum & sesudah transfer sama (selain selisih yang dicatat).

- [ ] **T-2.5.2 🟠 Stock Opname** · Est. 16 j
  - Alur: buat sesi (pilih gudang/zona/rak) → **snapshot `system_qty`** saat sesi dimulai → hitung fisik via scan QR/manual → submit → **approval** (`stock.opname.approve`) → sistem membuat adjustment IN/OUT sebesar selisih → jurnal penyesuaian.
  - Pergerakan stok yang terjadi *selama* sesi ditampilkan sebagai peringatan (agar selisih tidak salah baca).
  - Laporan selisih: produk, rak, sistem, fisik, selisih qty, selisih nilai (Rp).
  - **AC:** opname yang di-reject tidak mengubah stok; approve membuat movement bertipe `IN_ADJUSTMENT/OUT_ADJUSTMENT` yang terhubung ke dokumen opname.

- [ ] **T-2.5.3 🔴 Kartu Stok & Posisi Stok** · Est. 8 j
  - **Kartu stok** per produk/gudang/rentang tanggal: tanggal, dokumen (link), masuk, keluar, saldo berjalan, HPP — saldo awal periode dihitung dari ledger.
  - **Posisi stok**: per gudang/rak, nilai persediaan, filter kategori; export Excel/PDF.
  - **AC:** saldo akhir kartu stok == `stock_balances`; export sama dengan tampilan.

## 6.6 Spesifikasi QR Code

### 6.6.1 Format Payload

```
ERP1|P|{sku}                         → produk
ERP1|B|{sku}|{batch_no}              → batch
ERP1|L|{warehouse_code}|{loc_code}   → lokasi/rak
ERP1|D|{doc_type}|{number}|{sig}     → dokumen (sig = base64url(hmac_sha256(payload, APP_KEY))[0..16])
```
- `ERP1` = versi skema (naik bila format berubah; scanner menolak versi tak dikenal dengan pesan "update label").
- Pemisah `|`; karakter `|` dilarang di SKU/kode (validasi di master data).
- QR Code level koreksi error **M** (15 %); ukuran modul cukup untuk label 25×25 mm.
- Dukungan scanner USB yang mengirim **prefix/suffix** (mis. `Enter`): parser men-trim whitespace/CR/LF.

### 6.6.2 Task QR

- [ ] **T-2.6.1 🔴 `QrPayloadService`** · Est. 6 j
  - `encode(QrPayload): string`, `decode(string): QrPayload` (lempar `InvalidQrException` bertipe: `format`, `version`, `signature`, `unknown_type`).
  - Verifikasi signature dengan `hash_equals` (timing-safe).
  - **AC:** unit test: payload valid, versi salah, signature diubah 1 karakter, spasi/CRLF berlebih, karakter unicode.

- [ ] **T-2.6.2 🔴 `QrGeneratorService`** · Est. 4 j — `svg(string $payload, int $size = 200): string`, `png(...)`; cache hasil per payload (key hash) 24 jam.

- [ ] **T-2.6.3 🔴 Cetak label QR** · Est. 14 j
  - Halaman `/qr/labels`: pilih jenis (produk/batch/rak), pilih item (multi-select, filter kategori/gudang), jumlah salinan per item, template (A4 3×8, A4 4×10, thermal 38×25, thermal 50×30).
  - Isi label: QR + SKU + nama (dipotong 2 baris) + (opsional) harga/rak/batch+expired.
  - Output PDF via DomPDF (`LabelSheetService`), preview di browser sebelum unduh. Template didefinisikan di `config/erp.php` (lebar, tinggi, kolom, baris, margin, ukuran font).
  - **AC:** cetak 100 label tanpa terpotong/bergeser pada printer uji (verifikasi fisik, lampirkan foto di PR); QR terbaca oleh 2 jenis perangkat (HP Android & iOS).

- [ ] **T-2.6.4 🔴 Halaman Scan (`/qr/scan`)** · Est. 20 j
  - Layout mobile-first: area kamera (atas), pemilih mode (Masuk / Keluar / Opname / Info), daftar hasil scan (bawah), tombol **Simpan/Post**.
  - Kamera: `html5-qrcode` (`facingMode: environment`, fps 10, qrbox adaptif), tombol senter (jika didukung), ganti kamera, izin kamera ditolak → tampilkan petunjuk + fallback input manual.
  - **Keyboard-wedge:** input tersembunyi yang selalu fokus; `Enter` memproses scan; fokus kembali otomatis setelah modal.
  - **Debounce:** payload yang sama dalam 1,5 dtk diabaikan; scan berhasil → bunyi beep pendek + getar (`navigator.vibrate`); gagal → bunyi berbeda + banner merah.
  - Mode **Masuk**: scan produk → baris muncul (qty default 1; scan produk sama menambah qty +1; ketuk angka untuk edit) → scan rak untuk menentukan lokasi (rak aktif berlaku untuk baris berikutnya) → pilih supplier/gudang → **Post** → buat GR otomatis.
  - Mode **Keluar**: scan produk → validasi stok (merah bila kurang) & saran rak (FEFO/stok terbanyak) → pilih tujuan (pelanggan/invoice/alasan) → **Post** → buat GI.
  - Mode **Opname**: scan rak lalu scan produk-produk → hitung otomatis → kirim ke sesi opname.
  - Mode **Info**: tampilkan kartu produk (nama, foto, stok per gudang/rak, harga jual bila berhak).
  - Draft scan tersimpan di `sessionStorage` agar tidak hilang saat halaman refresh/koneksi putus; tombol "Kosongkan".
  - **AC:** alur masuk 20 item < 2 menit; scan ganda tidak menambah qty; QR tidak dikenal menampilkan pesan spesifik; hasil Post identik dengan input manual.

- [ ] **T-2.6.5 🔴 Endpoint API scan** · Est. 8 j
  - `POST /api/qr/resolve` `{payload, mode, warehouse_id}` → `200 {type, data}` atau `422 {code, message}`; rate limit 120/menit/user; semua hasil dicatat di `qr_scans`.
  - `POST /api/qr/commit` `{mode, warehouse_id, items:[{product_id, qty, location_id, batch_no?}], ...}` → membuat dokumen draft→posted dalam 1 transaksi; **idempotent** via header `Idempotency-Key` (mencegah dokumen ganda saat klik dua kali / retry jaringan).
  - **AC:** mengirim `commit` dua kali dengan key sama ⇒ hanya 1 dokumen; response konsisten.

- [ ] **T-2.6.6 🟠 Verifikasi dokumen via QR** · Est. 6 j
  - Route `GET /verify/{type}/{number}/{sig}` (publik, throttle 30/menit): validasi signature → tampilkan ringkasan **minimal** (nomor, tanggal, status, jumlah item — tanpa harga & data pribadi).
  - **AC:** signature salah ⇒ halaman "Dokumen tidak valid" (HTTP 404), tanpa membocorkan apakah nomor ada.

- [ ] **T-2.6.7 🟡 Audit & laporan scan** · Est. 5 j — daftar `qr_scans` (filter user/mode/hasil/tanggal), statistik scan gagal per penyebab, ekspor.

---

# 7. FASE 3 — SALES & INVOICE (Sprint 4)

## 7.1 Skema Database

- [ ] **T-3.1.1 🔴 Migrasi sales** · Est. 6 j

  ```
  invoices        id, number(unique), customer_id, sales_id, warehouse_id,
                  issue_date date, due_date date,
                  status enum(draft|posted|partial|paid|void),
                  subtotal bigint, header_discount_type enum(percent|amount) null,
                  header_discount_value bigint, header_discount_amount bigint,
                  tax_rate_bp smallint, tax_base bigint, tax_amount bigint,
                  shipping_fee bigint, grand_total bigint,
                  paid_total bigint default 0, credited_total bigint default 0,
                  balance_due bigint,                       -- grand_total - paid_total - credited_total
                  cogs_total bigint default 0,              -- diisi saat posting
                  note, terms, posted_by/at, voided_by/at/reason, softDeletes
                  INDEX(customer_id), INDEX(sales_id, issue_date), INDEX(status, due_date)

  invoice_items   id, invoice_id, product_id, description, qty decimal(15,3),
                  unit_price bigint, discount_type enum(percent|amount) null, discount_value bigint,
                  discount_amount bigint, line_total bigint,
                  unit_cost_snapshot bigint null,           -- diisi saat posting
                  sort_order smallint

  payments        id, number(unique), invoice_id, customer_id, method enum(cash|transfer|qris|card|other),
                  account_id (akun kas/bank), amount bigint CHECK(amount>0), paid_at datetime,
                  ref_no nullable, note, status enum(valid|void), created_by, voided_by/at/reason

  credit_notes    id, number(unique), invoice_id, reason, subtotal, tax_amount, total,
                  return_to_stock bool, status enum(draft|posted|void), posted_by/at
  credit_note_items id, credit_note_id, invoice_item_id, qty, unit_price, line_total, unit_cost_snapshot
  ```
  - **AC:** `balance_due` selalu konsisten (dijaga oleh `RecalculateInvoiceBalance` setelah payment/credit note; dicek test properti).

## 7.2 Fitur Invoice

- [ ] **T-3.2.1 🔴 Form invoice (draft)** · Est. 20 j
  - Header: pelanggan (autocomplete, tampilkan sisa limit kredit & piutang berjalan), sales (default = user/`default_sales_id`), gudang, tanggal terbit, termin (auto-isi `due_date` = terbit + `payment_term_days`, bisa diubah), catatan.
  - Item: cari produk (nama/SKU/scan), tampilkan stok tersedia di gudang terpilih, qty, harga (default `sell_price`, hanya role berhak boleh ubah harga di bawah harga modal → peringatan), diskon (% / Rp), subtotal baris.
  - Ringkasan: Subtotal, Diskon header, DPP, PPN (toggle), Ongkir, **Grand Total**.
  - **Preview angka via server:** `POST /sales/invoices/preview` → `InvoiceCalculator` (debounce 300 ms). JS **tidak** menghitung sendiri; hanya menampilkan hasil server → angka form selalu sama dengan PDF & dashboard.
  - Simpan draft kapan saja; validasi penuh hanya saat Post.
  - **AC:** angka di form == angka setelah disimpan == angka di PDF (test E2E membandingkan ketiganya).

- [ ] **T-3.2.2 🔴 `PostInvoice` (Action)** · Est. 16 j
  - Urutan (satu transaksi):
    1. Lock invoice; pastikan status `draft`; periode akuntansi terbuka.
    2. Hitung ulang total lewat `InvoiceCalculator` (abaikan angka dari client).
    3. Cek **limit kredit**: `piutang_berjalan + grand_total ≤ credit_limit` (kecuali permission `invoice.override_credit`) → `CreditLimitExceededException`.
    4. Cek stok semua item (pesan gabungan semua item yang kurang).
    5. Buat `GoodsIssue` tipe `sale` → `StockService::decrease` → simpan `unit_cost_snapshot` ke tiap `invoice_items` & `cogs_total`.
    6. Buat jurnal J1 (penjualan) & J2 (HPP) (§9.2).
    7. Set `status=posted`, `posted_at/by`, `number` (jika belum), `balance_due = grand_total`.
    8. Jika `commission_basis = invoice_posted` → `AccrueCommission`.
    9. Activity log.
  - **AC:** gagal di langkah mana pun ⇒ rollback total (stok, jurnal, nomor tetap seperti semula — kecuali nomor yang sudah terpakai, tidak boleh ada nomor ganda).

- [ ] **T-3.2.3 🔴 Pembayaran (`RecordPayment`)** · Est. 12 j
  - Input: tanggal, metode, akun kas/bank, jumlah (default = sisa tagihan), referensi, catatan, (opsional) bukti transfer (upload).
  - Validasi: `0 < amount ≤ balance_due` (overpayment sesuai keputusan Q10).
  - Efek: tambah `paid_total`, hitung ulang `balance_due`, ubah status `partial/paid`, jurnal J4, `AccrueCommission` (jika basis = payment, **proporsional**), kirim notifikasi ke sales.
  - **Void payment**: hanya `finance`/`owner`; membalik jurnal & komisi; status invoice dihitung ulang.
  - **AC:** pembayaran 3× cicilan hingga lunas ⇒ status `paid`, total komisi == komisi penuh (tanpa selisih pembulatan; lihat T-4.4.3).

- [ ] **T-3.2.4 🟠 Credit Note / Retur penjualan** · Est. 14 j
  - Pilih invoice → pilih item & qty retur (≤ qty terjual − qty sudah diretur) → pilihan "kembalikan ke stok" (`IN_RETURN_SALE` dengan `unit_cost = unit_cost_snapshot` asal) → posting: kurangi `balance_due`; jika sudah lunas → sisa menjadi **kredit pelanggan** (atau refund — keputusan Q10) → jurnal J5 → `ReverseCommission` proporsional.
  - **AC:** retur penuh ⇒ DPP bersih 0, komisi bersih 0, stok kembali, HPP kembali.

- [ ] **T-3.2.5 🔴 `VoidInvoice`** · Est. 8 j
  - Syarat: belum ada payment valid (jika ada → harus void payment dulu) & belum ada credit note.
  - Efek: reversal stok (`StockService::reverse`), reversal jurnal, reversal komisi, status `void`; **nomor tidak dipakai ulang**; alasan wajib.
  - **AC:** setelah void, saldo stok, piutang, dan omzet dashboard kembali seperti sebelum posting.

- [ ] **T-3.2.6 🔴 PDF Invoice** · Est. 10 j
  - Kop perusahaan (logo, NPWP), data pelanggan, tabel item (kolom: No, Deskripsi, Qty, Satuan, Harga, Diskon, Jumlah), blok total, terbilang rupiah, info rekening bank, syarat & ketentuan, status stempel (LUNAS / VOID), **QR verifikasi dokumen**.
  - Helper `terbilang(int): string` ("satu juta dua ratus lima puluh ribu rupiah").
  - **AC:** PDF 1 halaman untuk ≤ 15 item, otomatis multi-halaman dengan header tabel berulang bila lebih; nilai persis sama dengan layar.

- [ ] **T-3.2.7 🟡 Kirim invoice** · Est. 6 j — email (queue) dengan lampiran PDF; tautan WhatsApp `wa.me` berisi ringkasan + link unduh bertoken (kedaluwarsa 7 hari).

## 7.3 Daftar, Piutang & Reminder

- [ ] **T-3.3.1 🔴 Daftar invoice** · Est. 8 j
  - Filter: tanggal, status, pelanggan, sales, jatuh tempo (belum/jatuh tempo/telat >N hari), rentang nominal; pencarian nomor/pelanggan; sorting; pagination server-side (25/50/100).
  - Status turunan **Jatuh Tempo / Telat X hari** dihitung di query (bukan disimpan) : `status IN (posted, partial) AND due_date < CURDATE()`.
  - Footer total: jumlah invoice, Σ grand_total, Σ terbayar, Σ sisa.
  - **AC:** total footer == hasil query agregat terpisah; 10.000 invoice tampil < 500 ms.

- [ ] **T-3.3.2 🟠 Halaman detail invoice** · Est. 6 j — timeline (dibuat, diposting, dibayar, retur, void), daftar payment, link GI & jurnal, tombol aksi sesuai status & permission.

- [ ] **T-3.3.3 🟡 Reminder jatuh tempo** · Est. 6 j — scheduler harian: H-3, H-0, H+3, H+7 → notifikasi ke sales & finance (database + email).

---

# 8. FASE 4 — PERBAIKAN RUMUS & KOMISI (Sprint 5)

> 🚨 Fase ini adalah **bug-fix + refactor berisiko tinggi** karena menyangkut angka uang. Urutan kerja **wajib**: (1) audit → (2) tulis test yang mengunci *perilaku yang benar* → (3) buat Calculator → (4) ganti semua pemakai → (5) rekonsiliasi data lama.

## 8.1 Audit Rumus

- [ ] **T-4.1.1 🔴 Inventarisasi** · Est. 8 j
  - Cari semua titik hitung: `grep -rnE "(\*|/|round\(|number_format|floatval|toFixed)" app resources routes`; tinjau juga query dashboard (`SUM(`, `selectRaw`), export, PDF, JS (`resources/js`).
  - Isi **Tabel Temuan** (lampirkan di `docs/audit-rumus.md`):

  | # | File:baris | Rumus saat ini | Seharusnya | Contoh angka salah | Dampak (Rp/periode) | Prioritas | Status |
  |---|---|---|---|---|---|---|---|
  | 1 | | | | | | | ☐ |

- [ ] **T-4.1.2 🔴 Daftar periksa bug umum** (centang bila ditemukan → buat sub-task perbaikan)
  - [ ] Pembulatan **per baris** vs **di total** berbeda antar layar → selisih 1–N rupiah
  - [ ] PPN dihitung dari subtotal **sebelum** diskon header
  - [ ] Diskon % dihitung dari harga **setelah PPN**
  - [ ] Ongkir ikut kena PPN / ikut dasar komisi padahal tidak seharusnya
  - [ ] `float` dipakai untuk uang (mis. `0.1 + 0.2`)
  - [ ] Qty desimal dikali harga dengan `float` → presisi hilang
  - [ ] Dashboard memasukkan invoice `draft`/`void` sebagai omzet
  - [ ] Dashboard memakai `created_at` (UTC) bukan `issue_date` → transaksi 00:00–06:59 WIB masuk hari sebelumnya
  - [ ] Retur/void/credit note tidak mengurangi omzet & komisi
  - [ ] Pembayaran parsial menghitung komisi penuh di pembayaran pertama
  - [ ] Komisi dihitung dari `grand_total` (termasuk PPN) bukan DPP
  - [ ] Komisi dihitung ulang memakai rate **terbaru** untuk invoice lama
  - [ ] `balance_due` tidak sinkron dengan jumlah payment
  - [ ] HPP memakai `products.cost_price` terkini, bukan `unit_cost_snapshot`
  - [ ] Join ke `invoice_items` menggandakan baris saat `SUM(invoices.grand_total)`
  - [ ] `COUNT(*)` invoice ganda karena join pembayaran
  - [ ] Persentase target dibagi nol saat target 0
  - [ ] Rata-rata (AOV) dibagi jumlah invoice termasuk void

- [ ] **T-4.1.3 🔴 Snapshot "sebelum" (baseline)** · Est. 4 j — ekspor angka 3 bulan terakhir (omzet, PPN, komisi per sales, laba kotor) sebelum perbaikan, simpan di `docs/baseline-YYYYMM.xlsx`, agar dampak perbaikan bisa dijelaskan ke stakeholder.

## 8.2 `InvoiceCalculator`

- [ ] **T-4.2.1 🔴 Implementasi** · Est. 12 j

  ```php
  <?php
  declare(strict_types=1);

  namespace App\Services\Sales;

  use App\DTO\{DiscountInput, InvoiceLineInput, InvoiceTotals, InvoiceLineResult};
  use App\Support\Money;

  final class InvoiceCalculator
  {
      /** @param list<InvoiceLineInput> $lines */
      public function calculate(
          array $lines,
          ?DiscountInput $headerDiscount = null,
          int $taxRateBp = 1100,        // 11,00 %
          bool $taxable = true,
          int $shippingFee = 0,
      ): InvoiceTotals {
          $results = [];
          $subtotal = 0;

          foreach ($lines as $i => $line) {
              $gross = Money::mulQty($line->qtyMilli, $line->unitPrice);
              $disc  = match ($line->discount?->type) {
                  'percent' => Money::percentOf($gross, $line->discount->value),  // value dalam basis points
                  'amount'  => min($line->discount->value, $gross),               // tidak boleh > gross
                  default   => 0,
              };
              $total = $gross - $disc;
              $subtotal += $total;
              $results[$i] = new InvoiceLineResult($gross, $disc, $total);
          }

          $headerDisc = match ($headerDiscount?->type) {
              'percent' => Money::percentOf($subtotal, $headerDiscount->value),
              'amount'  => min($headerDiscount->value, $subtotal),
              default   => 0,
          };

          $taxBase = $subtotal - $headerDisc;                         // DPP
          $tax     = $taxable ? Money::percentOf($taxBase, $taxRateBp) : 0;
          $grand   = $taxBase + $tax + $shippingFee;

          return new InvoiceTotals($results, $subtotal, $headerDisc, $taxBase, $tax, $shippingFee, $grand);
      }
  }
  ```
  - Aturan: **kelas murni** (tanpa DB/Auth/Carbon), input & output DTO `readonly`, semua nilai `int`.
  - Diskon dalam basis points (10 % = `1000`); UI mengonversi.
  - **Aturan pembulatan:** half-up ke rupiah terdekat, pada *tiga titik saja*: diskon baris %, diskon header %, PPN. Tidak ada pembulatan lain di mana pun.
  - **AC:** 25+ test unit (lihat §12.2) termasuk property-based: `grand_total == taxBase + tax + shipping` dan `Σ line_total == subtotal` untuk 1.000 input acak.

- [ ] **T-4.2.2 🔴 Ganti semua pemakai** · Est. 10 j
  - Form preview, `PostInvoice`, PDF, export Excel, dashboard, laporan → semuanya membaca **kolom tersimpan** (`grand_total`, `tax_base`, dst. yang diisi oleh calculator) atau memanggil calculator; **tidak ada** hitungan paralel.
  - Tambah test arsitektur Pest: `arch()->expect('App\Http\Controllers')->not->toUse(['round','floor','ceil','number_format'])`.
  - **AC:** `grep` titik hitung hasilnya hanya di `Services/` & `Support/Money.php`.

- [ ] **T-4.2.3 🔴 Rekonsiliasi data lama** · Est. 10 j
  - Command `invoice:recalc-audit {--from=} {--to=} {--fix}`: hitung ulang tiap invoice `posted` dengan calculator, bandingkan dengan nilai tersimpan, keluarkan CSV selisih (nomor, nilai lama, nilai baru, selisih).
  - `--fix` hanya boleh dijalankan setelah persetujuan finance; membuat **jurnal koreksi** (bukan mengubah jurnal lama) & mencatat di activity log.
  - **AC:** untuk data demo, selisih = 0 setelah `--fix`; tersedia laporan sebelum/sesudah.

## 8.3 Komisi — Skema

- [ ] **T-4.3.1 🔴 Migrasi komisi** · Est. 6 j

  ```
  commission_rules  id, name, type enum(flat|tiered|per_product|per_category),
                    basis enum(invoice_posted|payment_received) null,   -- null = ikut setting global
                    rate_bp int null,                                    -- untuk flat/per_product/per_category
                    product_id null, category_id null, sales_id null,    -- cakupan
                    period_scope enum(per_invoice|monthly_total),        -- tiered dihitung per invoice atau akumulasi bulanan
                    valid_from date, valid_to date null, priority smallint default 100,
                    is_active bool, created_by, softDeletes
  commission_tiers  id, rule_id, from_amount bigint, to_amount bigint null, rate_bp int
                    CHECK(to_amount IS NULL OR to_amount > from_amount)
  sales_targets     id, sales_id, period char(7) 'YYYY-MM', target_amount bigint, UNIQUE(sales_id, period)
  target_bonuses    id, min_achievement_pct smallint, bonus_rate_bp int, valid_from, valid_to
  commissions       id, sales_id, invoice_id, invoice_item_id null, payment_id null, credit_note_id null,
                    entry_type enum(accrual|reversal|adjustment),
                    period char(7),                          -- bulan pengakuan komisi
                    base_amount bigint,                      -- DPP yang dipakai (bisa negatif utk reversal)
                    rate_bp int, amount bigint,              -- amount boleh negatif
                    status enum(pending|approved|paid|cancelled),
                    rule_id null, rule_snapshot json,        -- salinan aturan saat dihitung
                    approved_by/at, payout_id null, note, created_at
                    INDEX(sales_id, period, status), INDEX(invoice_id)
  commission_payouts id, number(unique), sales_id, period, total bigint, status enum(draft|paid),
                     paid_at, account_id null, journal_id null, created_by
  ```
  - Prinsip: **tabel `commissions` adalah ledger** (accrual & reversal sama-sama baris; tidak pernah di-update jumlahnya).
  - **AC:** total komisi sales S pada periode P = `SUM(amount)` (query tunggal) dan cocok dengan slip.

## 8.4 Komisi — Logika

- [ ] **T-4.4.1 🔴 `CommissionRuleResolver`** · Est. 8 j
  - Memilih aturan untuk tiap **baris invoice** berdasarkan prioritas: `per_product` → `per_category` → aturan khusus sales → aturan global; hanya yang `is_active` dan `valid_from ≤ issue_date ≤ valid_to`; bila prioritas sama → ambil yang paling spesifik, lalu `id` terbaru; hasilnya deterministik.
  - **AC:** test matriks: produk A (5 %) di kategori X (3 %), sales S (2 %), global (1 %) → baris produk A = 5 %, produk lain di X = 3 %, di luar X oleh S = 2 %, sisanya 1 %.

- [ ] **T-4.4.2 🔴 `CommissionCalculator`** · Est. 16 j

  ```php
  final class CommissionCalculator
  {
      /** Komisi atas satu baris DPP. */
      public function flat(int $baseAmount, int $rateBp): int
      {
          return Money::percentOf($baseAmount, $rateBp);
      }

      /**
       * Bertingkat MARGINAL: tiap rupiah dikenai rate tier tempat ia berada.
       * @param list<array{from:int,to:?int,rate_bp:int}> $tiers  urut naik, tanpa celah
       * @param int $alreadyAccumulated omzet bulan berjalan sebelum transaksi ini (utk period_scope=monthly_total)
       */
      public function tiered(int $baseAmount, array $tiers, int $alreadyAccumulated = 0): int
      {
          $start = $alreadyAccumulated;
          $end   = $alreadyAccumulated + $baseAmount;
          $sum   = 0;
          foreach ($tiers as $t) {
              $lo = max($start, $t['from']);
              $hi = min($end, $t['to'] ?? PHP_INT_MAX);
              if ($hi > $lo) {
                  $sum += Money::percentOf($hi - $lo, $t['rate_bp']);
              }
          }
          return $sum;
      }

      /** Proporsi pembayaran (cash basis) dengan alokasi selisih pembulatan di pembayaran terakhir. */
      public function forPayment(int $invoiceCommission, int $invoiceGrandTotal, int $paymentAmount,
                                 int $alreadyAccrued, bool $isFinalPayment): int
      {
          if ($isFinalPayment) {
              return $invoiceCommission - $alreadyAccrued;        // sisa persis → total tidak meleset
          }
          return intdiv($invoiceCommission * $paymentAmount * 2 + $invoiceGrandTotal, $invoiceGrandTotal * 2);
      }
  }
  ```
  - **Bonus target:** setelah akhir bulan, jika `pencapaian% ≥ min_achievement_pct` → buat baris `adjustment` = `bonus_rate_bp × DPP bersih bulan itu` (tier bonus tertinggi yang terpenuhi, bukan kumulatif).
  - **Potongan keterlambatan (opsional, Q2):** jika pelunasan melewati `due_date + N` hari → komisi baris pembayaran itu dikali `(1 − penalty%)`; aturan & hasil tersimpan di `rule_snapshot`.
  - **AC:** tiered: tier 0–50jt 1 %, 50–100jt 2 %, >100jt 3 %, DPP 120jt → 500.000 + 1.000.000 + 600.000 = **2.100.000**; kasus pembayaran 3 kali dengan sisa pembulatan → total = komisi penuh persis.

- [ ] **T-4.4.3 🔴 Actions komisi** · Est. 16 j
  - `AccrueCommission(Invoice|Payment)`: ambil basis dari setting/aturan → hitung per baris → simpan baris `accrual` (status `pending`) + `rule_snapshot` → idempoten (kunci unik `invoice_id + payment_id + entry_type + invoice_item_id`).
  - `ReverseCommission(CreditNote|VoidInvoice|VoidPayment)`: baris `reversal` bernilai negatif proporsional, merujuk baris asal; bila periode asal sudah `paid` → reversal tetap dicatat di periode berjalan (menjadi **potongan** pada payout berikutnya; bisa membuat saldo negatif → carry-forward).
  - `ApproveCommission(period, sales)`: `pending → approved` (permission `commission.approve`), mencatat approver & waktu; tidak bisa approve komisi milik sendiri bila user = sales (segregation of duties).
  - `CreatePayout(period, sales)`: gabungkan `approved` → `commission_payouts` → jurnal J7 → status `paid`.
  - **AC:** void invoice setelah komisi `paid` → muncul potongan di payout berikutnya; tidak ada baris yang pernah di-update jumlahnya.

- [ ] **T-4.4.4 🔴 Command rekalkulasi** · Est. 8 j
  - `commission:recalculate {period} {--sales=} {--dry-run}`: hitung ulang dengan `rule_snapshot` (bukan aturan baru) kecuali `--use-current-rules`; keluarkan CSV selisih; tanpa `--dry-run` menulis baris `adjustment` (tidak menimpa).
  - **AC:** dry-run tidak mengubah data (diverifikasi test hitung baris sebelum/sesudah).

## 8.5 UI Komisi & Dashboard Sales

- [ ] **T-4.5.1 🔴 Halaman aturan komisi** · Est. 12 j — daftar & form aturan (tipe, cakupan, tier dinamis dengan validasi tanpa celah/overlap, periode berlaku, prioritas); simulator "Hitung contoh": input DPP → tampilkan rincian per tier.
- [ ] **T-4.5.2 🔴 Halaman komisi (finance)** · Est. 10 j — daftar per periode & sales: pending/approved/paid, pilih banyak → approve, buat payout; rincian per invoice (DPP, rate, aturan yang dipakai, status).
- [ ] **T-4.5.3 🔴 Halaman "Komisi Saya" (sales)** · Est. 8 j — kartu: komisi bulan ini (pending/approved/paid), proyeksi bila semua piutang lunas, pencapaian target (progress bar), potongan/reversal; tabel per invoice; tombol unduh **slip komisi PDF**.
- [ ] **T-4.5.4 🟠 Penjelasan transparan** · Est. 5 j — tiap baris komisi punya tombol "Cara hitung" (modal: DPP, aturan, rate, proporsi pembayaran, hasil) bersumber dari `rule_snapshot`.
- [ ] **T-4.5.5 🟠 Target sales** · Est. 6 j — input target bulanan per sales (import Excel), riwayat pencapaian.

---

# 9. FASE 5 — KEUANGAN & REKAP (Sprint 6)

## 9.1 Skema & Ledger

- [ ] **T-5.1.1 🔴 Migrasi keuangan** · Est. 6 j

  ```
  accounts           id, code(unique), name, type enum(asset|liability|equity|revenue|cogs|expense),
                     normal_balance enum(debit|credit), parent_id null, is_cash bool, is_bank bool,
                     is_system bool (tidak boleh dihapus), is_active
  journals           id, number(unique), date date, description, source_type, source_id (polymorphic),
                     status enum(draft|posted|void), reversal_of null, total_debit bigint, total_credit bigint,
                     created_by, approved_by null, posted_at
                     INDEX(date), INDEX(source_type, source_id)
  journal_lines      id, journal_id, account_id, debit bigint default 0, credit bigint default 0, memo,
                     partner_type/id null (pelanggan/supplier, untuk sub-ledger AR/AP)
                     CHECK((debit = 0) <> (credit = 0))          -- salah satu saja yang terisi
                     INDEX(account_id), INDEX(partner_type, partner_id)
  cash_transactions  id, type enum(in|out|transfer), account_id, counter_account_id, amount, date,
                     category, ref_no, note, attachment_path, journal_id
  expenses           id, number, date, account_id (akun beban), paid_from_account_id, vendor,
                     amount, tax_amount, note, attachment_path, status enum(draft|posted|void), journal_id
  bills              id, number, supplier_id, goods_receipt_id null, bill_date, due_date,
                     total, paid_total, balance_due, status enum(open|partial|paid|void)
  bill_payments      id, bill_id, account_id, amount, paid_at, ref_no, journal_id
  accounting_periods id, year, month, status enum(open|closed), closed_by/at, UNIQUE(year, month)
  ```

- [ ] **T-5.1.2 🔴 `LedgerService::post(JournalData)`** · Est. 12 j
  - Validasi: ≥ 2 baris · Σdebit == Σkredit (jika tidak → `UnbalancedJournalException` dengan selisih) · semua akun aktif & bukan akun induk · tanggal berada di periode **open** (`PeriodClosedException`).
  - `reverse(Journal, reason)`: jurnal kebalikan, `reversal_of` terisi, tanggal = tanggal reversal (atau tanggal asal bila periode asal masih open — keputusan finance).
  - **Jurnal tidak pernah di-update/dihapus** setelah `posted` (guard di model + test arsitektur).
  - **AC:** test property: 1.000 jurnal acak seimbang → trial balance total debit == total kredit.

- [ ] **T-5.1.3 🔴 COA standar (seeder)** · Est. 4 j

  | Kode | Akun | Tipe |
  |---|---|---|
  | 1-1100 | Kas | asset (cash) |
  | 1-1200 | Bank | asset (bank) |
  | 1-1300 | Piutang Usaha | asset |
  | 1-1400 | Persediaan Barang | asset |
  | 1-1450 | Barang Dalam Perjalanan | asset |
  | 1-1500 | PPN Masukan | asset |
  | 2-1100 | Hutang Usaha | liability |
  | 2-1200 | PPN Keluaran | liability |
  | 2-1300 | Hutang Komisi | liability |
  | 2-1400 | Kredit Pelanggan (deposit) | liability |
  | 3-1000 | Modal | equity |
  | 3-2000 | Laba Ditahan | equity |
  | 4-1000 | Penjualan | revenue |
  | 4-1100 | Retur Penjualan | revenue (kontra) |
  | 4-1200 | Diskon Penjualan | revenue (kontra) |
  | 5-1000 | Harga Pokok Penjualan | cogs |
  | 5-1100 | Selisih Persediaan | cogs |
  | 6-1000 | Beban Gaji | expense |
  | 6-1100 | Beban Komisi Sales | expense |
  | 6-1200 | Beban Sewa | expense |
  | 6-1300 | Beban Listrik/Air/Internet | expense |
  | 6-1400 | Beban Transportasi/Ongkir | expense |
  | 6-1500 | Beban Barang Rusak/Hilang | expense |
  | 6-9000 | Beban Lain-lain | expense |

## 9.2 Jurnal Otomatis (Kontrak)

| ID | Event | Debit | Kredit |
|---|---|---|---|
| **J1** | Invoice diposting | Piutang Usaha = grand_total | Penjualan = subtotal − diskon header (DPP) + diskon baris ditampilkan sebagai kontra* ; PPN Keluaran = tax; Pendapatan Ongkir/Beban Transportasi = shipping |
| **J2** | HPP saat invoice diposting | HPP = Σ(qty × unit_cost_snapshot) | Persediaan |
| **J3** | Barang masuk diposting | Persediaan (+ PPN Masukan bila ada) | Hutang Usaha |
| **J4** | Pembayaran invoice | Kas/Bank (sesuai akun dipilih) | Piutang Usaha |
| **J5** | Credit note (retur) | Retur Penjualan + PPN Keluaran | Piutang Usaha (atau Kredit Pelanggan bila lunas) |
| **J5b** | Retur kembali ke stok | Persediaan = qty × unit_cost_snapshot | HPP |
| **J6** | Pembayaran hutang | Hutang Usaha | Kas/Bank |
| **J7** | Payout komisi | Hutang Komisi (atau Beban Komisi bila langsung bayar) | Kas/Bank |
| **J7a** | Akrual komisi bulanan (opsional) | Beban Komisi Sales | Hutang Komisi |
| **J8** | Adjustment stok (opname +) | Persediaan | Selisih Persediaan |
| **J8b** | Adjustment stok (opname −) | Selisih Persediaan | Persediaan |
| **J9** | Barang rusak/hilang | Beban Barang Rusak/Hilang | Persediaan |
| **J10** | Transfer — dikirim | Barang Dalam Perjalanan | Persediaan |
| **J11** | Transfer — diterima | Persediaan | Barang Dalam Perjalanan |
| **J12** | Biaya operasional | Beban terkait (+ PPN Masukan) | Kas/Bank |

\* Keputusan penyajian diskon (kontra-akun vs netto) harus disepakati finance sebelum T-5.2.1; **apa pun pilihannya, DPP harus sama dengan `invoices.tax_base`.**

- [ ] **T-5.2.1 🔴 Listener/Action jurnal otomatis J1–J12** · Est. 24 j
  - Setiap jurnal memiliki `source_type/source_id` ke dokumen asal; satu dokumen tidak boleh menghasilkan jurnal `posted` ganda (kunci unik logis + test idempotensi).
  - **AC:** untuk skenario E2E (§12.2 #E2E-1), setiap akun bernilai sesuai ekspektasi; **Σ Persediaan di GL == nilai persediaan di `stock_balances`** (test rekonsiliasi); **Σ Piutang Usaha di GL == Σ `invoices.balance_due`**.

- [ ] **T-5.2.2 🟠 Jurnal manual & approval** · Est. 10 j — form jurnal (baris dinamis, indikator selisih real-time), status draft → approval (`finance.journal.approve`, tidak boleh oleh pembuat yang sama) → posted.
- [ ] **T-5.2.3 🟠 Biaya (Expenses) & Kas** · Est. 12 j — input biaya + lampiran, transfer antar kas/bank, setoran/penarikan; semuanya menghasilkan jurnal.
- [ ] **T-5.2.4 🟠 Hutang (Bills) & pembayaran** · Est. 10 j — bill dibuat dari GR (atau manual), cicilan, status, jurnal J6.
- [ ] **T-5.2.5 🟠 Tutup periode** · Est. 8 j — `CloseAccountingPeriod`: validasi tidak ada dokumen draft di periode (peringatan), semua jurnal seimbang, rekonsiliasi lolos → status `closed`; buka kembali hanya `owner` dengan alasan (tercatat). Middleware `EnsurePeriodOpen` pada semua aksi posting.

## 9.3 Laporan

Semua laporan: filter periode & gudang, ekspor Excel + PDF, header berisi nama perusahaan, periode, waktu cetak, dan pencetak. Angka dari **GL (journal_lines)** kecuali disebut lain.

- [ ] **T-5.3.1 🔴 Rekap Keuangan** (harian/bulanan/tahunan) · Est. 10 j
  - Kolom: periode, Pemasukan kas, Pengeluaran kas, Selisih, Saldo akhir kas/bank; drill-down ke daftar transaksi.
  - Sumber: `journal_lines` pada akun `is_cash/is_bank`.
- [ ] **T-5.3.2 🔴 Laba Rugi** · Est. 10 j
  - Struktur: Penjualan − Retur − Diskon = **Penjualan Bersih** → − HPP = **Laba Kotor** → − Σ Beban (rinci per akun) = **Laba Bersih**; kolom pembanding periode sebelumnya & % perubahan; margin kotor % dan margin bersih %.
- [ ] **T-5.3.3 🔴 Arus Kas** · Est. 8 j — metode langsung: penerimaan dari pelanggan, pembayaran ke supplier, beban operasional, lainnya; saldo awal + arus = saldo akhir (harus sama dengan saldo akun kas/bank).
- [ ] **T-5.3.4 🟠 Neraca** · Est. 8 j — Aset = Kewajiban + Ekuitas (+ laba berjalan); validasi keseimbangan, tampilkan peringatan bila selisih.
- [ ] **T-5.3.5 🟠 Buku Besar & Neraca Saldo** · Est. 8 j — per akun dengan saldo berjalan; trial balance total debit == kredit.
- [ ] **T-5.3.6 🔴 Umur Piutang (AR Aging)** · Est. 8 j — bucket 0–30 / 31–60 / 61–90 / >90 hari **sejak jatuh tempo**, plus kolom "belum jatuh tempo"; per pelanggan, drill-down ke invoice; query agregat dengan `CASE WHEN DATEDIFF(:asof, due_date)`.
- [ ] **T-5.3.7 🟠 Umur Hutang (AP Aging)** · Est. 6 j.
- [ ] **T-5.3.8 🔴 Laporan Penjualan** · Est. 10 j — per produk, kategori, pelanggan, sales, hari/bulan; kolom: qty, omzet (DPP), HPP, laba kotor, margin %. Laba memakai `unit_cost_snapshot`.
- [ ] **T-5.3.9 🟠 Laporan PPN** · Est. 5 j — PPN keluaran (dari invoice) − PPN masukan (dari GR/biaya) = kurang/lebih bayar.
- [ ] **T-5.3.10 🟠 Laporan Komisi** · Est. 5 j — per sales/periode: accrual, reversal, adjustment, dibayar, saldo hutang komisi.
- [ ] **T-5.3.11 🔴 Rekonsiliasi otomatis** · Est. 8 j
  - Halaman & command `reconcile:check {period}`: (a) Σ penjualan GL == Σ `tax_base` invoice posted − credit note; (b) Piutang GL == Σ `balance_due`; (c) Persediaan GL == nilai `stock_balances`; (d) Σ payment == mutasi kas dari pembayaran; (e) komisi: Σ `commissions.amount` == Hutang Komisi + dibayar.
  - Hasil: tabel ✅/❌ + selisih; jalankan harian & kirim peringatan bila ❌.
  - **AC:** data demo lolos semua cek; sengaja merusak 1 angka di test ⇒ cek terkait ❌.

---

# 10. FASE 6 — DASHBOARD (Sprint 7)

## 10.1 Aturan Umum Dashboard

- Filter global: rentang tanggal (preset + kustom), gudang, (khusus sales/finance) sales. State di query string agar bisa dibagikan.
- **Definisi metrik harus seragam** (satu kelas per dashboard, dipakai juga oleh laporan):
  - *Omzet* = Σ `invoices.tax_base` (DPP) status ∈ {posted, partial, paid} − Σ `credit_notes` (DPP) pada periode `issue_date`.
  - *Piutang* = Σ `balance_due` status ∈ {posted, partial}.
  - *Piutang jatuh tempo* = piutang dengan `due_date < CURDATE()`.
  - *Laba kotor* = Omzet − Σ(qty × `unit_cost_snapshot`) − (HPP retur dikembalikan).
- Cache `Cache::remember("dash:{role}:{userId}:{hash(filter)}", 300, …)` + tombol **Segarkan** + label "diperbarui HH:MM".
- Semua grafik punya: loading skeleton, state kosong, tooltip format Rupiah, aksesibilitas (ringkasan teks `aria-label`).
- Setiap kartu KPI bisa diklik → daftar rinci terfilter.
- Pembanding: ▲▼ % vs periode sebelumnya yang sama panjang.
- Layout grid Tailwind: `grid-cols-1 sm:grid-cols-2 xl:grid-cols-4` untuk KPI; grafik `lg:col-span-2`.

## 10.2 Dashboard Gudang

- [ ] **T-6.1.1 🔴 KPI Gudang** · Est. 8 j

  | KPI | Definisi | Sumber |
  |---|---|---|
  | Total SKU aktif | `COUNT(products WHERE is_active)` | products |
  | Nilai persediaan | `Σ qty × avg_cost` | stock_balances |
  | Barang masuk (periode) | Σ qty movement `direction=in` bertipe pembelian/retur/transfer-masuk | stock_movements |
  | Barang keluar (periode) | Σ qty movement `direction=out` | stock_movements |
  | Stok menipis | produk dengan Σ qty ≤ `min_stock` | stock_balances |
  | Dokumen tertunda | GR/GI/Transfer/Opname berstatus draft/in_transit/submitted | dokumen |

- [ ] **T-6.1.2 🔴 Widget & grafik** · Est. 14 j
  1. **Tren masuk vs keluar** (garis, harian ≤ 31 hari / mingguan > 31 hari) — 2 seri.
  2. **Stok menipis** (tabel 10 teratas: SKU, nama, stok, minimum, rak; tombol "Buat penerimaan").
  3. **Hampir kedaluwarsa** (30/60/90 hari; hanya `track_batch`).
  4. **Fast moving Top 10** (bar horizontal; Σ qty keluar 30 hari) & **Slow/dead stock** (stok > 0 dan tanpa pergerakan keluar > 90 hari; tampilkan nilai tertahan).
  5. **Komposisi nilai persediaan per kategori** (donut).
  6. **Okupansi rak** (opsional): jumlah rak terisi / total.
  7. **Aktivitas scan terbaru** (10 terakhir: waktu, user, mode, hasil).
  8. **Pintasan**: Scan Masuk · Scan Keluar · Opname · Cetak Label.
  - **AC:** semua angka tervalidasi terhadap query manual (test dengan dataset terkontrol); halaman < 1,2 dtk (cache dingin) dengan 100 ribu movement.

- [ ] **T-6.1.3 🟠 Query contoh (referensi, jangan di-copy mentah — sesuaikan nama kolom)**

  ```sql
  -- Tren masuk vs keluar per hari
  SELECT DATE(created_at) AS d,
         SUM(CASE WHEN direction='in'  THEN qty END) AS qty_in,
         SUM(CASE WHEN direction='out' THEN qty END) AS qty_out
  FROM stock_movements
  WHERE created_at >= :from AND created_at < :to_plus_1  -- ingat konversi WIB→UTC pada batas
    AND (:wh IS NULL OR warehouse_id = :wh)
  GROUP BY DATE(created_at) ORDER BY d;

  -- Stok menipis
  SELECT p.id, p.sku, p.name, p.min_stock, SUM(b.qty) AS stock
  FROM products p JOIN stock_balances b ON b.product_id = p.id
  WHERE p.is_active = 1
  GROUP BY p.id HAVING stock <= p.min_stock ORDER BY (stock - p.min_stock) LIMIT 10;

  -- Dead stock
  SELECT p.id, p.name, SUM(b.qty) qty, SUM(b.qty*b.avg_cost) nilai, MAX(m.created_at) last_out
  FROM products p JOIN stock_balances b ON b.product_id=p.id
  LEFT JOIN stock_movements m ON m.product_id=p.id AND m.direction='out'
  GROUP BY p.id HAVING qty > 0 AND (last_out IS NULL OR last_out < NOW() - INTERVAL 90 DAY);
  ```
  - ⚠ Konversi batas tanggal WIB↔UTC wajib lewat helper `Period::toUtcRange($from, $to)`; jangan menulis `DATE(created_at)` mentah untuk laporan harian bisnis (gunakan `CONVERT_TZ` atau kolom tanggal bisnis).

## 10.3 Dashboard Sales

- [ ] **T-6.2.1 🔴 KPI Sales** · Est. 8 j

  | KPI | Definisi |
  |---|---|
  | Omzet | lihat §10.1 |
  | Jumlah invoice | `COUNT(DISTINCT id)` status posted/partial/paid |
  | Rata-rata nilai invoice (AOV) | Omzet ÷ jumlah invoice (0 bila jumlah 0) |
  | Piutang berjalan | lihat §10.1 |
  | Piutang jatuh tempo | lihat §10.1 + jumlah invoice |
  | Pencapaian target | Omzet ÷ target × 100 (tampilkan "—" bila target 0/kosong) |
  | Margin kotor % | Laba kotor ÷ Omzet |
  | Komisi (pending / approved / paid) | `SUM(commissions.amount)` per status & periode |

- [ ] **T-6.2.2 🔴 Widget & grafik** · Est. 16 j
  1. **Omzet vs target** (garis + garis target putus-putus, per hari kumulatif bulan berjalan; proyeksi akhir bulan).
  2. **Omzet per sales** (bar; hanya manager/owner/finance).
  3. **Omzet per kategori** (donut) & **Top 10 produk** (qty & Rp) & **Top 10 pelanggan**.
  4. **Funnel status invoice** (draft → posted → partial → paid; jumlah & nilai).
  5. **Tabel invoice jatuh tempo** (tombol cepat: *Catat Pembayaran*, *Kirim Pengingat*).
  6. **Blok Komisi** — kartu 3 status, progress target, tabel komisi per invoice (SKU rincian via modal "Cara hitung"), tren komisi 6–12 bulan.
  7. **Ranking sales** (peringkat omzet, pencapaian %, komisi) — hanya peran manajer.
  - Peran `sales`: semua widget otomatis difilter `sales_id` miliknya, widget "per sales" & "ranking" disembunyikan.
  - **AC:** total pada dashboard == total laporan penjualan == total daftar invoice (periode & filter sama) — test rekonsiliasi lintas halaman; sales A tidak melihat angka sales B (test otorisasi).

- [ ] **T-6.2.3 🟠 Query contoh omzet & komisi**

  ```php
  // Omzet bersih per bulan (hindari join ke items agar tidak menggandakan baris)
  $invoice = Invoice::query()
      ->whereIn('status', [Posted, Partial, Paid])
      ->whereBetween('issue_date', [$from, $to])
      ->when($salesId, fn ($q) => $q->where('sales_id', $salesId))
      ->selectRaw("DATE_FORMAT(issue_date,'%Y-%m') ym, SUM(tax_base) omzet, COUNT(*) n")
      ->groupBy('ym');

  $retur = CreditNote::query()->where('status','posted')
      ->join('invoices','invoices.id','=','credit_notes.invoice_id')
      ->whereBetween('credit_notes.created_at', [$fromUtc, $toUtc])   // sesuai tanggal retur
      ->selectRaw("DATE_FORMAT(CONVERT_TZ(credit_notes.created_at,'+00:00','+07:00'),'%Y-%m') ym,
                   SUM(credit_notes.total - credit_notes.tax_amount) retur")
      ->groupBy('ym');
  // Gabungkan di PHP per 'ym' (2 query ringan) — bukan satu query dengan join ganda.
  ```

## 10.4 Dashboard Keuangan

- [ ] **T-6.3.1 🔴 KPI Keuangan** · Est. 8 j

  | KPI | Definisi |
  |---|---|
  | Saldo kas & bank | saldo akun `is_cash/is_bank` per tanggal akhir periode (rinci per akun) |
  | Pemasukan / Pengeluaran | mutasi debit/kredit akun kas-bank dalam periode |
  | Laba kotor / Laba bersih | dari Laba Rugi (§T-5.3.2) |
  | Total piutang / Total hutang | saldo akun 1-1300 / 2-1100 (sama dengan aging) |
  | Komisi menunggu approval / siap dibayar | `commissions` pending / approved |
  | Selisih rekonsiliasi | jumlah cek ❌ dari T-5.3.11 (0 = hijau) |

- [ ] **T-6.3.2 🔴 Widget & grafik** · Est. 14 j
  1. **Arus kas bulanan** (bar bertumpuk masuk/keluar + garis saldo).
  2. **Laba rugi 12 bulan** (garis: penjualan bersih, laba kotor, laba bersih).
  3. **Komposisi beban** (donut, Top 6 + "lainnya").
  4. **AR aging** & **AP aging** (bar bertumpuk per bucket; klik → daftar).
  5. **Tagihan jatuh tempo minggu ini** (piutang & hutang, dua tab).
  6. **Antrian approval** (komisi, jurnal manual, opname, void).
  7. **Status periode** (open/closed) + tombol tutup periode (permission).
  - **AC:** Σ kartu saldo kas == saldo akun GL; aging total == saldo piutang/hutang GL.

- [ ] **T-6.4.1 🟠 Performa dashboard** · Est. 10 j
  - Tabel ringkasan `daily_sales_summary(date, sales_id, warehouse_id, invoice_count, tax_base, cogs, tax, paid)` & `daily_stock_summary(date, warehouse_id, qty_in, qty_out, value)` diisi **scheduler** tiap 15 menit + rebuild harian; dashboard membaca ringkasan untuk rentang > 31 hari.
  - Command `dashboard:warm-cache` jam 06:00 untuk role owner/finance.
  - **AC:** rentang 1 tahun < 1 detik; angka ringkasan == query langsung (test sampling 30 hari acak).

- [ ] **T-6.4.2 🟡 Ekspor & berbagi** · Est. 5 j — unduh PDF dashboard (snapshot), kirim ringkasan mingguan ke email owner (scheduler Senin 07:00).

---

# 11. FASE 7 — QA, KEAMANAN, DEPLOYMENT (Sprint 8)

## 11.1 Strategi Test

| Level | Cakupan | Target |
|---|---|---|
| Unit | `Money`, `InvoiceCalculator`, `CommissionCalculator`, `RuleResolver`, `ValuationService`, `QrPayloadService`, `terbilang` | 100 % baris, termasuk batas & negatif |
| Feature | tiap Action (sukses + semua jalur gagal), Policy, FormRequest, endpoint API | ≥ 80 % Services/Actions |
| Arsitektur (`arch()`) | controller tidak berisi hitungan uang; `StockMovement`/`Journal` tidak ada update/delete; Enum dipakai (bukan string) | lulus |
| E2E (Dusk/Playwright) | login → buat invoice → post → bayar → lihat komisi; scan masuk → scan keluar | 5 alur kritis |
| Concurrency | stok rebutan, nomor dokumen paralel, double-submit commit | lulus 20× berturut |
| Performa | 100 ribu movement, 50 ribu invoice | halaman utama < 1 dtk |

- [ ] **T-7.1.1 🔴 Dataset uji terkontrol** · Est. 8 j — factory + skenario deterministik (angka bulat mudah diverifikasi) untuk test dashboard & laporan.
- [ ] **T-7.1.2 🔴 Test E2E-1 "Siklus lengkap"** · Est. 12 j — Beli 100 pcs @ 10.000 → jual 40 pcs @ 15.000 diskon 10 % PPN 11 % → bayar 2 kali → retur 5 pcs → void bayar terakhir; di setiap langkah assert: stok, HPP, jurnal (semua akun), piutang, komisi, angka dashboard & laporan.
- [ ] **T-7.1.3 🔴 Test otorisasi** (`AuthorizationMatrixTest`) · Est. 6 j.
- [ ] **T-7.1.4 🟠 Test regresi rumus** · Est. 4 j — seluruh tabel kasus §12.2 sebagai dataset Pest; kasus baru dari bug produksi **wajib** ditambahkan.

## 11.2 Keamanan

- [ ] **T-7.2.1 🔴** Validasi FormRequest semua input; `{{ }}` (escape) default, `{!! !!}` dilarang kecuali dibersihkan (lint).
- [ ] **T-7.2.2 🔴** CSRF, SameSite=Lax, cookie `secure` + `httpOnly`, HSTS, header keamanan (CSP dasar, X-Frame-Options, Referrer-Policy) via middleware.
- [ ] **T-7.2.3 🔴** Rate limiting: login (5/mnt), API scan (120/mnt), verifikasi publik (30/mnt), unduh PDF (20/mnt).
- [ ] **T-7.2.4 🔴** Upload: whitelist mime & ekstensi, batas ukuran, nama acak, disk privat untuk lampiran keuangan (diakses via route bertanda tangan `URL::temporarySignedRoute`).
- [ ] **T-7.2.5 🔴** `$fillable` eksplisit; larang `$guarded = []`; cek IDOR (semua route model binding lewat scope otorisasi).
- [ ] **T-7.2.6 🟠** Activity log untuk: login gagal berulang, perubahan harga/rule komisi/setting, void, approve, buka periode, ubah role.
- [ ] **T-7.2.7 🟠** Backup DB harian (+ mingguan offsite), retensi 30 hari, **uji restore** tiap kuartal (dokumentasikan hasil).
- [ ] **T-7.2.8 🟠** `composer audit` & `npm audit` di CI; dependabot mingguan.
- [ ] **T-7.2.9 🟡** 2FA (TOTP) untuk owner/finance.

## 11.3 Performa

- [ ] **T-7.3.1 🔴** Hilangkan N+1 (Debugbar: ≤ 15 query/halaman daftar); `Model::preventLazyLoading(! app()->isProduction())`.
- [ ] **T-7.3.2 🔴** Review index berdasarkan `EXPLAIN` dari query dashboard/laporan; dokumentasikan di `docs/indexes.md`.
- [ ] **T-7.3.3 🟠** Queue untuk: ekspor besar, PDF massal, import, rekalkulasi komisi, email; Horizon/Supervisor + retry/backoff + failed jobs dipantau.
- [ ] **T-7.3.4 🟠** Aset: `vite build`, lazy-load Chart.js & html5-qrcode hanya di halaman yang memerlukan; gambar produk thumbnail.
- [ ] **T-7.3.5 🟡** Lighthouse mobile ≥ 80 untuk `/qr/scan` & dashboard.

## 11.4 Deployment & Serah Terima

- [ ] **T-7.4.1 🔴 Environment** · staging & production terpisah; `.env.example` lengkap; `APP_DEBUG=false`; log harian; error tracker (Sentry/Flare).
- [ ] **T-7.4.2 🔴 Proses rilis** · `php artisan migrate --force`, `optimize`, `queue:restart`; scheduler via cron `* * * * * php artisan schedule:run`; Supervisor untuk `queue:work`; **rollback plan** tertulis.
- [ ] **T-7.4.3 🔴 Migrasi data awal** · template Excel: saldo awal stok per produk/rak/batch (dicatat sebagai `IN_OPENING`), piutang berjalan per invoice, hutang berjalan, saldo kas/bank, saldo akun lain (jurnal pembuka); **verifikasi**: Σ nilai persediaan == saldo akun, Σ piutang == saldo akun; sign-off finance sebelum go-live.
- [ ] **T-7.4.4 🟠 Dokumentasi** · panduan pengguna per role (PDF), video pendek: scan masuk/keluar, buat invoice, catat pembayaran, lihat komisi; dokumentasi teknis (`docs/`): arsitektur, rumus, jurnal, runbook insiden.
- [ ] **T-7.4.5 🔴 UAT** · skenario uji per role (daftar centang) dijalankan user nyata di staging; bug dicatat & diprioritaskan; **sign-off tertulis** dari gudang, sales, finance, owner.
- [ ] **T-7.4.6 🟠 Hypercare** · 2 minggu pasca go-live: pemantauan harian rekonsiliasi, respon bug ≤ 4 jam kerja (kritis), review angka mingguan bersama finance.

### Checklist Go-Live

- [ ] Semua test CI hijau di `main` · [ ] UAT sign-off · [ ] Saldo awal terverifikasi
- [ ] Backup & uji restore terakhir sukses · [ ] Rekonsiliasi (T-5.3.11) semua ✅
- [ ] Akun default (`admin@…`) diganti/dinonaktifkan · [ ] Seeder demo tidak jalan di production
- [ ] HTTPS + header keamanan aktif · [ ] Cron & queue worker berjalan · [ ] Error tracker menerima event uji
- [ ] Label QR sudah dicetak & ditempel (sebagian) dan terbaca di perangkat gudang
- [ ] Pelatihan user selesai (daftar hadir)

---

# 12. LAMPIRAN

## 12.1 Rumus Resmi (Kontrak)

```
# Satuan: rupiah integer; qty dalam milli-unit (qty × 1000); persen dalam basis points (1 % = 100 bp)

line_gross      = round_half_up(qty_milli × unit_price / 1000)
line_discount   = percent ? round_half_up(line_gross × bp / 10000) : min(amount, line_gross)
line_total      = line_gross − line_discount
subtotal        = Σ line_total
header_discount = percent ? round_half_up(subtotal × bp / 10000) : min(amount, subtotal)
tax_base (DPP)  = subtotal − header_discount                 # ongkir TIDAK termasuk
tax_amount      = taxable ? round_half_up(tax_base × tax_bp / 10000) : 0
grand_total     = tax_base + tax_amount + shipping_fee
balance_due     = grand_total − Σ payment_valid − Σ credit_note_posted

# HPP (moving average)
new_avg_cost    = round_half_up((old_qty × old_avg + in_qty × in_cost) / (old_qty + in_qty))   ; old_qty ≤ 0 → in_cost
cogs_line       = round_half_up(qty × unit_cost_snapshot)
gross_profit    = tax_base − Σ cogs_line  (netto retur)

# Komisi
base            = tax_base − DPP_retur
flat            = round_half_up(base × rate_bp / 10000)
tiered (marginal) = Σ_tier round_half_up(portion_in_tier × tier_rate_bp / 10000)
payment_basis   = non-final: round_half_up(commission × payment / grand_total) ; final: commission − Σ accrued_before
reversal        = −round_half_up(commission_asal × (dpp_retur / dpp_asal))
achievement_pct = target > 0 ? omzet_bersih / target × 100 : null

# Keuangan
net_sales       = penjualan − retur − diskon
gross_profit    = net_sales − HPP
net_profit      = gross_profit − Σ beban
ar_age_days     = max(0, as_of − due_date)   → bucket 0–30 | 31–60 | 61–90 | >90 ; as_of ≤ due_date → "belum jatuh tempo"
```

## 12.2 Kasus Uji Wajib

### A. InvoiceCalculator (angka sudah dihitung untuk verifikasi)

| # | Skenario | Input ringkas | Hasil yang diharapkan |
|---|---|---|---|
| C1 | Tanpa diskon, tanpa PPN | 2 × 50.000 | subtotal 100.000 · grand 100.000 |
| C2 | PPN 11 % | DPP 100.000 | PPN 11.000 · grand 111.000 |
| C3 | Diskon baris 10 % | 3 × 100.000 | line 270.000 |
| C4 | Diskon header Rp 5.000 | subtotal 520.000 | DPP 515.000 |
| C5 | Kombinasi lengkap | item1: 3×100.000 disk 10 % ; item2: 1×250.000 ; header disk 5.000 ; PPN 11 % ; ongkir 20.000 | subtotal **520.000** · DPP **515.000** · PPN **56.650** · grand **591.650** |
| C6 | Pembulatan half-up PPN | DPP 12.345 | 11 % = 1.357,95 → **1.358** |
| C7 | Pembulatan tepat .5 | DPP 50 · 1 % = 0,5 | **1** (half-up) |
| C8 | Qty desimal | 1,5 × 33.333 | **50.000** (49.999,5 → 50.000) |
| C9 | Diskon nominal > harga | harga 10.000, diskon 15.000 | diskon dibatasi 10.000, total 0 |
| C10 | Diskon header 100 % | subtotal 100.000 | DPP 0 · PPN 0 · grand = ongkir |
| C11 | Nilai besar | 10.000 × 999.999.999 | tidak overflow (int64) |
| C12 | `taxable=false` | DPP 100.000 | PPN 0 |
| C13 | Invoice kosong | tanpa item | semua 0 (atau validasi gagal di FormRequest) |

### B. Komisi

| # | Skenario | Hasil |
|---|---|---|
| K1 | Flat 2 % atas DPP 515.000 | 10.300 |
| K2 | DPP bukan grand total | komisi dari 515.000, bukan 591.650 |
| K3 | Basis payment — bayar 40 % lalu 60 % | komisi 40 % + sisa persis 60 % (total = penuh) |
| K4 | Tiered: 0–50 jt 1 %, 50–100 jt 2 %, >100 jt 3 %; DPP 120 jt | **2.100.000** |
| K5 | Tiered monthly_total: akumulasi sebelumnya 45 jt, DPP 10 jt | 5 jt@1 % + 5 jt@2 % = **150.000** |
| K6 | Per produk menimpa kategori | rate produk dipakai |
| K7 | Aturan berubah setelah invoice terbit | komisi lama tidak berubah (`rule_snapshot`) |
| K8 | Retur 20 % DPP | reversal = −20 % komisi |
| K9 | Void invoice setelah komisi `paid` | potongan di payout berikutnya |
| K10 | Target 100 % tercapai bonus 0,5 % | adjustment = 0,5 % × DPP bersih bulan itu |
| K11 | Target 0/kosong | pencapaian tampil "—", tidak error |
| K12 | Approve komisi sendiri | ditolak |

### C. Stok, Jurnal, Lainnya

| # | Skenario | Hasil |
|---|---|---|
| S1 | Masuk 10@100 + 10@200 | avg 150 |
| S2 | Keluar 5 setelah S1 | HPP 750, sisa 15 |
| S3 | Dua keluar bersamaan, stok 1 | satu sukses, satu `InsufficientStock` |
| S4 | Void GR yang stoknya sudah terpakai | ditolak |
| S5 | `stock:rebuild` setelah saldo dirusak | pulih |
| J1 | Jurnal tidak seimbang | `UnbalancedJournalException` |
| J2 | Posting di periode closed | `PeriodClosedException` |
| J3 | Σ persediaan GL == stock_balances | selisih 0 |
| T1 | Transaksi 23:59 WIB | masuk hari WIB yang benar |
| T2 | Transaksi 00:30 WIB | tidak masuk hari sebelumnya |
| Q1 | QR signature diubah 1 karakter | ditolak, tercatat `invalid` |
| Q2 | Scan sama dalam 1 dtk | tidak menambah qty |
| Q3 | `commit` dua kali dengan Idempotency-Key sama | 1 dokumen |
| **E2E-1** | Siklus lengkap (T-7.1.2) | semua akun & dashboard cocok tiap langkah |

## 12.3 Daftar Route (ringkas)

| Method | URI | Nama | Permission |
|---|---|---|---|
| GET | `/dashboard/{warehouse\|sales\|finance}` | `dashboard.*` | `dashboard.*` |
| RESOURCE | `/master/products` `customers` `suppliers` `warehouses` `locations` `categories` `units` | `master.*` | `*.manage` |
| RESOURCE | `/inventory/receipts` | `receipts.*` | `stock.in.*` |
| POST | `/inventory/receipts/{id}/post` · `/void` | `receipts.post/void` | `stock.in.post` |
| RESOURCE | `/inventory/issues` (+ `post`, `void`, `status`) | `issues.*` | `stock.out.*` |
| RESOURCE | `/inventory/transfers` (+ `send`, `receive`) | `transfers.*` | `stock.transfer` |
| RESOURCE | `/inventory/opnames` (+ `submit`, `approve`, `reject`) | `opnames.*` | `stock.opname.*` |
| GET | `/inventory/stock` · `/inventory/stock-card` | `stock.*` | `stock.view` |
| GET | `/qr/scan` · `/qr/labels` · `/qr/log` | `qr.*` | `qr.*` |
| POST | `/api/qr/resolve` · `/api/qr/commit` | `api.qr.*` | `qr.scan` |
| GET | `/api/stock/available` | `api.stock.available` | `stock.view` |
| GET | `/verify/{type}/{number}/{sig}` | `verify` | publik (throttle) |
| RESOURCE | `/sales/invoices` (+ `post`, `void`, `pdf`, `preview`) | `invoices.*` | `invoice.*` |
| POST | `/sales/invoices/{id}/payments` · `DELETE …/payments/{pid}` | `payments.*` | `payment.record` |
| RESOURCE | `/sales/credit-notes` (+ `post`) | `creditnotes.*` | `creditnote.create` |
| RESOURCE | `/commission/rules` | `commission.rules.*` | `commission.rule.manage` |
| GET | `/commission` · `/commission/me` · `/commission/{id}/explain` | `commission.*` | `commission.view` |
| POST | `/commission/approve` · `/commission/payouts` | `commission.approve/payout` | `commission.approve/pay` |
| RESOURCE | `/finance/journals` · `expenses` · `bills` · `cash` | `finance.*` | `finance.*` |
| GET | `/finance/reports/{recap\|pnl\|cashflow\|balance-sheet\|ledger\|trial-balance\|ar-aging\|ap-aging\|sales\|tax\|commission}` | `reports.*` | `report.*` |
| POST | `/finance/periods/{y}/{m}/close` · `/reopen` | `periods.*` | `finance.period.close` |
| RESOURCE | `/settings` · `/users` · `/roles` | `settings.*` … | `settings.manage` … |

## 12.4 Isi `config/erp.php` (contoh)

```php
return [
    'doc_prefix' => [
        'goods_receipt' => 'GR', 'goods_issue' => 'GI', 'transfer' => 'TR', 'opname' => 'SO',
        'invoice' => 'INV', 'payment' => 'PAY', 'credit_note' => 'CN',
        'journal' => 'JV', 'payout' => 'KOM', 'expense' => 'EXP', 'bill' => 'BILL',
    ],
    'doc_number_format' => '{PREFIX}/{YYYY}/{MM}/{SEQ:4}',
    'sequence_reset'    => 'monthly',                // monthly|yearly
    'qr' => [
        'version' => 'ERP1', 'error_correction' => 'M', 'debounce_ms' => 1500,
        'labels' => [
            'a4_3x8'      => ['page'=>'A4','cols'=>3,'rows'=>8,'w_mm'=>63.5,'h_mm'=>33.9,'qr_mm'=>22],
            'a4_4x10'     => ['page'=>'A4','cols'=>4,'rows'=>10,'w_mm'=>48.5,'h_mm'=>25.4,'qr_mm'=>18],
            'thermal_38x25' => ['page'=>[38,25],'cols'=>1,'rows'=>1,'qr_mm'=>20],
        ],
    ],
    'defaults' => [
        'tax_rate_bp' => 1100, 'commission_basis' => 'payment_received',
        'valuation_method' => 'moving_average', 'allow_negative_stock' => false,
        'aging_buckets' => [30, 60, 90], 'dead_stock_days' => 90, 'expiry_warning_days' => [30, 60, 90],
    ],
    'cache' => ['dashboard_ttl' => 300],
];
```

## 12.5 Enum Utama

| Enum | Nilai |
|---|---|
| `InvoiceStatus` | draft, posted, partial, paid, void |
| `DocumentStatus` | draft, posted, void |
| `MovementType` | IN_PURCHASE, IN_RETURN_SALE, IN_TRANSFER, IN_ADJUSTMENT, IN_OPENING, OUT_SALE, OUT_TRANSFER, OUT_ADJUSTMENT, OUT_DAMAGED, OUT_RETURN_SUPPLIER |
| `CommissionBasis` | invoice_posted, payment_received |
| `CommissionRuleType` | flat, tiered, per_product, per_category |
| `CommissionStatus` | pending, approved, paid, cancelled |
| `CommissionEntryType` | accrual, reversal, adjustment |
| `PaymentMethod` | cash, transfer, qris, card, other |
| `AccountType` | asset, liability, equity, revenue, cogs, expense |
| `QrMode` | in, out, opname, info |
| `QrResult` | ok, invalid, duplicate, not_found, forbidden |

## 12.6 Seeder & Akun Demo

| Email | Role | Password demo |
|---|---|---|
| owner@erp.test | owner | `password` (ganti di staging/prod) |
| admin@erp.test | admin | |
| gudang@erp.test | gudang | |
| sales1@erp.test … sales4@erp.test | sales | |
| finance@erp.test | finance | |

> Akun demo **hanya** dibuat bila `APP_ENV` ∈ {local, staging}.

## 12.7 Glosarium

| Istilah | Arti |
|---|---|
| DPP | Dasar Pengenaan Pajak — nilai setelah diskon, sebelum PPN |
| HPP / COGS | Harga Pokok Penjualan |
| Moving Average | HPP rata-rata bergerak, dihitung ulang tiap barang masuk |
| FEFO | First-Expired-First-Out |
| GR / GI | Goods Receipt (barang masuk) / Goods Issue (barang keluar) |
| Ledger | Catatan berurutan yang tidak diubah (hanya ditambah) |
| Reversal | Entri kebalikan untuk membatalkan entri sebelumnya |
| Cash basis | Pengakuan saat uang diterima |
| Marginal tier | Tiap rupiah dikenai rate tier tempatnya berada |
| Idempotent | Diulang berkali-kali menghasilkan efek yang sama dengan sekali |
| Basis points (bp) | 1/100 persen (11 % = 1100 bp) |
| AOV | Average Order Value — rata-rata nilai invoice |
| UAT | User Acceptance Testing |

## 12.8 Ringkasan Estimasi

| Fase | Jam (±) | Catatan |
|---|---:|---|
| 1 Fondasi | 120 | Komponen UI & master data |
| 2 Gudang + QR | 290 | Terbesar; QR scan & StockService |
| 3 Sales & Invoice | 150 | |
| 4 Rumus + Komisi | 190 | Risiko tertinggi; banyak test |
| 5 Keuangan | 190 | Jurnal otomatis & rekonsiliasi |
| 6 Dashboard | 130 | + ringkasan harian |
| 7 QA & Deploy | 150 | Termasuk UAT & migrasi data |
| **Total** | **≈ 1.220 jam** | ≈ 30 minggu-orang → 16 minggu (1 dev) / 9–10 minggu (2 dev) + buffer 15 % |

*Estimasi kasar untuk perencanaan; revisi setelah Keputusan Terbuka (§0.3) dijawab dan setelah Sprint 1 (kecepatan tim terukur).*

---
*Versi dokumen: 2.0 (detail) · Diperbarui: 26 Oktober 2026*
