-- =============================================================================
--  15_kategori_umkm.sql - kategori UMKM umum + ikon per kategori
--  Jalankan SETELAH 14_acc_chat.sql
--
--  Kategori bunga yang lama TIDAK dihapus - masih ada produk yang
--  memakainya (foreign key ON DELETE RESTRICT). Penjual bisa memindahkan
--  produknya ke kategori baru dari panel, lalu kategori lama dinonaktifkan.
-- =============================================================================

SET NAMES utf8mb4;

ALTER TABLE categories
  /* Nama ikon, bukan gambar. Tampilannya dipetakan di view, jadi mengganti
     gaya ikon cukup di satu tempat - tidak perlu mengunggah ulang berkas
     untuk tiap kategori. Nilai yang dikenali: makanan, kerajinan, fashion,
     kecantikan, hadiah, rumah. Selain itu memakai ikon umum. */
  ADD COLUMN icon VARCHAR(30) NULL AFTER slug,

  -- Satu baris penjelas di kartu kategori beranda.
  ADD COLUMN keterangan VARCHAR(120) NULL AFTER icon;

/* WHERE NOT EXISTS, bukan INSERT IGNORE - aman dijalankan ulang walau
   kolom slug ternyata tidak punya indeks unik. */
INSERT INTO categories (name, slug, icon, keterangan)
SELECT 'Makanan & Minuman', 'makanan-minuman', 'makanan', 'Camilan, lauk, kopi'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'makanan-minuman');

INSERT INTO categories (name, slug, icon, keterangan)
SELECT 'Kerajinan', 'kerajinan', 'kerajinan', 'Anyaman, ukiran, keramik'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'kerajinan');

INSERT INTO categories (name, slug, icon, keterangan)
SELECT 'Fashion', 'fashion', 'fashion', 'Batik, tenun, aksesori'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'fashion');

INSERT INTO categories (name, slug, icon, keterangan)
SELECT 'Kecantikan', 'kecantikan', 'kecantikan', 'Sabun, minyak, jamu'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'kecantikan');

INSERT INTO categories (name, slug, icon, keterangan)
SELECT 'Rumah Tangga', 'rumah-tangga', 'rumah', 'Dapur, dekorasi, tanaman'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'rumah-tangga');

-- Kategori bunga yang sudah ada diberi ikon hadiah.
UPDATE categories
   SET icon = 'hadiah',
       keterangan = COALESCE(keterangan, 'Buket, parsel, hampers')
 WHERE icon IS NULL
   AND (slug LIKE '%bunga%' OR name LIKE '%bunga%');
