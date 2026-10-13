<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Naik_kelas - skor & langkah naik kelas.  Alamat: seller/naik_kelas
 */
class Naik_kelas extends Seller_Controller
{
    public function index()
    {
        $this->load->library('naik_kelas_lib');

        $pendamping = NULL;
        $catatan = array();
        $toko = $this->db->where('id', (int) $this->store['id'])->get('stores')->row_array();
        if ( ! empty($toko['pendamping_id'])) {
            $pendamping = $this->db->select('id, name, phone, email')->where('id', (int) $toko['pendamping_id'])->get('users')->row_array();
            $catatan = $this->db->where('store_id', (int) $this->store['id'])
                ->order_by('tanggal', 'DESC')->limit(5)->get('pendampingan_catatan')->result_array();
        }

        $this->render('penjual/v_naik_kelas', array(
            'n'          => $this->naik_kelas_lib->skor($this->store['id']),
            'pendamping' => $pendamping,
            'catatan'    => $catatan,
        ));
    }
}
