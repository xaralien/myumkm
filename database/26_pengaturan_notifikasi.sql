-- =============================================================================
--  26_pengaturan_notifikasi.sql
--
--  1. Pengaturan sistem yang bisa diubah admin dari panel (komisi, kontak,
--     gerbang WhatsApp, email) - sebelumnya hanya lewat berkas config.
--  2. Catatan notifikasi keluar (WhatsApp/email): terkirim, gagal, dilewati.
--  3. Log audit aktivitas admin.
--  4. Penghitung salah tebak OTP WhatsApp di tabel auth_tokens (migrasi 25).
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

/* Kode OTP WhatsApp memakai tabel auth_tokens dari migrasi 25
   (25_reset_sandi_verifikasi.sql - JALANKAN ITU DULU) dengan jenis 'otp'.
   Yang ditambahkan di sini hanya penghitung salah tebak: OTP 6 angka
   dikunci setelah 5 kali salah. */
DROP PROCEDURE IF EXISTS _kolom26;
DELIMITER $$
CREATE PROCEDURE _kolom26(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;

CALL _kolom26('auth_tokens', 'percobaan', 'TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER token_hash');

DROP PROCEDURE IF EXISTS _kolom26;
