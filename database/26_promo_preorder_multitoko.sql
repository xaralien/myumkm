-- =============================================================================
--  26_promo_preorder_multitoko.sql
--
--  1. Voucher toko & platform, catatan pemakaiannya, dan potongan di pesanan.
--  2. Harga grosir (harga satuan bertingkat menurut jumlah beli).
--  3. Pre-order per produk: lama produksi, kuota harian, uang muka (DP),
--     dan tanggal kirim pilihan pembeli.
--  4. Tagihan gabungan: satu pembayaran untuk beberapa pesanan sekaligus
--     (checkout multi-toko), juga untuk DP dan pelunasan pre-order.
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _kolom26;
DELIMITER $$
CREATE PROCEDURE _kolom26(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;

/* ---------------------------------------------------------------------------
   1. VOUCHER
   store_id NULL = voucher platform (dibuat admin, ditanggung platform).
   jenis: persen  -> nilai = persen, dibatasi maks_potongan
          nominal -> nilai = rupiah
          ongkir  -> potongan ongkir sampai nilai rupiah
   ------------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS vouchers (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id      INT UNSIGNED NULL,
  kode          VARCHAR(30)  NOT NULL,
  nama          VARCHAR(100) NOT NULL,
  jenis         ENUM('persen','nominal','ongkir') NOT NULL DEFAULT 'nominal',
  nilai         INT UNSIGNED NOT NULL,
  maks_potongan INT UNSIGNED NULL,
  min_belanja   INT UNSIGNED NOT NULL DEFAULT 0,
  kuota         INT UNSIGNED NULL,
  terpakai      INT UNSIGNED NOT NULL DEFAULT 0,
  per_pembeli   TINYINT UNSIGNED NOT NULL DEFAULT 1,
  mulai         DATETIME NULL,
  selesai       DATETIME NULL,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  /* Kode unik di seluruh marketplace: pembeli cukup mengetik kodenya,
     sistem yang mencari milik toko mana. */
  UNIQUE KEY uq_voucher_kode (kode),
  KEY ix_voucher_toko (store_id, is_active),
  CONSTRAINT fk_voucher_toko FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS voucher_pemakaian (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  voucher_id INT UNSIGNED NOT NULL,
  order_id   BIGINT UNSIGNED NOT NULL,
  user_id    INT UNSIGNED NULL,
  phone      VARCHAR(25) NULL,
  potongan   BIGINT UNSIGNED NOT NULL,
  /* Satu checkout = satu grup. Voucher platform yang dibagi ke beberapa
     toko tercatat per pesanan, tapi kuotanya hanya terpakai SEKALI. */
  grup       VARCHAR(40) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_pakai_voucher (voucher_id, user_id),
  KEY ix_pakai_grup (voucher_id, grup),
  KEY ix_pakai_order (order_id),
  CONSTRAINT fk_pakai_voucher FOREIGN KEY (voucher_id) REFERENCES vouchers (id) ON DELETE CASCADE,
  CONSTRAINT fk_pakai_order   FOREIGN KEY (order_id)   REFERENCES orders (id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Potongan dicatat terpisah menurut SIAPA yang menanggungnya:
   diskon_toko     - voucher toko, mengurangi bagian toko
   diskon_platform - voucher platform, ditanggung marketplace
   subsidi_ongkir  - subsidi ongkir otomatis dari Pengaturan, ditanggung
                     marketplace. Toko tetap menerima ongkir utuh. */
CALL _kolom26('orders', 'diskon_toko',      'BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER shipping_fee');
CALL _kolom26('orders', 'diskon_platform',  'BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER diskon_toko');
CALL _kolom26('orders', 'subsidi_ongkir',   'BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER diskon_platform');
CALL _kolom26('orders', 'voucher_toko',     'VARCHAR(30) NULL AFTER subsidi_ongkir');
CALL _kolom26('orders', 'voucher_platform', 'VARCHAR(30) NULL AFTER voucher_toko');

/* ---------------------------------------------------------------------------
   2. HARGA GROSIR
   Harga SATUAN produk saat jumlah beli mencapai min_qty. Selisih varian dan
   tambahan tetap ditambahkan di atasnya.
   ------------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS product_tiers (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  min_qty    SMALLINT UNSIGNED NOT NULL,
  harga      INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tier (product_id, min_qty),
  CONSTRAINT fk_tier_produk FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CALL _kolom26('order_items', 'harga_catatan', 'VARCHAR(60) NULL');

/* ---------------------------------------------------------------------------
   3. PRE-ORDER
   ------------------------------------------------------------------------- */
CALL _kolom26('products', 'is_preorder',     'TINYINT(1) NOT NULL DEFAULT 0');
CALL _kolom26('products', 'po_hari',         'TINYINT UNSIGNED NOT NULL DEFAULT 3');
CALL _kolom26('products', 'po_kuota_harian', 'SMALLINT UNSIGNED NULL');
CALL _kolom26('products', 'po_dp_persen',    'TINYINT UNSIGNED NOT NULL DEFAULT 0');

/* tanggal_kirim: tanggal kirim/ambil yang dipilih pembeli untuk pesanan
   berisi produk pre-order. dp_jumlah: uang muka yang dibayar lebih dulu;
   NULL berarti dibayar penuh. lunas_at: kapan sisanya dilunasi. */
CALL _kolom26('orders', 'tanggal_kirim', 'DATE NULL');
CALL _kolom26('orders', 'dp_jumlah',     'BIGINT UNSIGNED NULL');
CALL _kolom26('orders', 'lunas_at',      'DATETIME NULL');
CALL _kolom26('order_items', 'is_preorder', 'TINYINT(1) NOT NULL DEFAULT 0');

/* ---------------------------------------------------------------------------
   4. TAGIHAN GABUNGAN
   kode dipakai sebagai merchantOrderId Duitku (awalan TG-), jadi tidak
   bentrok dengan nomor pesanan biasa.
   jenis: grup      - checkout beberapa toko sekaligus (bisa juga berisi DP)
          dp        - uang muka satu pesanan pre-order
          pelunasan - sisa pembayaran pre-order
   ------------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS tagihan (
  id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode               VARCHAR(40) NOT NULL,
  access_token       VARCHAR(64) NOT NULL,
  jenis              ENUM('grup','dp','pelunasan') NOT NULL,
  jumlah             BIGINT UNSIGNED NOT NULL,
  status             ENUM('unpaid','paid','failed','expired') NOT NULL DEFAULT 'unpaid',
  customer_name      VARCHAR(100) NULL,
  customer_phone     VARCHAR(25) NULL,
  customer_email     VARCHAR(150) NULL,
  duitku_reference   VARCHAR(100) NULL,
  duitku_payment_url VARCHAR(255) NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at            DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tagihan_kode (kode),
  KEY ix_tagihan_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tagihan_order (
  tagihan_id BIGINT UNSIGNED NOT NULL,
  order_id   BIGINT UNSIGNED NOT NULL,
  jumlah     BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (tagihan_id, order_id),
  KEY ix_to_order (order_id),
  CONSTRAINT fk_to_tagihan FOREIGN KEY (tagihan_id) REFERENCES tagihan (id) ON DELETE CASCADE,
  CONSTRAINT fk_to_order   FOREIGN KEY (order_id)   REFERENCES orders (id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tagihan PERTAMA pesanan ini (grup atau DP). Pesanan tanpa tagihan_id
   dibayar lewat alur lama: nomor pesanan = merchantOrderId. */
CALL _kolom26('orders', 'tagihan_id', 'BIGINT UNSIGNED NULL');

DROP PROCEDURE IF EXISTS _kolom26;
