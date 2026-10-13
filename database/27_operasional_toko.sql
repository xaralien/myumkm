-- =============================================================================
--  27_operasional_toko.sql
--
--  1. Mode libur toko (mulai, sampai, pesan untuk pembeli).
--  2. Template balasan chat penjual.
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _kolom27;
DELIMITER $$
CREATE PROCEDURE _kolom27(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;

/* Libur: produk TETAP tampil (pembeli bisa menyimpan ke Favorit atau
   pre-order), tapi pesanan biasa ditolak di checkout selama rentang ini.
   Toko kembali buka sendiri setelah libur_sampai - penjual tidak perlu
   ingat mematikannya. */
CALL _kolom27('stores', 'libur_mulai',  'DATE NULL');
CALL _kolom27('stores', 'libur_sampai', 'DATE NULL');
CALL _kolom27('stores', 'libur_pesan',  'VARCHAR(160) NULL');

CREATE TABLE IF NOT EXISTS chat_templates (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id   INT UNSIGNED NOT NULL,
  judul      VARCHAR(40)  NOT NULL,
  isi        VARCHAR(1000) NOT NULL,
  urutan     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_tpl_toko (store_id, urutan),
  CONSTRAINT fk_tpl_toko FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP PROCEDURE IF EXISTS _kolom27;
