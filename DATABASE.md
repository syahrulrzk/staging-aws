# 📊 Database Documentation — BPR Sync

> Struktur database, field mapping, dan relasi antar table.

---

## 📁 Database: `db_staging_aws`

### Table: `master_pks`

Table utama yang menyimpan data Master PKS dari BPR.

| # | Field | Type | Null | Default | Description |
|---|-------|------|------|---------|-------------|
| 1 | `id` | bigint unsigned | NO | auto_increment | Primary key staging |
| 2 | `bpr_id` | bigint unsigned | YES | NULL | ID dari BPR (unix timestamp) |
| 3 | `no_pks` | varchar(50) | NO | — | Nomor PKS |
| 3 | `nama_client` | varchar(255) | YES | NULL | Nama client berdasarkan PKS |
| 4 | `group_bisnis` | varchar(255) | YES | NULL | Group bisnis client |
| 5 | `start_date_pks` | date | YES | NULL | Tanggal mulai PKS |
| 6 | `end_date_pks` | date | YES | NULL | Tanggal berakhir PKS |
| 7 | `nama_lob` | varchar(100) | YES | NULL | Nama Line of Business |
| 8 | `kode_lob` | varchar(20) | YES | NULL | Kode Line of Business |
| 9 | `jenis_kontrak` | varchar(50) | YES | NULL | Tipe PKS: New / Addendum / Perpanjangan |
| 10 | `create_at` | timestamp | YES | NULL | Waktu data dibuat di BPR |
| 11 | `update_at_bpr` | timestamp | YES | NULL | Update terakhir dari BPR |
| 12 | `retry_at` | timestamp | YES | NULL | Waktu data disinkronisasi (retry) |
| 13 | `sync_at` | timestamp | YES | NULL | Waktu terakhir data diperbarui dari BPR |
| 14 | `sync_status` | varchar(20) | NO | `pending` | Status: `pending` / `success` / `failed` |
| 15 | `is_fetched` | tinyint(1) | NO | 0 | Apakah data sudah pernah diambil dari BPR |
| 16 | `fetched_at` | timestamp | YES | NULL | Waktu pertama kali data diambil |
| 17 | `is_synced` | tinyint(1) | NO | 0 | Apakah data sudah pernah di-sync ke AWS |
| 18 | `synced_to_aws_at` | timestamp | YES | NULL | Waktu terakhir data di-sync ke AWS |
| 19 | `fetch_count` | int | NO | 0 | Jumlah kali data diambil |
| 20 | `created_at` | timestamp | YES | NULL | Laravel timestamp |
| 21 | `updated_at` | timestamp | YES | NULL | Laravel timestamp |

**Indexes:**

| Index Name | Column(s) | Type |
|------------|-----------|------|
| PRIMARY | `id` | Primary Key |
| `idx_no_pks` | `no_pks` | INDEX |
| `idx_sync_status` | `sync_status` | INDEX |
| `idx_pks_lob` | `no_pks`, `kode_lob` | COMPOSITE INDEX |

---

### Table: `sync_logs`

Menyimpan history perubahan data setiap kali sync berjalan.

| # | Field | Type | Null | Default | Description |
|---|-------|------|------|---------|-------------|
| 1 | `id` | bigint unsigned | NO | auto_increment | Primary key |
| 2 | `master_pks_id` | bigint unsigned | NO | — | FK → `master_pks.id` |
| 3 | `action` | enum | NO | — | `create` / `update` / `delete` / `sync` |
| 4 | `status` | enum | NO | — | `success` / `failed` |
| 5 | `old_data` | json | YES | NULL | Data sebelum perubahan |
| 6 | `new_data` | json | YES | NULL | Data sesudah perubahan |
| 7 | `error_message` | text | YES | NULL | Pesan error jika gagal |
| 8 | `synced_at` | timestamp | NO | — | Waktu sync dilakukan |
| 9 | `created_at` | timestamp | YES | NULL | Laravel timestamp |
| 10 | `updated_at` | timestamp | YES | NULL | Laravel timestamp |

**Foreign Keys:**

| Constraint | Column | References |
|------------|--------|------------|
| `fk_sync_logs_master_pks` | `master_pks_id` | `master_pks(id)` ON DELETE CASCADE |

**Indexes:**

| Index Name | Column(s) | Type |
|------------|-----------|------|
| PRIMARY | `id` | Primary Key |
| `idx_master_pks_id` | `master_pks_id` | INDEX |
| `idx_action` | `action` | INDEX |
| `idx_status` | `status` | INDEX |
| `idx_synced_at` | `synced_at` | INDEX |

---

### Table: `sessions`

Laravel session storage (database driver).

| # | Field | Type | Null | Description |
|---|-------|------|------|-------------|
| 1 | `id` | varchar(128) | NO | Session ID (PK) |
| 2 | `user_id` | bigint unsigned | YES | User ID |
| 3 | `ip_address` | varchar(45) | YES | IP address |
| 4 | `user_agent` | text | YES | Browser user agent |
| 5 | `payload` | longtext | NO | Session data |
| 6 | `last_activity` | int | NO | Last activity timestamp |

---

### Table: `cache`

Laravel cache storage (database driver).

| # | Field | Type | Null | Description |
|---|-------|------|------|-------------|
| 1 | `key` | varchar(255) | NO | Cache key (PK) |
| 2 | `value` | mediumtext | NO | Cache value |
| 3 | `expiration` | int | NO | Expiration timestamp |

---

### Table: `cache_locks`

Laravel cache lock storage.

| # | Field | Type | Null | Description |
|---|-------|------|------|-------------|
| 1 | `key` | varchar(255) | NO | Lock key (PK) |
| 2 | `owner` | varchar(255) | NO | Lock owner |
| 3 | `expiration` | int | NO | Expiration timestamp |

---

### Table: `jobs`

Laravel queue jobs.

| # | Field | Type | Null | Description |
|---|-------|------|------|-------------|
| 1 | `id` | bigint unsigned | NO | Job ID (PK) |
| 2 | `queue` | varchar(255) | NO | Queue name |
| 3 | `payload` | longtext | NO | Job payload |
| 4 | `attempts` | tinyint unsigned | NO | Attempt count |
| 5 | `reserved_at` | timestamp | YES | Reserved timestamp |
| 6 | `available_at` | timestamp | YES | Available timestamp |
| 7 | `created_at` | timestamp | YES | Created timestamp |

---

### Table: `job_batches`

Laravel job batches.

| # | Field | Type | Null | Description |
|---|-------|------|------|-------------|
| 1 | `id` | varchar(255) | NO | Batch ID (PK) |
| 2 | `name` | varchar(255) | NO | Batch name |
| 3 | `total_jobs` | int | NO | Total jobs |
| 4 | `pending_jobs` | int | NO | Pending jobs |
| 5 | `failed_jobs` | int | NO | Failed jobs |
| 6 | `failed_job_ids` | longtext | NO | Failed job IDs |
| 7 | `options` | mediumtext | YES | Options |
| 8 | `cancelled_at` | timestamp | YES | Cancelled timestamp |
| 9 | `created_at` | timestamp | YES | Created timestamp |
| 10 | `finished_at` | timestamp | YES | Finished timestamp |

---

### Table: `failed_jobs`

Laravel failed jobs.

| # | Field | Type | Null | Description |
|---|-------|------|------|-------------|
| 1 | `id` | bigint unsigned | NO | Failed job ID (PK) |
| 2 | `uuid` | varchar(255) | NO | UUID (UNIQUE) |
| 3 | `connection` | text | NO | Connection name |
| 4 | `queue` | text | NO | Queue name |
| 5 | `payload` | longtext | NO | Job payload |
| 6 | `exception` | longtext | NO | Exception message |
| 7 | `failed_at` | timestamp | NO | Failed timestamp |

---

### Table: `migrations`

Laravel migration tracking.

| # | Field | Type | Null | Description |
|---|-------|------|------|-------------|
| 1 | `id` | int unsigned | NO | Migration ID (PK) |
| 2 | `migration` | varchar(255) | NO | Migration filename |
| 3 | `batch` | int | NO | Batch number |

---

## 📁 Database: `core` (BPR — Read Only)

### View: `view_pks_aws`

View di database BPR sebagai sumber data Master PKS.

| # | Field (DB) | Field (Alias) | Type | Description |
|---|------------|---------------|------|-------------|
| 0 | `id` | `id` | bigint | ID dari BPR (unix timestamp) |
| 1 | `No pks` | `no_pks` | varchar(100) | Nomor PKS |
| 2 | `Nama client` | `nama_client` | varchar(150) | Nama client |
| 3 | `Group bisnis` | `group_bisnis` | varchar(125) | Group bisnis |
| 4 | `start date PKS` | `start_date_pks` | date | Tanggal mulai PKS |
| 5 | `end date PKS` | `end_date_pks` | date | Tanggal berakhir PKS |
| 6 | `Nama LOB` | `nama_lob` | varchar(125) | Nama Line of Business |
| 7 | `Kode LOB` | `kode_lob` | varchar(25) | Kode Line of Business |
| 8 | `Jenis Kontrak` | `jenis_kontrak` | varchar(12) | Tipe PKS |
| 9 | `Create at` | `create_at` | datetime | Waktu dibuat |
| 10 | `Update at` | `update_at` | datetime | Waktu update |

> ⚠️ **Catatan:** View BPR menggunakan **spasi di nama kolom** (e.g., `No pks`). Sudah di-handle di model `BprPksView` dengan alias `DB::raw()`.

> ⚠️ **Catatan:** Beberapa record memiliki `Update at = '0000-00-00 00:00:00'` yang dihandle sebagai `NULL`.

---

## 🗺️ Field Mapping: BPR → Staging

| BPR View Field | Staging Field | Transform |
|----------------|---------------|-----------|
| `id` | `bpr_id` | — |
| `No pks` | `no_pks` | — |
| `Nama client` | `nama_client` | — |
| `Group bisnis` | `group_bisnis` | — |
| `start date PKS` | `start_date_pks` | — |
| `end date PKS` | `end_date_pks` | — |
| `Nama LOB` | `nama_lob` | — |
| `Kode LOB` | `kode_lob` | — |
| `Jenis Kontrak` | `jenis_kontrak` | — |
| `Create at` | `create_at` | `0000-00-00 00:00:00` → `NULL` |
| `Update at` | `update_at_bpr` | `0000-00-00 00:00:00` → `NULL` |
| — | `sync_status` | Default: `pending` → `success` |
| — | `is_fetched` | Default: `0` |
| — | `is_synced` | Default: `0` |
| — | `fetch_count` | Default: `0` |
| — | `sync_at` | Auto: `now()` |

---

## 📊 Data Statistics (Sample)

### Records per LOB

| Kode LOB | Nama LOB | Count |
|----------|----------|-------|
| FIF | SOB FIF | 63,663 |
| MSM1 | LOB MSM1 | 26,227 |
| MSM2 | LOB MSM2 | 14,905 |
| CCCM | LOB CCCM | 6,902 |
| SM | LOB SM | 3,454 |
| SL | LOB-SL | 1,227 |
| NULL | NULL | 1,220 |
| LO | LOB-LO5 | 402 |
| **Total** | | **136,631** |

---

## 🔧 SQL Setup Commands

### Create All Tables

```sql
-- Lihat file database/setup.sql untuk full SQL
mysql -h 192.168.0.7 -u bpr_sync -p db_staging_aws < database/setup.sql
```

### Quick Reference: ALTER TABLE (Nullable)

```sql
ALTER TABLE master_pks 
  MODIFY nama_client varchar(255) DEFAULT NULL,
  MODIFY group_bisnis varchar(255) DEFAULT NULL,
  MODIFY nama_lob varchar(100) DEFAULT NULL,
  MODIFY kode_lob varchar(20) DEFAULT NULL,
  MODIFY jenis_kontrak varchar(50) DEFAULT NULL;
```

### Reset Data

```sql
SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE sync_logs;
TRUNCATE TABLE master_pks;
SET FOREIGN_KEY_CHECKS=1;
```

---

*BPR Sync Database Documentation — August 2026*
