<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Toko_model - aturan nama toko.
 * Simpan di: application/models/Toko_model.php
 *
 * Dipakai di dua tempat yang sama-sama bisa mengubah nama toko: buka toko
 * baru (Akun) dan ubah profil toko (Seller). Kalau aturannya ditulis dua
 * kali, cepat atau lambat keduanya berbeda.
 */
class Toko_model extends CI_Model {

    /**
     * Rapikan nama: buang spasi di ujung, satukan spasi ganda.
     * "  Toko   Bunga " -> "Toko Bunga"
     */
    public function rapikan($nama)
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $nama));
    }

    /**
     * Apakah nama ini sudah dipakai toko lain?
     *
     * Tidak peka huruf besar-kecil: kolom memakai collation *_ci, jadi
     * "Toko Bunga" dan "TOKO BUNGA" dianggap sama oleh perbandingan '='.
     *
     * @param int|NULL $kecuali_id toko sendiri, saat mengubah profil
     */
    public function nama_dipakai($nama, $kecuali_id = NULL)
    {
        $this->db->where('name', $this->rapikan($nama));
        if ($kecuali_id) {
            $this->db->where('id !=', (int) $kecuali_id);
        }
        return $this->db->count_all_results('stores') > 0;
    }

    /** Slug unik dari nama toko. */
    public function slug_unik($nama, $abaikan_id = NULL)
    {
        $this->load->helper(array('url', 'text'));
        $dasar = url_title(convert_accented_characters($this->rapikan($nama)), '-', TRUE) ?: 'toko';
        $slug  = $dasar;
        $n     = 2;

        while (TRUE) {
            $this->db->where('slug', $slug);
            if ($abaikan_id) {
                $this->db->where('id !=', (int) $abaikan_id);
            }
            if ( ! $this->db->count_all_results('stores')) {
                return $slug;
            }
            $slug = $dasar . '-' . $n++;
        }
    }

    /**
     * Buat toko baru untuk sebuah akun. Status MENUNGGU persetujuan admin.
     *
     * @return int|FALSE id toko, FALSE kalau nama baru saja diambil orang lain
     */
    public function buat($user_id, array $data)
    {
        $nama = $this->rapikan($data['name']);

        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        $ok = $this->db->insert('stores', array_merge($data, array(
            'user_id'   => (int) $user_id,
            'name'      => $nama,
            'slug'      => $this->slug_unik($nama),

            /* Belum aktif sampai admin menyetujui. Uang pembeli mengalir
               lewat akun Duitku platform - toko palsu yang langsung bisa
               berjualan adalah risiko nyata. */
            'is_active' => 0,
        )));

        $this->db->db_debug = $debug;

        /* Gagal di sini hampir pasti karena indeks unik uq_stores_name atau
           uq_stores_user: orang lain mengambil nama yang sama di detik yang
           sama, atau tombol ditekan dua kali. */
        return $ok ? (int) $this->db->insert_id() : FALSE;
    }
}
