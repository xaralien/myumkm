-- =============================================================================
--  32_moderasi_cms.sql
--
--  1. Moderasi: produk diturunkan admin, ulasan disembunyikan, laporan
--     dari pengguna.
--  2. CMS: banner beranda & halaman promo (kampanye).
--  3. Pusat sengketa pembeli-penjual yang ditengahi admin.
--  4. Broadcast pengumuman ke penjual.
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _kolom32;
DELIMITER $$
CREATE PROCEDURE _kolom32(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;

/* ---------------------------------------------------------------------------
   1. MODERASI
   Produk yang diturunkan admin otomatis disembunyikan (is_active = 0) dan
   TIDAK bisa ditayangkan lagi oleh penjual sampai admin memulihkannya.
   ------------------------------------------------------------------------- */
CALL _kolom32('products', 'diturunkan_at',     'DATETIME NULL');
CALL _kolom32('products', 'diturunkan_alasan', 'VARCHAR(255) NULL');
CALL _kolom32('reviews',  'is_hidden',         'TINYINT(1) NOT NULL DEFAULT 0');
CALL _kolom32('reviews',  'hidden_alasan',     'VARCHAR(255) NULL');

CREATE TABLE IF NOT EXISTS laporan (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  jenis      ENUM('produk','ulasan','toko') NOT NULL,
  objek_id   BIGINT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NULL,
  alasan     VARCHAR(60)  NOT NULL,
  keterangan VARCHAR(500) NULL,
  ip         VARCHAR(45)  NULL,
  status     ENUM('baru','ditindak','diabaikan') NOT NULL DEFAULT 'baru',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_laporan_status (status, created_at),
  KEY ix_laporan_objek (jenis, objek_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------------------------------------------------------------------------
   2. CMS
   ------------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS banners (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  judul      VARCHAR(120) NOT NULL,
  sub        VARCHAR(200) NULL,
  gambar     VARCHAR(80)  NULL,
  tautan     VARCHAR(255) NULL,
  warna      VARCHAR(20)  NULL,
  mulai      DATE NULL,
  selesai    DATE NULL,
  urutan     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Halaman kampanye: /promo/{slug}. Isinya teks + daftar produk yang
   disaring kategori dan/atau kata kunci - tanpa perlu mengubah kode. */
CREATE TABLE IF NOT EXISTS halaman_promo (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(120) NOT NULL,
  judul       VARCHAR(150) NOT NULL,
  isi         TEXT NULL,
  kategori_id INT UNSIGNED NULL,
  kata_kunci  VARCHAR(100) NULL,
  voucher     VARCHAR(30)  NULL,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_promo_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------------------------------------------------------------------------
   3. SENGKETA
   Dibuka pembeli (atau penjual) kalau refund ditolak / masalah tidak selesai
   lewat chat. Admin membaca kedua sisi lalu memutuskan.
   ------------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS sengketa (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id   BIGINT UNSIGNED NOT NULL,
  store_id   INT UNSIGNED NOT NULL,
  pelapor    ENUM('pembeli','penjual') NOT NULL DEFAULT 'pembeli',
  alasan     VARCHAR(60)  NOT NULL,
  tuntutan   ENUM('refund_penuh','refund_sebagian','tukar_barang','lainnya') NOT NULL DEFAULT 'refund_penuh',
  jumlah     BIGINT UNSIGNED NULL,
  status     ENUM('terbuka','ditinjau','menang_pembeli','menang_penjual','damai','ditutup') NOT NULL DEFAULT 'terbuka',
  keputusan  TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diputus_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY ix_sengketa_status (status, created_at),
  KEY ix_sengketa_order (order_id),
  CONSTRAINT fk_sengketa_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sengketa_pesan (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sengketa_id BIGINT UNSIGNED NOT NULL,
  pengirim    ENUM('pembeli','penjual','admin') NOT NULL,
  isi         TEXT NOT NULL,
  gambar      VARCHAR(80) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_spesan (sengketa_id, id),
  CONSTRAINT fk_spesan FOREIGN KEY (sengketa_id) REFERENCES sengketa (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------------------------------------------------------------------------
   4. BROADCAST
   ------------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS broadcast (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  judul       VARCHAR(150) NOT NULL,
  isi         TEXT NOT NULL,
  regency_id  INT UNSIGNED NULL,       -- NULL = semua wilayah
  category_id INT UNSIGNED NULL,       -- toko yang punya produk di kategori ini
  kirim_wa    TINYINT(1) NOT NULL DEFAULT 0,
  penerima    INT UNSIGNED NOT NULL DEFAULT 0,
  terkirim_wa INT UNSIGNED NOT NULL DEFAULT 0,
  dibuat_oleh INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS broadcast_toko (
  broadcast_id INT UNSIGNED NOT NULL,
  store_id     INT UNSIGNED NOT NULL,
  dibaca_at    DATETIME NULL,
  PRIMARY KEY (broadcast_id, store_id),
  KEY ix_bt_toko (store_id, dibaca_at),
  CONSTRAINT fk_bt_broadcast FOREIGN KEY (broadcast_id) REFERENCES broadcast (id) ON DELETE CASCADE,
  CONSTRAINT fk_bt_toko      FOREIGN KEY (store_id)     REFERENCES stores (id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP PROCEDURE IF EXISTS _kolom32;
