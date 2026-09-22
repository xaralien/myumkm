-- =============================================================================
--  16_akun_pengguna.sql - akun pembeli, 1 akun = 1 toko
--  Jalankan SETELAH 15_kategori_umkm.sql
--
--  Ditulis untuk MariaDB (bawaan XAMPP) - memakai IF NOT EXISTS supaya aman
--  dijalankan ulang. MySQL tidak mendukung IF NOT EXISTS pada ADD COLUMN;
--  kalau memakai MySQL, hapus kata IF NOT EXISTS dan jalankan sekali saja.
-- =============================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
--  1. PERIKSA DULU: nama toko ganda
--
--  Indeks unik di langkah 4 akan GAGAL kalau sudah ada dua toko bernama
--  sama. Jalankan query ini lebih dulu; kalau ada hasilnya, ganti salah
--  satu nama tokonya sebelum melanjutkan.
-- ---------------------------------------------------------------------------
SELECT LOWER(TRIM(name)) AS nama, COUNT(*) AS jml, GROUP_CONCAT(id) AS id_toko
  FROM stores
 GROUP BY LOWER(TRIM(name))
HAVING COUNT(*) > 1;

-- Satu akun hanya boleh punya satu toko - periksa juga.
SELECT user_id, COUNT(*) AS jml FROM stores GROUP BY user_id HAVING COUNT(*) > 1;


-- ---------------------------------------------------------------------------
--  2. PENGGUNA
-- ---------------------------------------------------------------------------

/* Peran disederhanakan jadi dua: 'admin' dan 'user'. Apakah seseorang
   penjual tidak lagi ditentukan perannya, tapi dari punya toko atau tidak.
   Dengan begitu satu akun bisa belanja DAN berjualan tanpa dua login. */
ALTER TABLE users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'user';
UPDATE users SET role = 'user' WHERE role = 'seller';

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS phone       VARCHAR(20)  NULL AFTER email,
  ADD COLUMN IF NOT EXISTS avatar      VARCHAR(191) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS address     VARCHAR(255) NULL AFTER avatar,
  ADD COLUMN IF NOT EXISTS province_id INT UNSIGNED NULL AFTER address,
  ADD COLUMN IF NOT EXISTS regency_id  INT UNSIGNED NULL AFTER province_id,
  ADD COLUMN IF NOT EXISTS district_id INT UNSIGNED NULL AFTER regency_id,
  ADD COLUMN IF NOT EXISTS created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Email jadi kunci login - dua akun dengan email sama berarti salah satunya
-- tidak pernah bisa masuk.
ALTER TABLE users ADD UNIQUE INDEX IF NOT EXISTS uq_users_email (email);


-- ---------------------------------------------------------------------------
--  3. PESANAN tersimpan ke akun
--
--  NULL untuk pesanan tamu. Pesanan tamu yang LAMA sengaja tidak
--  dihubungkan ke akun berdasarkan nomor HP - nomor HP tidak diverifikasi,
--  jadi siapa pun bisa mendaftar dengan nomor orang lain lalu melihat
--  alamat penerima pesanannya.
-- ---------------------------------------------------------------------------
ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS user_id INT UNSIGNED NULL AFTER store_id,
  ADD INDEX IF NOT EXISTS ix_orders_user (user_id, created_at);


-- ---------------------------------------------------------------------------
--  4. TOKO: 1 akun 1 toko, nama unik
-- ---------------------------------------------------------------------------

-- Rapikan spasi lebih dulu, supaya "Toko  Bunga" dan "Toko Bunga" terdeteksi
-- sebagai nama yang sama oleh indeks di bawah.
UPDATE stores SET name = TRIM(REGEXP_REPLACE(name, '[[:space:]]+', ' '));

/* Indeks unik di database, bukan cuma pengecekan di PHP. Pengecekan di PHP
   bisa kalah balapan - dua orang mendaftarkan nama yang sama di detik yang
   sama, keduanya lolos pengecekan, keduanya tersimpan. Indeks ini yang
   menolak yang kedua.

   Collation *_ci membuat perbandingannya tidak peka huruf besar-kecil,
   jadi "Toko Bunga" dan "TOKO BUNGA" juga ditolak. */
ALTER TABLE stores ADD UNIQUE INDEX IF NOT EXISTS uq_stores_name (name);
ALTER TABLE stores ADD UNIQUE INDEX IF NOT EXISTS uq_stores_user (user_id);
