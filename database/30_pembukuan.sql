-- =============================================================================
--  30_pembukuan.sql - pembukuan sederhana (kas masuk & keluar) untuk penjual.
--
--  Penjualan di marketplace TIDAK disalin ke tabel ini - dihitung langsung
--  dari tabel orders (bagian toko = net_store) saat laporan dibuka, supaya
--  tidak pernah ada dua angka berbeda untuk hal yang sama. Tabel ini hanya
--  menyimpan catatan yang diketik penjual: penjualan di luar aplikasi
--  (warung, pasar, titipan) dan pengeluaran usaha.
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS kas (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id   INT UNSIGNED NOT NULL,
  tanggal    DATE NOT NULL,
  jenis      ENUM('masuk','keluar') NOT NULL,
  kategori   VARCHAR(40) NOT NULL,
  jumlah     BIGINT UNSIGNED NOT NULL,
  keterangan VARCHAR(200) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_kas_toko (store_id, tanggal),
  CONSTRAINT fk_kas_toko FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
