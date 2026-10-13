-- =============================================================================
--  25_wishlist_ikuti_label.sql
--
--  1. Wishlist (produk favorit) pembeli.
--  2. Ikuti toko - pelanggan setia melihat produk baru toko langganannya.
--  3. Label produk UMKM: buatan tangan, bahan lokal, nomor Halal & P-IRT.
--  4. Jumlah terjual disimpan di produk, untuk urutan "Terlaris".
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _kolom25;
DROP PROCEDURE IF EXISTS _indeks25;
DELIMITER $$
CREATE PROCEDURE _kolom25(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
CREATE PROCEDURE _indeks25(IN t VARCHAR(64), IN i VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND INDEX_NAME = i) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;

CREATE TABLE IF NOT EXISTS wishlists (
  user_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, product_id),
  KEY ix_wish_produk (product_id),
  CONSTRAINT fk_wish_user   FOREIGN KEY (user_id)    REFERENCES users (id)    ON DELETE CASCADE,
  CONSTRAINT fk_wish_produk FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS store_follows (
  user_id    INT UNSIGNED NOT NULL,
  store_id   INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, store_id),
  KEY ix_follow_toko (store_id),
  CONSTRAINT fk_follow_user FOREIGN KEY (user_id)  REFERENCES users (id)  ON DELETE CASCADE,
  CONSTRAINT fk_follow_toko FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Label diisi penjual. Nomor Halal/P-IRT ditampilkan apa adanya sebagai
   klaim penjual; tanda "terverifikasi" baru muncul kalau dokumen tokonya
   sudah diperiksa admin (lihat 29_naik_kelas.sql). */
CALL _kolom25('products', 'buatan_tangan', 'TINYINT(1) NOT NULL DEFAULT 0');
CALL _kolom25('products', 'bahan_lokal',   'TINYINT(1) NOT NULL DEFAULT 0');
CALL _kolom25('products', 'halal_no',      'VARCHAR(60) NULL');
CALL _kolom25('products', 'pirt_no',       'VARCHAR(60) NULL');

/* Disimpan, bukan dihitung tiap halaman dibuka: mengurutkan katalog
   berdasarkan SUM() dari seluruh order_items terlalu berat. Dinaikkan
   setiap pembayaran masuk (Order_model::mark_paid). */
CALL _kolom25('products', 'terjual_count', 'INT UNSIGNED NOT NULL DEFAULT 0');
CALL _indeks25('products', 'ix_produk_terjual', 'INDEX `ix_produk_terjual` (`terjual_count`)');

UPDATE products p
   SET p.terjual_count = (SELECT COALESCE(SUM(oi.qty), 0)
                            FROM order_items oi
                            JOIN orders o ON o.id = oi.order_id
                           WHERE oi.product_id = p.id AND o.payment_status = 'paid');

DROP PROCEDURE IF EXISTS _kolom25;
DROP PROCEDURE IF EXISTS _indeks25;
