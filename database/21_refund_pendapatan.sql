-- =============================================================================
--  21_refund_pendapatan.sql
--
--  1. Refund - permintaan pengembalian dana dan riwayatnya
--  2. Pembagian uang - berapa hak toko dari tiap pesanan, dan pencairannya
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _kolom21;
DELIMITER $$
CREATE PROCEDURE _kolom21(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;


-- ---------------------------------------------------------------------------
--  1. REFUND
--
--  Duitku tidak menyediakan API pengembalian dana, jadi transfernya
--  dilakukan manual oleh admin. Yang dikerjakan sistem adalah MENCATAT:
--  siapa meminta, siapa menyetujui, berapa, dan kapan uangnya dikirim.
--  Tanpa catatan ini, sengketa "sudah ditransfer atau belum" tidak punya
--  bukti selain ingatan masing-masing.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS refunds (
  id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id  BIGINT UNSIGNED NOT NULL,
  store_id  INT UNSIGNED NOT NULL,
  user_id   INT UNSIGNED NULL,

  /* Jumlah yang dikembalikan. Disimpan tersendiri, bukan selalu sama dengan
     total pesanan: ada kasus hanya sebagian barang yang bermasalah. */
  jumlah    BIGINT UNSIGNED NOT NULL,

  alasan    VARCHAR(500) NOT NULL,

  /* diminta  : pembeli mengajukan, menunggu penjual
     disetujui: penjual setuju, menunggu admin mentransfer
     ditolak  : penjual menolak, disertai catatan
     selesai  : uang sudah dikirim, nomor buktinya dicatat */
  status    ENUM('diminta','disetujui','ditolak','selesai') NOT NULL DEFAULT 'diminta',

  catatan_penjual VARCHAR(500) NULL,
  bukti_transfer  VARCHAR(120) NULL,

  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diputus_at  DATETIME NULL,
  selesai_at  DATETIME NULL,

  PRIMARY KEY (id),

  /* Satu pesanan hanya boleh punya satu permintaan yang masih hidup.
     Tanpa ini, pembeli yang menekan kirim dua kali membuat penjual melihat
     dua permintaan untuk hal yang sama. Dijaga di kode karena MySQL tidak
     bisa membuat indeks unik bersyarat. */
  KEY ix_refund_order (order_id, status),
  KEY ix_refund_toko (store_id, status, created_at),

  CONSTRAINT fk_refund_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_refund_toko  FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ---------------------------------------------------------------------------
--  2. PEMBAGIAN UANG
--
--  Semua pembayaran masuk ke satu akun Duitku milik marketplace. Jadi perlu
--  dicatat berapa bagian tiap toko, dan mana yang sudah dicairkan.
--
--  Angkanya disimpan di pesanan, bukan dihitung ulang saat dibutuhkan:
--  persentase komisi bisa berubah tahun depan, dan pesanan lama harus tetap
--  memakai angka yang berlaku saat itu.
-- ---------------------------------------------------------------------------

CALL _kolom21('orders', 'fee_platform', 'BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER total');
CALL _kolom21('orders', 'net_store',    'BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER fee_platform');
CALL _kolom21('orders', 'payout_id',    'BIGINT UNSIGNED NULL AFTER net_store');


CREATE TABLE IF NOT EXISTS payouts (
  id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,

  jumlah        BIGINT UNSIGNED NOT NULL,
  jumlah_order  INT UNSIGNED NOT NULL DEFAULT 0,

  /* Rekening disalin saat pencairan dibuat, tidak dibaca dari profil toko
     saat ditampilkan: kalau penjual mengganti rekeningnya nanti, riwayat
     pencairan lama harus tetap menunjukkan ke mana uangnya dulu dikirim. */
  bank_nama   VARCHAR(60) NULL,
  bank_nomor  VARCHAR(40) NULL,
  bank_atas_nama VARCHAR(100) NULL,

  status     ENUM('diproses','selesai','gagal') NOT NULL DEFAULT 'diproses',
  catatan    VARCHAR(255) NULL,
  bukti      VARCHAR(120) NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  selesai_at DATETIME NULL,

  PRIMARY KEY (id),
  KEY ix_payout_toko (store_id, status, created_at),

  CONSTRAINT fk_payout_toko FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Rekening penjual, untuk pencairan.
CALL _kolom21('stores', 'bank_nama',      'VARCHAR(60) NULL');
CALL _kolom21('stores', 'bank_nomor',     'VARCHAR(40) NULL');
CALL _kolom21('stores', 'bank_atas_nama', 'VARCHAR(100) NULL');

DROP PROCEDURE IF EXISTS _kolom21;
