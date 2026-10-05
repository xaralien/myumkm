-- =============================================================================
--  20_stok_batal_ulasan.sql
--
--  1. Stok produk
--  2. Pembatalan oleh pembeli (siapa yang membatalkan & alasannya)
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _kolom20;
DELIMITER $$
CREATE PROCEDURE _kolom20(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;


-- ---------------------------------------------------------------------------
--  1. STOK
--
--  NULL = tidak dibatasi, dan itu BAWAANNYA.
--
--  Banyak UMKM membuat barangnya sesuai pesanan - bunga papan, kue ulang
--  tahun, jahitan. Memaksa mereka mengisi angka stok berarti memaksa
--  menebak, lalu produknya hilang dari katalog begitu tebakannya habis.
--  Yang menjual barang jadi tinggal mengisi angkanya.
-- ---------------------------------------------------------------------------

CALL _kolom20('products', 'stock', 'INT UNSIGNED NULL DEFAULT NULL AFTER price');

-- Dipakai mengurutkan dan menyaring produk habis di katalog.
CALL _kolom20('products', 'stock_habis_at', 'DATETIME NULL AFTER stock');


-- ---------------------------------------------------------------------------
--  2. PEMBATALAN
--
--  Siapa yang membatalkan perlu dicatat: penjual yang melihat pesanannya
--  hilang berhak tahu apakah pembelinya yang membatalkan, atau sistem
--  karena pembayarannya kedaluwarsa.
-- ---------------------------------------------------------------------------

CALL _kolom20('orders', 'cancelled_by', "ENUM('customer','seller','system') NULL AFTER order_status");
CALL _kolom20('orders', 'cancel_reason', 'VARCHAR(255) NULL AFTER cancelled_by');
CALL _kolom20('orders', 'cancelled_at', 'DATETIME NULL AFTER cancel_reason');

DROP PROCEDURE IF EXISTS _kolom20;
