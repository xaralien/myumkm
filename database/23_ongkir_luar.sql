-- =============================================================================
--  23_ongkir_luar.sql
--
--  Tarif ongkir ke luar provinsi.
--
--  Sebelumnya tarif hanya punya tiga tingkat (kecamatan, kota, provinsi),
--  jadi checkout MENOLAK alamat di luar provinsi toko - bukan karena
--  kebijakan, tapi karena sistem tidak tahu harus menagih berapa.
--
--  NULL = toko tidak melayani luar provinsi, dan itu tetap pilihan yang sah:
--  bunga segar dan kue basah memang rusak kalau berhari-hari di jalan.
--  Penjual yang memutuskan, bukan aturan global.
--
--  Aman dijalankan berulang. CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _kolom23;
DELIMITER $$
CREATE PROCEDURE _kolom23(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;

CALL _kolom23('stores', 'ongkir_luar', 'INT UNSIGNED NULL AFTER ongkir_provinsi');

DROP PROCEDURE IF EXISTS _kolom23;
