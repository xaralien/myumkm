-- =============================================================================
--  29_naik_kelas.sql
--
--  1. Dokumen legalitas toko (NIB, P-IRT, Halal, ...) + verifikasi admin.
--  2. Akademi UMKM: modul belajar, kuis, progres, jadwal kelas/konsultasi.
--  3. Pendamping UMKM: akun pendamping, toko binaan, catatan kunjungan.
--
--  Skor "Naik Kelas" TIDAK disimpan - dihitung dari data yang sudah ada
--  setiap kali dibuka (Naik_kelas_lib), jadi selalu sesuai keadaan toko.
--
--  Untuk MySQL 8 (juga jalan di MariaDB). Aman dijalankan berulang.
--  CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _kolom29;
DELIMITER $$
CREATE PROCEDURE _kolom29(IN t VARCHAR(64), IN c VARCHAR(64), IN def TEXT)
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t AND COLUMN_NAME = c) THEN
    SET @q = CONCAT('ALTER TABLE `', t, '` ADD COLUMN `', c, '` ', def);
    PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
  END IF;
END$$
DELIMITER ;

/* ---------------------------------------------------------------------------
   1. LEGALITAS
   Berkas disimpan di upload/dokumen/ dengan nama acak dan HANYA bisa dibuka
   lewat controller (pemilik toko, admin, atau pendampingnya) - dokumen
   seperti KTP tidak boleh terbuka untuk umum.
   ------------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS store_documents (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id      INT UNSIGNED NOT NULL,
  jenis         ENUM('nib','pirt','halal','ktp','npwp','lainnya') NOT NULL,
  nomor         VARCHAR(80)  NULL,
  berkas        VARCHAR(80)  NULL,
  berlaku_sampai DATE        NULL,
  status        ENUM('menunggu','terverifikasi','ditolak') NOT NULL DEFAULT 'menunggu',
  catatan_admin VARCHAR(255) NULL,
  diperiksa_oleh INT UNSIGNED NULL,
  diperiksa_at  DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_dok_toko_jenis (store_id, jenis),
  KEY ix_dok_status (status, created_at),
  CONSTRAINT fk_dok_toko FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------------------------------------------------------------------------
   2. AKADEMI UMKM
   ------------------------------------------------------------------------- */
CREATE TABLE IF NOT EXISTS akademi_modul (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  judul        VARCHAR(150) NOT NULL,
  slug         VARCHAR(170) NOT NULL,
  kategori     VARCHAR(40)  NOT NULL DEFAULT 'Umum',
  ringkas      VARCHAR(255) NULL,
  isi          MEDIUMTEXT   NULL,
  video_url    VARCHAR(255) NULL,
  durasi_menit SMALLINT UNSIGNED NOT NULL DEFAULT 5,
  urutan       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_modul_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS akademi_soal (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  modul_id   INT UNSIGNED NOT NULL,
  pertanyaan VARCHAR(255) NOT NULL,
  pilihan    TEXT NOT NULL,          -- JSON: ["A", "B", "C"]
  jawaban    TINYINT UNSIGNED NOT NULL,
  urutan     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_soal_modul (modul_id, urutan),
  CONSTRAINT fk_soal_modul FOREIGN KEY (modul_id) REFERENCES akademi_modul (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS akademi_progres (
  store_id   INT UNSIGNED NOT NULL,
  modul_id   INT UNSIGNED NOT NULL,
  skor       TINYINT UNSIGNED NULL,
  selesai_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (store_id, modul_id),
  CONSTRAINT fk_prog_toko  FOREIGN KEY (store_id) REFERENCES stores (id)        ON DELETE CASCADE,
  CONSTRAINT fk_prog_modul FOREIGN KEY (modul_id) REFERENCES akademi_modul (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Kelas tatap muka / daring dan sesi konsultasi dengan pendamping. */
CREATE TABLE IF NOT EXISTS akademi_jadwal (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  judul      VARCHAR(150) NOT NULL,
  jenis      ENUM('kelas','konsultasi') NOT NULL DEFAULT 'kelas',
  waktu      DATETIME NOT NULL,
  tempat     VARCHAR(255) NULL,      -- alamat atau tautan Zoom/Meet
  keterangan VARCHAR(500) NULL,
  kuota      SMALLINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_jadwal_waktu (waktu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS akademi_daftar (
  jadwal_id  INT UNSIGNED NOT NULL,
  store_id   INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (jadwal_id, store_id),
  CONSTRAINT fk_daftar_jadwal FOREIGN KEY (jadwal_id) REFERENCES akademi_jadwal (id) ON DELETE CASCADE,
  CONSTRAINT fk_daftar_toko   FOREIGN KEY (store_id)  REFERENCES stores (id)          ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------------------------------------------------------------------------
   3. PENDAMPING
   Pendamping = akun dengan role 'pendamping' (users.role sudah VARCHAR).
   ------------------------------------------------------------------------- */
CALL _kolom29('stores', 'pendamping_id', 'INT UNSIGNED NULL');

CREATE TABLE IF NOT EXISTS pendampingan_catatan (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  store_id      INT UNSIGNED NOT NULL,
  pendamping_id INT UNSIGNED NOT NULL,
  tanggal       DATE NOT NULL,
  jenis         ENUM('kunjungan','telepon','kelas','lainnya') NOT NULL DEFAULT 'kunjungan',
  catatan       TEXT NOT NULL,
  tindak_lanjut VARCHAR(255) NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_catatan_toko (store_id, tanggal),
  CONSTRAINT fk_catatan_toko FOREIGN KEY (store_id) REFERENCES stores (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---------------------------------------------------------------------------
   Modul awal Akademi - supaya halaman tidak kosong saat pertama dibuka.
   Admin bisa mengubah atau menambah dari panel.
   ------------------------------------------------------------------------- */
INSERT INTO akademi_modul (judul, slug, kategori, ringkas, isi, durasi_menit, urutan)
SELECT 'Foto produk yang menjual, cukup pakai HP', 'foto-produk-pakai-hp', 'Mulai',
       'Cahaya, latar, dan sudut yang membuat produk terlihat menarik.',
       '<p>Foto adalah hal pertama yang dilihat pembeli. Tiga kunci sederhana:</p><ol><li><strong>Cahaya alami</strong> - foto di dekat jendela pada pagi atau sore hari. Hindari lampu kuning dan flash.</li><li><strong>Latar polos</strong> - pakai kain, karton putih, atau meja kayu. Singkirkan barang lain.</li><li><strong>Tiga sudut</strong> - depan, samping, dan detail (tekstur, isi, ukuran dibanding tangan).</li></ol><p>Bersihkan lensa HP dengan kain sebelum memotret. Foto mendatar (lanskap) dipotong; gunakan foto persegi.</p>',
       5, 1
WHERE NOT EXISTS (SELECT 1 FROM akademi_modul WHERE slug = 'foto-produk-pakai-hp');

INSERT INTO akademi_modul (judul, slug, kategori, ringkas, isi, durasi_menit, urutan)
SELECT 'Menentukan harga jual yang untung', 'menentukan-harga-jual', 'Keuangan',
       'Hitung modal per barang, lalu tambahkan keuntungan yang wajar.',
       '<p>Rumus sederhana: <strong>Harga jual = (bahan + kemasan + tenaga + biaya lain) per barang + keuntungan</strong>.</p><ul><li>Hitung semua bahan untuk satu kali produksi, lalu bagi dengan jumlah barang yang dihasilkan.</li><li>Jangan lupa upahmu sendiri - waktu juga modal.</li><li>Keuntungan UMKM pangan umumnya 20-40% dari modal.</li><li>Ingat komisi marketplace dipotong dari harga barang.</li></ul><p>Catat pengeluaran di menu <em>Pembukuan</em> supaya hitunganmu berdasarkan angka nyata.</p>',
       7, 2
WHERE NOT EXISTS (SELECT 1 FROM akademi_modul WHERE slug = 'menentukan-harga-jual');

INSERT INTO akademi_modul (judul, slug, kategori, ringkas, isi, durasi_menit, urutan)
SELECT 'Mengurus NIB lewat OSS, gratis', 'mengurus-nib-oss', 'Legalitas',
       'Nomor Induk Berusaha adalah pintu masuk P-IRT, Halal, dan KUR.',
       '<p>NIB diurus <strong>gratis</strong> di oss.go.id. Siapkan: KTP, NPWP (kalau ada), email aktif, nomor HP, dan alamat usaha.</p><ol><li>Daftar akun di oss.go.id (pilih Usaha Mikro dan Kecil).</li><li>Isi data usaha dan pilih KBLI yang sesuai produkmu.</li><li>Terbitkan NIB - biasanya selesai di hari yang sama.</li></ol><p>Setelah terbit, unggah NIB di menu <em>Legalitas</em> supaya tokomu mendapat lencana terverifikasi.</p>',
       8, 3
WHERE NOT EXISTS (SELECT 1 FROM akademi_modul WHERE slug = 'mengurus-nib-oss');

INSERT INTO akademi_modul (judul, slug, kategori, ringkas, isi, durasi_menit, urutan)
SELECT 'Membalas chat pembeli dengan ramah dan cepat', 'membalas-chat-pembeli', 'Layanan',
       'Balasan cepat menaikkan peluang pesanan berkali-kali lipat.',
       '<p>Pembeli yang dibalas dalam 5 menit jauh lebih mungkin membeli. Tips:</p><ul><li>Sapa dengan nama, jawab pertanyaannya langsung.</li><li>Pakai <em>Balasan cepat</em> untuk pertanyaan yang sering muncul.</li><li>Kirim foto asli produk kalau ditanya detail.</li><li>Tutup dengan ajakan: "Silakan dipesan lewat tombol Beli ya, kak."</li></ul>',
       4, 4
WHERE NOT EXISTS (SELECT 1 FROM akademi_modul WHERE slug = 'membalas-chat-pembeli');

INSERT INTO akademi_soal (modul_id, pertanyaan, pilihan, jawaban, urutan)
SELECT m.id, 'Cahaya terbaik untuk memotret produk adalah...', '["Lampu kuning di malam hari","Cahaya alami dari jendela","Flash HP"]', 1, 1
  FROM akademi_modul m WHERE m.slug = 'foto-produk-pakai-hp'
   AND NOT EXISTS (SELECT 1 FROM akademi_soal s WHERE s.modul_id = m.id);
INSERT INTO akademi_soal (modul_id, pertanyaan, pilihan, jawaban, urutan)
SELECT m.id, 'Komponen yang sering lupa dihitung dalam modal adalah...', '["Bahan baku","Upah/tenaga sendiri","Kemasan"]', 1, 1
  FROM akademi_modul m WHERE m.slug = 'menentukan-harga-jual'
   AND NOT EXISTS (SELECT 1 FROM akademi_soal s WHERE s.modul_id = m.id);
INSERT INTO akademi_soal (modul_id, pertanyaan, pilihan, jawaban, urutan)
SELECT m.id, 'Biaya mengurus NIB di OSS adalah...', '["Gratis","Rp 500.000","Tergantung kecamatan"]', 0, 1
  FROM akademi_modul m WHERE m.slug = 'mengurus-nib-oss'
   AND NOT EXISTS (SELECT 1 FROM akademi_soal s WHERE s.modul_id = m.id);

DROP PROCEDURE IF EXISTS _kolom29;
