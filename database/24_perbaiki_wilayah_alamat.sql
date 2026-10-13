-- =============================================================================
--  24_perbaiki_wilayah_alamat.sql
--
--  Isi ulang province_id dan regency_id pada alamat tersimpan yang kosong.
--
--  Penyebabnya: formulir alamat mengirim prov_awal dan reg_awal (nama yang
--  dipakai region-select.js sebagai pembantu dropdown bertingkat), tapi
--  model membacanya sebagai province_id dan regency_id - jadi keduanya
--  selalu tersimpan NULL. Akibatnya wilayah tidak pernah muncul lagi saat
--  alamat dibuka ulang untuk diubah.
--
--  Kodenya sudah diperbaiki (diturunkan dari kecamatan). Berkas ini
--  membereskan baris yang terlanjur tersimpan sebelum perbaikan itu.
--
--  Aman dijalankan berulang. CADANGKAN DULU.
-- =============================================================================

SET NAMES utf8mb4;

UPDATE user_addresses a
  JOIN districts d  ON d.id = a.district_id
  JOIN regencies r  ON r.id = d.regency_id
  JOIN provinces p  ON p.id = r.province_id
SET a.regency_id  = r.id,
    a.province_id = p.id
WHERE a.district_id IS NOT NULL
  AND (a.province_id IS NULL OR a.regency_id IS NULL);
