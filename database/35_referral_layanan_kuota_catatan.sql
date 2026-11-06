-- =============================================================================
--  35_referral_layanan_kuota_catatan.sql
--
--  1. Kode referral pembeli: setiap akun punya kode; teman yang mendaftar
--     dengan kode itu mendapat voucher, dan pengajaknya mendapat voucher
--     setelah pesanan pertama teman itu lunas. Voucher bisa dikhususkan
--     untuk satu akun (vouchers.user_id).
--  2. Jangkauan layanan toko: saklar melayani luar kecamatan (layani_kota).
--     Luar kota & luar provinsi tetap memakai aturan lama: tarif NULL =
--     tidak melayani.
--  3. Batas pesanan per hari per toko (maks_pesanan_harian, NULL = tanpa batas).
--  4. Catatan & gambar dari pembeli saat memesan (per pesanan/toko).
--
--  Untuk MySQL 8 / MariaDB. Aman dijalankan berulang. CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _kolom35;
DELIMITER $$
CREATE PROCEDURE _kolom35(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS _indeks35;
DELIMITER $$
CREATE PROCEDURE _indeks35(IN t VARCHAR(64), IN i VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND INDEX_NAME = i) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;

/* ---------------------------------------------------------------------------
   1. REFERRAL
   ------------------------------------------------------------------------- */
CALL _kolom35('users', 'kode_referral', 'VARCHAR(12) NULL');
CALL _kolom35('users', 'diajak_oleh',   'INT UNSIGNED NULL');
CALL _indeks35('users', 'uq_users_referral', 'UNIQUE KEY uq_users_referral (kode_referral)');

-- Voucher khusus satu akun (hadiah referral). NULL = untuk siapa saja.
CALL _kolom35('vouchers', 'user_id', 'INT UNSIGNED NULL AFTER store_id');
CALL _indeks35('vouchers', 'ix_voucher_user', 'KEY ix_voucher_user (user_id)');

CREATE TABLE IF NOT EXISTS referral (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  pengajak_id         INT UNSIGNED NOT NULL,
  teman_id            INT UNSIGNED NOT NULL,
  status              ENUM('daftar','selesai') NOT NULL DEFAULT 'daftar',
  voucher_teman_id    INT UNSIGNED NULL,
  voucher_pengajak_id INT UNSIGNED NULL,
  order_id            BIGINT UNSIGNED NULL,      -- pesanan pertama teman yang lunas
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  selesai_at          DATETIME NULL,
  PRIMARY KEY (id),
  -- Satu akun hanya bisa diajak satu kali.
  UNIQUE KEY uq_referral_teman (teman_id),
  KEY ix_referral_pengajak (pengajak_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------------------------------------------------------------------------
   2. JANGKAUAN LAYANAN TOKO
   0 = hanya melayani kecamatan toko sendiri.
   ------------------------------------------------------------------------- */
CALL _kolom35('stores', 'layani_kota', 'TINYINT(1) NOT NULL DEFAULT 1');

/* ---------------------------------------------------------------------------
   3. BATAS PESANAN PER HARI
   ------------------------------------------------------------------------- */
CALL _kolom35('stores', 'maks_pesanan_harian', 'SMALLINT UNSIGNED NULL');

/* ---------------------------------------------------------------------------
   4. CATATAN & GAMBAR DARI PEMBELI
   recipient_notes (255 huruf) tetap ada untuk patokan alamat; catatan_pembeli
   untuk keterangan pesanan yang lebih panjang (tulisan kartu ucapan, model
   yang diinginkan, dsb.) beserta satu gambar contoh.
   ------------------------------------------------------------------------- */
CALL _kolom35('orders', 'catatan_pembeli', 'TEXT NULL');
CALL _kolom35('orders', 'catatan_gambar',  'VARCHAR(80) NULL');

DROP PROCEDURE IF EXISTS _kolom35;
DROP PROCEDURE IF EXISTS _indeks35;
