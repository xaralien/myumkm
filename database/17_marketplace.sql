-- =============================================================================
--  17_marketplace.sql - dari sistem pesan bunga menjadi marketplace UMKM
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang: setiap
--  perubahan memeriksa information_schema dulu. Sekaligus MENAMBAL migrasi
--  16 yang sebagian gagal di MySQL - MySQL tidak mendukung
--  "ADD COLUMN IF NOT EXISTS", jadi kolom alamat akun tidak pernah dibuat
--  dan users.role masih enum('admin','seller').
--
--  Jalankan lewat HeidiSQL (File > Run SQL file) atau:
--      mysql -u root -p myumkm < database/17_marketplace.sql
--
--  CADANGKAN DULU. Kolom tanggal kirim, kartu ucapan, dan persetujuan foto
--  dihapus beserta isinya.
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _tambah_kolom;
DROP PROCEDURE IF EXISTS _hapus_kolom;
DROP PROCEDURE IF EXISTS _hapus_indeks;
DROP PROCEDURE IF EXISTS _tambah_indeks;

DELIMITER $$

CREATE PROCEDURE _tambah_kolom(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$

CREATE PROCEDURE _hapus_kolom(IN t VARCHAR(64), IN c VARCHAR(64))
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` DROP COLUMN `', c, '`');
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$

CREATE PROCEDURE _hapus_indeks(IN t VARCHAR(64), IN i VARCHAR(64))
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.STATISTICS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND INDEX_NAME = i) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` DROP INDEX `', i, '`');
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$

CREATE PROCEDURE _tambah_indeks(IN t VARCHAR(64), IN i VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND INDEX_NAME = i) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$

DELIMITER ;


-- =============================================================================
--  1. AKUN (tambalan migrasi 16)
-- =============================================================================

/* Enum hanya menerima 'admin' dan 'seller' - pendaftaran akun baru yang
   menyimpan 'user' ditolak MySQL. Diubah ke VARCHAR, dan 'seller' disatukan
   ke 'user': penjual sekarang ditentukan dari punya toko, bukan perannya. */
ALTER TABLE users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'user';
UPDATE users SET role = 'user' WHERE role = 'seller';

CALL _tambah_kolom('users', 'address',     'VARCHAR(255) NULL AFTER phone');
CALL _tambah_kolom('users', 'province_id', 'INT UNSIGNED NULL AFTER address');
CALL _tambah_kolom('users', 'regency_id',  'INT UNSIGNED NULL AFTER province_id');
CALL _tambah_kolom('users', 'district_id', 'INT UNSIGNED NULL AFTER regency_id');


-- =============================================================================
--  2. TOKO
-- =============================================================================

/* Pengaturan khas toko bunga dihapus. Semuanya dipakai untuk menghitung
   jadwal kirim di hari yang sama - marketplace tidak memilih jam antar. */
CALL _hapus_kolom('stores', 'jeda_persiapan_menit');
CALL _hapus_kolom('stores', 'maks_hari_kedepan');
CALL _hapus_kolom('stores', 'acc_tunggu_jam');
CALL _hapus_kolom('stores', 'maks_revisi');
CALL _hapus_kolom('stores', 'open');
CALL _hapus_kolom('stores', 'close');

/* Gantinya satu angka yang dipahami pembeli marketplace: berapa hari
   sampai pesanan dikirim. Tampil di halaman produk dan checkout. */
CALL _tambah_kolom('stores', 'waktu_proses_hari',
     'TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER gratis_ongkir_min');

-- Rapikan spasi ganda supaya "Toko  Kue" dan "Toko Kue" terdeteksi sama.
UPDATE stores SET name = TRIM(REGEXP_REPLACE(name, '[[:space:]]+', ' '));

/* PERIKSA: kalau query ini mengembalikan baris, ada nama toko ganda -
   indeks unik di bawahnya akan GAGAL. Ganti dulu salah satu nama tokonya,
   lalu jalankan ulang berkas ini. */
SELECT LOWER(name) AS nama_ganda, COUNT(*) AS jml, GROUP_CONCAT(id) AS id_toko
  FROM stores GROUP BY LOWER(name) HAVING COUNT(*) > 1;

/* Nama unik & satu akun satu toko, dijaga database - bukan cuma PHP.
   Pengecekan PHP bisa kalah balapan kalau dua orang mendaftar di detik
   yang sama. Collation *_ci membuatnya tidak peka huruf besar-kecil. */
CALL _tambah_indeks('stores', 'uq_stores_name', 'UNIQUE INDEX `uq_stores_name` (`name`)');

/* ix_stores_user sudah ada sebagai indeks biasa; diganti versi UNIK.
   Foreign key fk_stores_user butuh indeks pada user_id - indeks unik
   dibuat LEBIH DULU supaya FK tidak pernah kehilangan indeksnya. */
CALL _tambah_indeks('stores', 'uq_stores_user', 'UNIQUE INDEX `uq_stores_user` (`user_id`)');
CALL _hapus_indeks('stores', 'ix_stores_user');


-- =============================================================================
--  3. PESANAN
-- =============================================================================

-- Indeks lama menyertakan delivery_date - harus dilepas sebelum kolomnya.
CALL _hapus_indeks('orders', 'ix_orders_status');
CALL _hapus_indeks('orders', 'ix_orders_store');
CALL _hapus_indeks('orders', 'ix_orders_acc');

-- Jadwal antar, kartu ucapan, mode kejutan, persetujuan foto rangkaian.
CALL _hapus_kolom('orders', 'delivery_date');
CALL _hapus_kolom('orders', 'delivery_time');
CALL _hapus_kolom('orders', 'delivery_slot');
CALL _hapus_kolom('orders', 'card_message');
CALL _hapus_kolom('orders', 'card_from');
CALL _hapus_kolom('orders', 'is_anonymous');
CALL _hapus_kolom('orders', 'surprise_mode');
CALL _hapus_kolom('orders', 'acc_status');
CALL _hapus_kolom('orders', 'acc_deadline');
CALL _hapus_kolom('orders', 'revisi_terpakai');
CALL _hapus_kolom('orders', 'card_locked_at');

/* Pengiriman ala marketplace: kurir + nomor resi diisi penjual saat
   menandai "Dikirim". completed_at diisi saat pembeli menekan
   "Pesanan diterima" - atau penjual menandainya selesai. */
CALL _tambah_kolom('orders', 'courier',         'VARCHAR(50) NULL AFTER shipping_fee');
CALL _tambah_kolom('orders', 'tracking_number', 'VARCHAR(60) NULL AFTER courier');
CALL _tambah_kolom('orders', 'shipped_at',      'DATETIME NULL AFTER paid_at');
CALL _tambah_kolom('orders', 'completed_at',    'DATETIME NULL AFTER shipped_at');

-- users.id bertipe INT UNSIGNED; user_id yang ditambahkan sebelumnya INT
-- bertanda. Disamakan supaya perbandingan & join tidak bergantung konversi.
CALL _tambah_kolom('orders', 'user_id', 'INT UNSIGNED NULL AFTER store_id');
ALTER TABLE orders MODIFY user_id INT UNSIGNED NULL;

CALL _tambah_indeks('orders', 'ix_orders_status', 'INDEX `ix_orders_status` (`order_status`, `created_at`)');
CALL _tambah_indeks('orders', 'ix_orders_store',  'INDEX `ix_orders_store` (`store_id`, `order_status`, `created_at`)');
CALL _tambah_indeks('orders', 'ix_orders_user',   'INDEX `ix_orders_user` (`user_id`, `created_at`)');


-- =============================================================================
--  4. PERCAKAPAN - foto persetujuan & foto lokasi jadi satu jenis "foto"
-- =============================================================================

ALTER TABLE order_messages
  MODIFY tipe ENUM('teks','foto','foto_acc','foto_lokasi','sistem') NOT NULL DEFAULT 'teks';
UPDATE order_messages SET tipe = 'foto' WHERE tipe IN ('foto_acc', 'foto_lokasi');
ALTER TABLE order_messages
  MODIFY tipe ENUM('teks','foto','sistem') NOT NULL DEFAULT 'teks';


-- =============================================================================
--  5. TABEL YANG TIDAK DIPAKAI
-- =============================================================================

/* Tambahan global (kartu ucapan, vas, cokelat) - sudah digantikan tambahan
   per produk di product_addons, dan tidak dirujuk kode mana pun. */
DROP TABLE IF EXISTS addons;


DROP PROCEDURE IF EXISTS _tambah_kolom;
DROP PROCEDURE IF EXISTS _hapus_kolom;
DROP PROCEDURE IF EXISTS _hapus_indeks;
DROP PROCEDURE IF EXISTS _tambah_indeks;
