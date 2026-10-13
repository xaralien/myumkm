-- =============================================================================
--  24_pengaturan_notifikasi.sql
--
--  1. Pengaturan sistem yang bisa diubah admin dari panel (komisi, kontak,
--     gerbang WhatsApp, email) - sebelumnya hanya lewat berkas config.
--  2. Catatan notifikasi keluar (WhatsApp/email): terkirim, gagal, dilewati.
--  3. Log audit aktivitas admin.
--  4. Token sekali pakai: reset kata sandi & kode OTP WhatsApp.
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

/* Kunci-nilai sederhana. Nilai yang TIDAK ada di tabel ini jatuh ke nilai
   bawaan di config/marketplace.php - jadi tabel kosong pun aman. */
CREATE TABLE IF NOT EXISTS settings (
  kunci      VARCHAR(60)  NOT NULL,
  nilai      TEXT         NULL,
  updated_at DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (kunci)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Setiap notifikasi dicatat, termasuk yang DILEWATI karena gerbangnya belum
   diisi. Tanpa catatan ini, "kenapa pembeli tidak dapat WA?" tidak bisa
   dijawab. */
CREATE TABLE IF NOT EXISTS notifikasi_keluar (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kanal      ENUM('wa','email') NOT NULL,
  tujuan     VARCHAR(150) NOT NULL,
  judul      VARCHAR(150) NULL,
  isi        TEXT NOT NULL,
  status     ENUM('terkirim','gagal','dilewati') NOT NULL,
  galat      VARCHAR(255) NULL,
  acara      VARCHAR(40)  NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_notif_waktu (created_at),
  KEY ix_notif_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NULL,
  nama       VARCHAR(100) NULL,
  aksi       VARCHAR(60)  NOT NULL,
  objek      VARCHAR(40)  NULL,
  objek_id   BIGINT UNSIGNED NULL,
  detail     VARCHAR(500) NULL,
  ip         VARCHAR(45)  NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_audit_waktu (created_at),
  KEY ix_audit_objek (objek, objek_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Yang disimpan HASH-nya, bukan token aslinya: kalau tabel ini bocor,
   tautan reset dan kode OTP yang masih berlaku tidak bisa dipakai. */
CREATE TABLE IF NOT EXISTS auth_tokens (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  jenis       ENUM('reset','otp') NOT NULL,
  token_hash  CHAR(64) NOT NULL,
  percobaan   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  kedaluwarsa DATETIME NOT NULL,
  dipakai_at  DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_token_user (user_id, jenis, created_at),
  CONSTRAINT fk_token_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
