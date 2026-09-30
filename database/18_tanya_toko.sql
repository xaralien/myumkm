-- =============================================================================
--  18_tanya_toko.sql - percakapan yang tidak menempel pada pesanan
--
--  Sebelum ini, satu percakapan = satu pesanan. "Tanya ke toko" terjadi
--  SEBELUM ada pesanan, jadi butuh wadah sendiri.
--
--  Bentuknya: tabel `conversations` jadi wadah, dan order_messages menunjuk
--  ke sana. Percakapan pesanan tetap ada - hanya sekarang ia satu baris di
--  conversations yang kebetulan punya order_id.
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _tambah_kolom18;
DROP PROCEDURE IF EXISTS _tambah_indeks18;

DELIMITER $$
CREATE PROCEDURE _tambah_kolom18(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
CREATE PROCEDURE _tambah_indeks18(IN t VARCHAR(64), IN i VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND INDEX_NAME = i) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;


-- ---------------------------------------------------------------------------
--  1. WADAH PERCAKAPAN
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS conversations (
  id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id INT UNSIGNED NOT NULL,

  /* Pembeli. NULL untuk percakapan pesanan tamu - mereka tidak punya akun,
     dan haknya dibuktikan lewat access_token pesanan. */
  user_id  INT UNSIGNED NULL,

  /* Diisi kalau percakapan ini tentang satu pesanan. NULL berarti tanya
     sebelum beli. UNIK: satu pesanan cukup satu percakapan - tanpa itu,
     dua permintaan yang datang bersamaan bisa membuat dua ruang terpisah
     untuk pesanan yang sama, dan pesannya terbelah. */
  order_id BIGINT UNSIGNED NULL,

  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_message_at DATETIME NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_conv_order (order_id),

  /* Satu pembeli cukup punya SATU percakapan umum per toko. Pertanyaan
     berikutnya menyambung di ruang yang sama, bukan menumpuk jadi daftar
     panjang berisi satu pesan masing-masing. */
  UNIQUE KEY uq_conv_toko_user (store_id, user_id, order_id),

  KEY ix_conv_toko (store_id, last_message_at),
  KEY ix_conv_user (user_id, last_message_at),

  CONSTRAINT fk_conv_store FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE,
  CONSTRAINT fk_conv_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ---------------------------------------------------------------------------
--  2. PESAN MENUNJUK KE WADAH
-- ---------------------------------------------------------------------------

CALL _tambah_kolom18('order_messages', 'conversation_id', 'BIGINT UNSIGNED NULL AFTER order_id');

/* Kartu produk di dalam gelembung pesan: "saya menanyakan produk ini".
   Disimpan sebagai rujukan, bukan salinan nama & harga - kalau penjual
   mengubah harganya nanti, kartu di percakapan lama ikut menunjukkan
   harga yang berlaku sekarang, bukan angka usang. */
CALL _tambah_kolom18('order_messages', 'product_id', 'INT UNSIGNED NULL AFTER image');

CALL _tambah_indeks18('order_messages', 'ix_msg_conv', 'INDEX `ix_msg_conv` (`conversation_id`, `id`)');

-- order_id kini boleh kosong: pesan pada percakapan tanya-toko tidak punya
-- pesanan sama sekali.
ALTER TABLE order_messages MODIFY order_id BIGINT UNSIGNED NULL;


-- ---------------------------------------------------------------------------
--  3. PINDAHKAN PERCAKAPAN PESANAN YANG SUDAH ADA
-- ---------------------------------------------------------------------------

-- Satu wadah untuk tiap pesanan yang sudah punya pesan.
INSERT INTO conversations (store_id, user_id, order_id, created_at, last_message_at)
SELECT o.store_id, o.user_id, o.id, MIN(m.created_at), MAX(m.created_at)
  FROM order_messages m
  JOIN orders o ON o.id = m.order_id
 WHERE m.conversation_id IS NULL
 GROUP BY o.id, o.store_id, o.user_id;

UPDATE order_messages m
  JOIN conversations c ON c.order_id = m.order_id
   SET m.conversation_id = c.id
 WHERE m.conversation_id IS NULL;


DROP PROCEDURE IF EXISTS _tambah_kolom18;
DROP PROCEDURE IF EXISTS _tambah_indeks18;
