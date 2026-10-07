-- =============================================================================
--  22_buku_alamat.sql
--
--  Buku alamat pembeli: beberapa alamat tersimpan, masing-masing diberi nama
--  sendiri. Dipilih saat checkout.
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS user_addresses (
  id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id  INT UNSIGNED NOT NULL,

  /* Nama alamat, diisi bebas oleh pembeli: "Rumah", "Kantor", "Rumah Ibu".
     Pembeli mengenali alamatnya dari nama yang dia pakai sendiri, bukan dari
     potongan jalan yang mirip satu sama lain. */
  label    VARCHAR(40) NOT NULL,

  /* Penerima sering BUKAN pembeli - kurir menghubungi orang di tujuan,
     bukan pemesannya. */
  recipient_name  VARCHAR(100) NOT NULL,
  recipient_phone VARCHAR(25) NOT NULL,

  address   VARCHAR(500) NOT NULL,
  /* Patokan jauh lebih berguna bagi kurir daripada alamat formal. */
  landmark  VARCHAR(160) NULL,
  postcode  VARCHAR(10) NULL,

  province_id INT UNSIGNED NULL,
  regency_id  INT UNSIGNED NULL,
  district_id INT UNSIGNED NULL,

  latitude  DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,

  /* Satu alamat ditandai utama, terpilih sendiri di checkout. Mengetik ulang
     alamat tiap belanja adalah alasan umum orang membatalkan di tengah. */
  is_primary TINYINT(1) NOT NULL DEFAULT 0,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY ix_alamat_user (user_id, is_primary, id),

  CONSTRAINT fk_alamat_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ---------------------------------------------------------------------------
--  Catatan penting: pesanan TIDAK merujuk ke tabel ini.
--
--  Alamat disalin utuh ke kolom recipient_* milik pesanan saat checkout.
--  Kalau pembeli mengubah atau menghapus alamatnya nanti, pesanan lama harus
--  tetap menunjukkan ke mana barang dulu dikirim - bukan ikut berubah.
--
--  Jadi tidak ada foreign key dari orders ke user_addresses, dan itu memang
--  disengaja.
-- ---------------------------------------------------------------------------
