-- =============================================================================
--  19_ulasan.sql - ulasan & rating produk
--
--  Aturan yang ditegakkan di struktur tabelnya sendiri, bukan hanya di kode:
--
--  1. Satu baris pesanan = satu ulasan (uq_ulasan_item).
--     Tanpa itu, menekan tombol kirim dua kali menghasilkan dua ulasan dan
--     rata-ratanya ikut terdorong.
--
--  2. Ulasan selalu menunjuk ke pesanan sungguhan. Jadi yang bisa menulis
--     hanya orang yang benar-benar membeli - bukan siapa saja yang membuka
--     halaman produk.
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _kolom19;
DELIMITER $$
CREATE PROCEDURE _kolom19(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;


CREATE TABLE IF NOT EXISTS reviews (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id      BIGINT UNSIGNED NOT NULL,
  order_item_id BIGINT UNSIGNED NOT NULL,
  product_id    INT UNSIGNED NOT NULL,
  store_id      INT UNSIGNED NOT NULL,

  /* NULL untuk pembeli tamu - mereka tidak punya akun, dan haknya
     dibuktikan lewat access_token pesanan. */
  user_id       INT UNSIGNED NULL,

  /* Nama ditulis ulang di sini, tidak diambil dari users saat ditampilkan:
     ulasan adalah catatan pada satu waktu. Kalau pembeli mengganti namanya
     tahun depan, ulasan lamanya tidak ikut berubah. */
  nama          VARCHAR(100) NOT NULL,

  rating        TINYINT UNSIGNED NOT NULL,
  isi           TEXT NULL,
  variant_name  VARCHAR(100) NULL,

  /* Balasan penjual, ditampilkan menempel di bawah ulasannya. */
  balasan       TEXT NULL,
  balasan_at    DATETIME NULL,

  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_ulasan_item (order_item_id),

  KEY ix_ulasan_produk (product_id, created_at),
  KEY ix_ulasan_toko (store_id, created_at),
  KEY ix_ulasan_rating (product_id, rating),

  CONSTRAINT fk_ulasan_order   FOREIGN KEY (order_id)      REFERENCES orders (id)      ON DELETE CASCADE,
  CONSTRAINT fk_ulasan_item    FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE CASCADE,
  CONSTRAINT fk_ulasan_produk  FOREIGN KEY (product_id)    REFERENCES products (id)    ON DELETE CASCADE,
  CONSTRAINT fk_ulasan_toko    FOREIGN KEY (store_id)      REFERENCES stores (id)      ON DELETE CASCADE,
  CONSTRAINT ck_ulasan_rating  CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS review_media (
  id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  review_id BIGINT UNSIGNED NOT NULL,
  tipe      ENUM('foto','video') NOT NULL DEFAULT 'foto',
  berkas    VARCHAR(120) NOT NULL,
  urutan    TINYINT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (id),
  KEY ix_media_ulasan (review_id, urutan),

  CONSTRAINT fk_media_ulasan FOREIGN KEY (review_id) REFERENCES reviews (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


/* ---------------------------------------------------------------------------
   RINGKASAN DISIMPAN DI PRODUK & TOKO

   Rata-rata dihitung ulang setiap ada ulasan baru, lalu disimpan. Menghitung
   AVG() setiap kali katalog dibuka berarti menjumlahkan seluruh ulasan untuk
   tiap produk di halaman - lambat begitu ulasannya ribuan, padahal angkanya
   jarang berubah.
   --------------------------------------------------------------------------- */

CALL _kolom19('products', 'rating_avg',   'DECIMAL(2,1) NOT NULL DEFAULT 0');
CALL _kolom19('products', 'rating_count', 'INT UNSIGNED NOT NULL DEFAULT 0');
CALL _kolom19('stores',   'rating_avg',   'DECIMAL(2,1) NOT NULL DEFAULT 0');
CALL _kolom19('stores',   'rating_count', 'INT UNSIGNED NOT NULL DEFAULT 0');

DROP PROCEDURE IF EXISTS _kolom19;
