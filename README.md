# 🏦 BPR Sync — Master PKS Integration

> Laravel 13 application untuk sinkronisasi data Master PKS dari database BPR ke staging server AWS.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Database](#database)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Documentation](#api-documentation)
- [Dashboard](#dashboard)
- [Scheduler](#scheduler)
- [Project Structure](#project-structure)
- [Troubleshooting](#troubleshooting)

---

## Overview

Sistem integrasi untuk mengambil data Master PKS (Perjanjian Kerja Sama) dari database BPR (Bank Perkreditan Rakyat) dan menyimpannya di staging server AWS. Data ini kemudian bisa diambil oleh aplikasi AWS melalui REST API.

### Key Features

- ✅ **Dual Database Connection** — Read-only ke BPR, Write ke staging
- ✅ **Flagging System** — Track data sudah di-fetch / belum, sudah di-sync / belum
- ✅ **Scheduler** — Auto-sync setiap 30 menit
- ✅ **REST API** — Endpoint untuk integrasi AWS
- ✅ **Dashboard** — Monitoring dengan charts & grafik
- ✅ **Sync Logs** — History perubahan data

---

## Architecture

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│    BPR DB    │ ──────▶ │  Staging DB  │ ──────▶ │   AWS App   │
│  (view_pks)  │  Sync   │  (master_pks)│  Fetch  │             │
│ 192.168.0.41 │  30m    │ 192.168.0.7  │         │             │
└─────────────┘         └──────────────┘         └─────────────┘
                               │                        │
                               │    POST /synced        │
                               ◀────────────────────────┘
```

### Flow

1. **Cron Job** jalan tiap 30 menit → ambil data dari `view_pks_aws` di BPR
2. Data disimpan di staging `master_pks` dengan flagging:
   - `is_fetched` — AWS udah ambil data
   - `is_synced` — AWS udah proses data
   - `sync_status` — `pending` / `success` / `failed`
3. AWS hit `GET /api/master-pks/fetch` → ambil data baru
4. AWS proses → hit `POST /api/master-pks/{id}/synced` → tandai selesai

---

## Tech Stack

| Component | Version |
|-----------|---------|
| PHP | 8.3.6 |
| Laravel | 13.29.0 |
| MySQL | 8.x |
| Chart.js | 4.4.0 |
| Tailwind CSS | CDN |

---

## Database

### BPR Database (Source — Read Only)

| Key | Value |
|-----|-------|
| Host | `192.168.0.41:3306` |
| Database | `core` |
| Username | `appslms` |
| Table | `view_pks_aws` |

### Staging Database (Target — Read/Write)

| Key | Value |
|-----|-------|
| Host | `192.168.0.7:3306` |
| Database | `db_staging_aws` |
| Username | `bpr_sync` |

---

## Installation

### 1. Clone Project

```bash
git clone <repo-url> ~/staging-aws/bpr-sync
cd ~/staging-aws/bpr-sync
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Create Database Tables

Login ke MySQL staging server, lalu jalankan SQL di `database/setup.sql`:

```bash
mysql -h 192.168.0.7 -u bpr_sync -p db_staging_aws < database/setup.sql
```

Atau manual via MySQL client:

```sql
-- Lihat database/setup.sql untuk full SQL
```

### 5. Run Initial Sync

```bash
php artisan master-pks:sync --first-time
```

### 6. Start Development Server

```bash
cd public
php -S 0.0.0.0:8000 router.php
```

---

## Configuration

### .env

```env
# Database Staging (Write)
DB_CONNECTION=mysql
DB_HOST=192.168.0.7
DB_PORT=3306
DB_DATABASE=db_staging_aws
DB_USERNAME=bpr_sync
DB_PASSWORD="PasswordYangKuat2026#"

# Database BPR (Read Only)
DB_SYSBPR_CONNECTION=mysql
DB_SYSBPR_HOST=192.168.0.41
DB_SYSBPR_PORT=3306
DB_SYSBPR_DATABASE=core
DB_SYSBPR_USERNAME=appslms
DB_SYSBPR_PASSWORD="PasswordYangKuat2026#"
```

---

## Usage

### Manual Sync

```bash
# First-time bulk sync (136k records)
php artisan master-pks:sync --first-time

# Incremental sync (with change detection)
php artisan master-pks:sync

# Retry failed records
php artisan master-pks:sync --retry
```

### Fast Sync (Direct PDO — Recommended)

```bash
php fast_sync.php
```

Script PHP native yang bypass Laravel, langsung PDO. Lebih cepat untuk bulk insert.

---

## API Documentation

Buka `/docs` di browser untuk API documentation lengkap.

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/master-pks/stats` | Statistik dashboard |
| `GET` | `/api/master-pks/fetch` | AWS ambil data (auto-flag fetched) |
| `POST` | `/api/master-pks/{id}/synced` | AWS mark sebagai synced |
| `POST` | `/api/master-pks/batch-synced` | AWS batch mark synced |
| `POST` | `/api/master-pks/sync` | Manual trigger sync |
| `GET` | `/api/master-pks` | List semua data (filterable) |
| `GET` | `/api/master-pks/{id}` | Detail + sync logs |

### Example: AWS Fetch Data

```bash
# Ambil data yang belum di-sync
curl -X GET http://localhost:8000/api/master-pks/fetch

# Response
{
  "success": true,
  "data": [...],
  "meta": { "total": 500, "fetched_at": "2026-08-29T12:00:00Z" }
}
```

### Example: AWS Mark Synced

```bash
# Tandai data sudah di-sync
curl -X POST http://localhost:8000/api/master-pks/1/synced

# Batch mark
curl -X POST http://localhost:8000/api/master-pks/batch-synced \
  -H "Content-Type: application/json" \
  -d '{"ids": [1, 2, 3, 4, 5]}'
```

---

## Dashboard

Buka `http://<server-ip>:8000/` di browser.

### Features

- 📊 **Donut Charts** — Sync Status, Fetched Status, Synced ke AWS
- 📈 **Bar Chart** — Jumlah data per LOB
- 🎯 **Stats Cards** — Total, Fetched, Belum Fetch, Synced AWS
- 🔍 **Search & Filter** — By No PKS, Client, LOB, Status
- 📄 **Pagination** — 20 records per page

---

## Scheduler

### Setup Cron

Tambahkan di crontab server untuk auto-sync setiap 1 jam:

```bash
0 * * * * cd /home/linux/staging-aws/bpr-sync && php artisan master-pks:sync >> /home/linux/staging-aws/bpr-sync/storage/logs/sync.log 2>&1
```

### Schedule

| Task | Frequency | Command |
|------|-----------|---------|
| Full sync | Every 1 hour | `php artisan master-pks:sync` |
| First-time bulk sync | Manual | `php artisan master-pks:sync --first-time` |
| Retry failed | Manual | `php artisan master-pks:sync --retry` |

### View Sync Logs

```bash
tail -f /home/linux/staging-aws/bpr-sync/storage/logs/sync.log
```

---

## Project Structure

```
bpr-sync/
├── app/
│   ├── Console/Commands/
│   │   └── SyncMasterPks.php        # Artisan command
│   ├── Http/Controllers/
│   │   ├── Api/MasterPksController.php  # REST API
│   │   └── DashboardController.php      # Dashboard
│   ├── Models/
│   │   ├── MasterPks.php             # Staging DB model
│   │   ├── BprPksView.php           # BPR view model (read-only)
│   │   └── SyncLog.php              # Sync history
│   └── Services/
│       └── SyncMasterPksService.php  # Sync logic
├── database/
│   ├── migrations/                   # Laravel migrations
│   └── setup.sql                     # Manual table setup
├── public/
│   ├── index.php
│   └── router.php                    # PHP built-in server router
├── resources/views/
│   ├── dashboard.blade.php           # Dashboard UI
│   └── api-docs.blade.php           # API documentation
├── routes/
│   ├── api.php                       # API routes
│   ├── web.php                       # Dashboard routes
│   └── console.php                   # Scheduler
├── fast_sync.php                     # Fast direct PDO sync
├── akses.md                          # DB access credentials
├── mom.md                            # Meeting notes
└── .env                              # Environment config
```

---

## Troubleshooting

### Server 500 Error

```bash
# Cek error log
tail -50 storage/logs/laravel.log

# Biasanya table belum ada (sessions, cache, etc)
# Buat manual via MySQL sesuai struktur Laravel
```

### Sync Gagal: Column cannot be null

```sql
-- Pastikan column nullable
ALTER TABLE master_pks
  MODIFY nama_client varchar(255) DEFAULT NULL,
  MODIFY group_bisnis varchar(255) DEFAULT NULL,
  MODIFY nama_lob varchar(100) DEFAULT NULL;
```

### Sync Gagal: Invalid datetime '0000-00-00 00:00:00'

BPR punya default datetime yang invalid. Sudah di-handle di `SyncMasterPksService::sanitizeDatetime()`.

### Port 8000/8181 Tidak Bisa Diakses

```bash
# Cek firewall
sudo ufw status
sudo ufw allow 8181/tcp

# Atau AWS Security Group — buka port di console
```

### BPR View Column Names (Spasi)

View BPR pakai spasi di nama kolom: `No pks`, `Nama client`, dll. Sudah di-handle di `BprPksView` model dengan `DB::raw()` select.

---

## Flagging System

| Field | Type | Description |
|-------|------|-------------|
| `is_fetched` | boolean | Data sudah pernah di-ambil dari BPR |
| `fetched_at` | datetime | Waktu pertama kali di-fetch |
| `fetch_count` | integer | Jumlah kali data di-fetch |
| `is_synced` | boolean | Data sudah di-sync ke AWS |
| `synced_to_aws_at` | datetime | Waktu terakhir sync ke AWS |
| `sync_status` | enum | `pending` / `success` / `failed` |
| `sync_at` | datetime | Waktu terakhir sync dari BPR |
| `retry_at` | datetime | Waktu terakhir retry |

---

## Notes

- **Laravel Version**: 13.29.0 (latest)
- **PHP Version**: 8.3.6
- **DB BPR**: Read-only, jangan tulis apapun
- **DB Staging**: User `bpr_sync` punya full access
- **First-time sync**: ~136,631 records dari BPR view
- **Auto-sync**: Setiap 30 menit via scheduler

---

*BPR Sync — AWS/PKP Integration | August 2026*
# staging-aws
