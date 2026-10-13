<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Preferensi - atur ukuran huruf, hemat kuota, dan bahasa.
 *
 *   GET preferensi/atur?k=huruf&v=besar&balik=produk/...
 *
 * GET, bukan POST: hanya mengubah cookie tampilan milik pengunjung sendiri
 * (sama seperti ?tampilan=mobile yang sudah ada), tidak menyentuh data.
 */
class Preferensi extends CI_Controller
{
    public function atur()
    {
        $this->load->helper(array('cookie', 'url'));
        $this->load->library('auth_lib');

        $k = (string) $this->input->get('k');
        $v = (string) $this->input->get('v');
        $daftar = preferensi_daftar();
        if (isset($daftar[$k]) && array_key_exists($v, $daftar[$k])) {
            set_cookie('pref_' . $k, $v, 365 * 86400);
        }
        $balik = $this->auth_lib->tujuan_aman($this->input->get('balik', TRUE));
        return redirect($balik ?: '');
    }
}
