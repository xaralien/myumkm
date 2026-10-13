-- =============================================================================
-- 25_reset_sandi_verifikasi.sql
-- Reset sandi lewat email dan verifikasi alamat email.
--
-- Jalankan sekali:
--   mysql -u root -p myumkm < database/25_reset_sandi_verifikasi.sql
--
-- Aman dijalankan berulang. CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Kapan email diverifikasi. NULL = belum.
--
-- Disimpan sebagai waktu, bukan boolean. Dengan boolean, satu-satunya yang
-- bisa dijawab adalah "sudah atau belum"; dengan waktu kita juga bisa tahu
-- akun mana yang sudah lama terdaftar tapi emailnya tidak pernah diklik -
-- itu tanda alamatnya salah tulis atau memang bukan miliknya.
--
-- Lewat prosedur, bukan "ADD COLUMN IF NOT EXISTS": bentuk pendek itu hanya
-- ada di MariaDB, sedangkan MySQL 8 menolaknya. Cara ini jalan di keduanya,
-- dan sama dengan yang dipakai migrasi 20, 21 dan 23.
-- -----------------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _kolom25;
DELIMITER $$
CREATE PROCEDURE _kolom25(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;

CALL _kolom25('users', 'email_verified_at', 'DATETIME NULL DEFAULT NULL AFTER email');

DROP PROCEDURE IF EXISTS _kolom25;

-- Akun yang sudah ada dianggap terverifikasi. Mereka mendaftar sebelum fitur
-- ini ada, jadi menandai semuanya "belum" berarti menghukum mereka atas
-- sesuatu yang dulu tidak diminta - dan membanjiri mereka dengan spanduk.
UPDATE users SET email_verified_at = COALESCE(created_at, NOW())
 WHERE email_verified_at IS NULL;

-- -----------------------------------------------------------------------------
-- Token untuk tautan sekali-pakai di email.
--
-- Yang disimpan di sini HASH-nya, bukan tokennya.
--
-- Alasannya: token di tabel ini setara dengan sandi. Siapa pun yang bisa
-- membaca tabelnya - lewat SQL injection, cadangan database yang bocor, atau
-- sekadar phpMyAdmin di hosting bersama - bisa menempelkan token siapa saja
-- ke tautan reset dan mengambil alih akunnya tanpa pernah tahu sandinya.
-- Dengan hash, isi tabel ini tidak berguna: sha256 tidak bisa dibalik, dan
-- yang dikirim lewat email tidak pernah disimpan di mana pun.
--
-- sha256 cukup di sini - bukan bcrypt. Bcrypt dibuat lambat untuk melawan
-- penebakan sandi manusia yang pendek dan bisa diduga. Token di sini 32 byte
-- acak dari random_bytes(), jadi tidak ada yang bisa ditebak; yang dibutuhkan
-- hanya hash satu arah, dan sha256 cukup cepat untuk dicari lewat indeks.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auth_tokens (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,

  -- 'reset' atau 'verifikasi'. VARCHAR, bukan ENUM: menambah jenis baru
  -- nanti (ganti email, misalnya) tidak perlu ALTER TABLE pada tabel yang
  -- sedang dipakai.
  jenis       VARCHAR(16)  NOT NULL,

  token_hash  CHAR(64)     NOT NULL,
  expires_at  DATETIME     NOT NULL,

  -- Diisi saat token dipakai. Baris TIDAK dihapus setelah dipakai: kalau
  -- dihapus, token yang sudah habis dan token yang tidak pernah ada
  -- menghasilkan galat yang sama, dan kita kehilangan kemampuan memberi
  -- pesan "tautannya sudah pernah dipakai" - yang justru paling sering
  -- terjadi, karena banyak orang mengklik tautan di email dua kali.
  used_at     DATETIME     NULL DEFAULT NULL,

  -- Untuk menelusuri kalau ada yang menyalahgunakan. 45 karakter supaya
  -- IPv6 ikut masuk.
  ip          VARCHAR(45)  NULL DEFAULT NULL,

  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),

  -- UNIQUE, bukan KEY biasa: dua token dengan hash sama berarti ada yang
  -- salah pada pembangkit acaknya, dan lebih baik insert-nya gagal keras
  -- daripada dua akun berbagi satu tautan.
  UNIQUE KEY uq_auth_tokens_hash (token_hash),

  -- Dipakai pembatasan jumlah permintaan: "berapa token reset yang dibuat
  -- akun ini belakangan".
  KEY idx_auth_tokens_user (user_id, jenis, created_at),

  -- Dipakai pembersihan token kedaluwarsa.
  KEY idx_auth_tokens_expires (expires_at),

  CONSTRAINT fk_auth_tokens_user
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
