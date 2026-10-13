<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Promo - halaman kampanye yang dibuat admin.  GET promo/{slug}
 */
class Promo extends CI_Controller
{
    public function index($slug = NULL)
    {
        $p = $slug ? $this->db->where('slug', $slug)->where('is_active', 1)->get('halaman_promo')->row_array() : NULL;
        if ( ! $p) {
            show_404();
        }
        $this->load->library(array('location_lib', 'voucher_lib'));
        $this->load->model('product_model');

        $f = array('sort' => 'terlaris');
        if ($p['kata_kunci']) {
            $f['q'] = $p['kata_kunci'];
        }
        if ($p['kategori_id']) {
            $k = $this->db->select('slug')->where('id', (int) $p['kategori_id'])->get('categories')->row_array();
            if ($k) {
                $f['category'] = $k['slug'];
            }
        }
        $lokasi = $this->location_lib->get();
        if ($lokasi) {
            $f += array('district_id' => (int) $lokasi['district_id'], 'regency_id' => (int) $lokasi['regency_id'], 'province_id' => (int) $lokasi['province_id']);
        }

        $v = $p['voucher'] ? $this->voucher_lib->cari($p['voucher']) : NULL;
        $this->load->view('index', array(
            'pages'   => 'v_promo',
            'promo'   => $p,
            'produk'  => $this->product_model->search($f, 40, 0),
            'voucher' => $v && $v['is_active'] ? $v : NULL,
        ));
    }
}
