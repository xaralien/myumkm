-- =============================================================================
--  33_utility_wa.sql
--
--  Tabel `utility` yang dibaca library Api_Whatsapp (application/libraries/
--  Api_Whatsapp.php, tidak ikut di repo) untuk memilih server WhatsApp:
--    server_wa = 'fast_wa' | 'kirimi' | lainnya (= server bind)
--
--  Kalau tabelnya SUDAH ada (mis. dibagi dengan aplikasi lain), isinya tidak
--  diubah - hanya kolom server_wa yang ditambahkan bila belum ada, dan satu
--  baris bawaan dibuat bila tabelnya masih kosong.
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS utility (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  server_wa VARCHAR(20) NOT NULL DEFAULT 'bind',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP PROCEDURE IF EXISTS _kolom33;
DELIMITER $$
CREATE PROCEDURE _kolom33(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;

CALL _kolom33('utility', 'server_wa', 'VARCHAR(20) NOT NULL DEFAULT ''bind''');

DROP PROCEDURE IF EXISTS _kolom33;

-- Api_Whatsapp membaca baris pertama; tanpa baris, ia membaca properti dari
-- NULL (peringatan PHP) sebelum jatuh ke server bind.
INSERT INTO utility (server_wa)
SELECT 'bind' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM utility);
