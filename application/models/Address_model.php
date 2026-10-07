<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Address_model - buku alamat pembeli.
 * Simpan di: application/models/Address_model.php
 *
 * Alamat di sini DISALIN ke pesanan saat checkout, bukan dirujuk. Kalau
 * pembeli mengubah atau menghapus alamatnya nanti, pesanan lama harus tetap
 * menunjukkan ke mana barang dulu dikirim.
 */
class Address_model extends CI_Model {

    const MAKS = 20;

    /** Semua alamat milik satu pengguna; yang utama di urutan pertama. */
    public function daftar($user_id)
    {
        return $this->db
            ->select('a.*, d.name AS district_name, r.name AS regency_name, p.name AS province_name', FALSE)
            ->from('user_addresses a')
            ->join('districts d', 'd.id = a.district_id', 'left')
            ->join('regencies r', 'r.id = a.regency_id', 'left')
            ->join('provinces p', 'p.id = a.province_id', 'left')
            ->where('a.user_id', (int) $user_id)
            ->order_by('a.is_primary', 'DESC')
            ->order_by('a.id', 'DESC')
            ->get()->result_array();
    }

    /** Satu alamat, dipastikan milik pengguna yang meminta. */
    public function milik($id, $user_id)
    {
        return $this->db
            ->select('a.*, d.name AS district_name, r.name AS regency_name, p.name AS province_name', FALSE)
            ->from('user_addresses a')
            ->join('districts d', 'd.id = a.district_id', 'left')
            ->join('regencies r', 'r.id = a.regency_id', 'left')
            ->join('provinces p', 'p.id = a.province_id', 'left')
            ->where('a.id', (int) $id)
            ->where('a.user_id', (int) $user_id)
            ->get()->row_array();
    }

    public function utama($user_id)
    {
        $a = $this->db->where('user_id', (int) $user_id)->where('is_primary', 1)
                      ->get('user_addresses')->row_array();

        // Belum ada yang ditandai utama - pakai yang terbaru, supaya checkout
        // tetap terisi sendiri.
        if ( ! $a) {
            $a = $this->db->where('user_id', (int) $user_id)
                          ->order_by('id', 'DESC')->limit(1)
                          ->get('user_addresses')->row_array();
        }

        return $a ? $this->milik($a['id'], $user_id) : NULL;
    }

    public function jumlah($user_id)
    {
        return (int) $this->db->where('user_id', (int) $user_id)
                              ->count_all_results('user_addresses');
    }

    /**
     * Simpan alamat baru atau ubah yang ada.
     *
     * @return array ['ok' => bool, 'pesan' => string, 'id' => int]
     */
    public function simpan(array $data, $user_id, $id = NULL)
    {
        if ( ! $id && $this->jumlah($user_id) >= self::MAKS) {
            return array('ok' => FALSE, 'pesan' => 'Maksimal ' . self::MAKS . ' alamat tersimpan.');
        }

        $baris = array(
            'label'           => mb_substr(trim($data['label']) ?: 'Alamat', 0, 40),
            'recipient_name'  => mb_substr(trim($data['recipient_name']), 0, 100),
            'recipient_phone' => mb_substr(trim($data['recipient_phone']), 0, 25),
            'address'         => mb_substr(trim($data['address']), 0, 500),
            'landmark'        => trim((string) ($data['landmark'] ?? '')) ?: NULL,
            'postcode'        => trim((string) ($data['postcode'] ?? '')) ?: NULL,
            'province_id'     => (int) ($data['province_id'] ?? 0) ?: NULL,
            'regency_id'      => (int) ($data['regency_id'] ?? 0) ?: NULL,
            'district_id'     => (int) ($data['district_id'] ?? 0) ?: NULL,
            'latitude'        => is_numeric($data['latitude'] ?? NULL) ? $data['latitude'] : NULL,
            'longitude'       => is_numeric($data['longitude'] ?? NULL) ? $data['longitude'] : NULL,
        );

        if ($id) {
            // where user_id ikut disertakan: tanpa itu, mengganti angka di
            // alamat halaman bisa mengubah alamat milik orang lain.
            $this->db->where('id', (int) $id)->where('user_id', (int) $user_id)
                     ->update('user_addresses', $baris);

            if ($this->db->affected_rows() < 0) {
                return array('ok' => FALSE, 'pesan' => 'Alamat tidak ditemukan.');
            }
        } else {
            $baris['user_id'] = (int) $user_id;

            // Alamat pertama otomatis jadi utama - tanpa ini checkout tidak
            // punya pilihan bawaan sampai pembeli menandainya sendiri.
            $baris['is_primary'] = $this->jumlah($user_id) === 0 ? 1 : 0;

            $this->db->insert('user_addresses', $baris);
            $id = (int) $this->db->insert_id();
        }

        if ( ! empty($data['jadikan_utama'])) {
            $this->jadikan_utama($id, $user_id);
        }

        return array('ok' => TRUE, 'pesan' => 'Alamat disimpan.', 'id' => (int) $id);
    }

    public function jadikan_utama($id, $user_id)
    {
        $a = $this->db->where('id', (int) $id)->where('user_id', (int) $user_id)
                      ->get('user_addresses')->row_array();
        if ( ! $a) {
            return FALSE;
        }

        $this->db->where('user_id', (int) $user_id)->update('user_addresses', array('is_primary' => 0));
        $this->db->where('id', (int) $id)->update('user_addresses', array('is_primary' => 1));

        return TRUE;
    }

    public function hapus($id, $user_id)
    {
        $a = $this->db->where('id', (int) $id)->where('user_id', (int) $user_id)
                      ->get('user_addresses')->row_array();
        if ( ! $a) {
            return FALSE;
        }

        $this->db->where('id', (int) $id)->where('user_id', (int) $user_id)
                 ->delete('user_addresses');

        /* Kalau yang dihapus adalah alamat utama, yang lain dinaikkan.
           Dibiarkan kosong, checkout kehilangan pilihan bawaannya dan
           pembeli harus memilih manual tanpa tahu kenapa. */
        if ($a['is_primary']) {
            $lain = $this->db->where('user_id', (int) $user_id)
                             ->order_by('id', 'DESC')->limit(1)
                             ->get('user_addresses')->row_array();

            if ($lain) {
                $this->db->where('id', $lain['id'])->update('user_addresses', array('is_primary' => 1));
            }
        }

        return TRUE;
    }

    /** Satu baris ringkas untuk ditampilkan di daftar pilihan. */
    public function ringkas(array $a)
    {
        $bagian = array_filter(array(
            $a['address'],
            $a['district_name'] ?? NULL,
            $a['regency_name'] ?? NULL,
            $a['postcode'],
        ));

        return implode(', ', $bagian);
    }
}
