-- ============================================
-- BPR Sync - Database & Table Setup
-- Run di MySQL staging server (192.168.0.7)
-- ============================================

-- 1. Buat database
CREATE DATABASE IF NOT EXISTS db_staging_aws
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- 2. Grant permission ke user bpr_sync (kalau belum)
GRANT ALL PRIVILEGES ON db_staging_aws.* TO 'bpr_sync'@'%';
FLUSH PRIVILEGES;

-- 3. Pindah ke database
USE db_staging_aws;

-- 4. Buat tabel migrations (Laravel)
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `migration` varchar(255) NOT NULL,
    `batch` int NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Buat tabel master_pks (Utama)
CREATE TABLE IF NOT EXISTS `master_pks` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT 'Primary key staging',
    `bpr_id` bigint unsigned DEFAULT NULL COMMENT 'ID dari BPR (unix timestamp)',
    `no_pks` varchar(50) NOT NULL COMMENT 'Nomor PKS',
    `nama_client` varchar(255) NOT NULL COMMENT 'Nama client berdasarkan PKS',
    `group_bisnis` varchar(255) NOT NULL COMMENT 'Group bisnis client',
    `start_date_pks` date NOT NULL COMMENT 'Tanggal mulai PKS',
    `end_date_pks` date NOT NULL COMMENT 'Tanggal berakhir PKS',
    `nama_lob` varchar(100) NOT NULL COMMENT 'Nama Line of Business',
    `kode_lob` varchar(20) NOT NULL COMMENT 'Kode Line of Business',
    `jenis_kontrak` varchar(50) NOT NULL COMMENT 'Tipe PKS: New / Addendum / Perpanjangan',
    `create_at` timestamp NULL DEFAULT NULL COMMENT 'Waktu data dibuat di BPR',
    `update_at_bpr` timestamp NULL DEFAULT NULL COMMENT 'Update terakhir dari BPR',
    `retry_at` timestamp NULL DEFAULT NULL COMMENT 'Waktu data disinkronisasi',
    `sync_at` timestamp NULL DEFAULT NULL COMMENT 'Waktu terakhir data diperbarui',
    `sync_status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'Status sinkronisasi: pending / success / failed',
    `is_fetched` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Apakah data sudah pernah diambil dari BPR',
    `fetched_at` timestamp NULL DEFAULT NULL COMMENT 'Waktu pertama kali data diambil',
    `is_synced` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Apakah data sudah pernah di-sync ke AWS',
    `synced_to_aws_at` timestamp NULL DEFAULT NULL COMMENT 'Waktu terakhir data di-sync ke AWS',
    `fetch_count` int NOT NULL DEFAULT 0 COMMENT 'Jumlah kali data diambil',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_no_pks` (`no_pks`),
    KEY `idx_sync_status` (`sync_status`),
    KEY `idx_pks_lob` (`no_pks`, `kode_lob`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Buat tabel sync_logs (Histori sync)
CREATE TABLE IF NOT EXISTS `sync_logs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `master_pks_id` bigint unsigned NOT NULL,
    `action` enum('create','update','delete','sync') NOT NULL COMMENT 'Jenis aksi sync',
    `status` enum('success','failed') NOT NULL COMMENT 'Status aksi',
    `old_data` json DEFAULT NULL COMMENT 'Data sebelum perubahan',
    `new_data` json DEFAULT NULL COMMENT 'Data sesudah perubahan',
    `error_message` text DEFAULT NULL COMMENT 'Pesan error jika gagal',
    `synced_at` timestamp NOT NULL COMMENT 'Waktu sync dilakukan',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_master_pks_id` (`master_pks_id`),
    KEY `idx_action` (`action`),
    KEY `idx_status` (`status`),
    KEY `idx_synced_at` (`synced_at`),
    CONSTRAINT `fk_sync_logs_master_pks` FOREIGN KEY (`master_pks_id`) REFERENCES `master_pks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
