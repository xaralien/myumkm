<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Peta - peta sebaran UMKM + tabel per kecamatan.
 * Untuk pemda/mitra: wilayah mana yang UMKM-nya banyak tapi belum menjual,
 * sehingga pembinaan bisa diarahkan ke sana.
 */
class Peta extends Admin_Controller {

    public function index()
    {
        $this->load->model('analitik_model');
        $this->render('admin/v_peta', array(
            'titik'   => $this->analitik_model->titik_toko(),
            'sebaran' => $this->analitik_model->sebaran(),
        ));
    }
}
