<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Favorit - wishlist & toko yang diikuti.
 * Simpan di: application/controllers/Favorit.php
 *
 *   GET  favorit               produk favorit + toko diikuti
 *   POST favorit/tukar/{id}    simpan / hapus produk dari favorit
 *   POST favorit/ikuti/{id}    ikuti / berhenti mengikuti toko
 *
 * Kedua POST melayani formulir biasa (dialihkan kembali ke halaman asal)
 * maupun fetch() dari JavaScript (dijawab JSON) - tombolnya tetap bekerja
 * walau JavaScript gagal dimuat.
 */
class Favorit extends Member_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('pembeli_model');
    }

    public function index()
    {
        $this->load->library('location_lib');
        $this->load->view('index', array(
            'pages'   => 'akun/v_favorit',
            'favorit' => $this->pembeli_model->daftar_favorit($this->me['id']),
            'toko'    => $this->pembeli_model->toko_diikuti($this->me['id']),
            'baru'    => $this->pembeli_model->baru_dari_langganan($this->me['id'], 8),
            'tab'     => $this->input->get('tab') === 'toko' ? 'toko' : 'produk',
        ));
    }

    public function tukar($id = NULL)
    {
        $this->wajib_post();
        $p = $this->db->select('id, name')->where('id', (int) $id)->get('products')->row_array();
        if ( ! $p) {
            show_404();
        }
        $aktif = $this->pembeli_model->tukar_favorit($this->me['id'], $p['id']);

        return $this->jawab($aktif,
            $aktif ? $p['name'] . ' disimpan ke Favorit.' : $p['name'] . ' dihapus dari Favorit.',
            array('jumlah' => $this->pembeli_model->jumlah_favorit($p['id'])));
    }

    public function ikuti($id = NULL)
    {
        $this->wajib_post();
        $s = $this->db->select('id, name, user_id')->where('id', (int) $id)->get('stores')->row_array();
        if ( ! $s) {
            show_404();
        }
        if ((int) $s['user_id'] === (int) $this->me['id']) {
            return $this->jawab(FALSE, 'Ini tokomu sendiri.', array(), 422);
        }
        $aktif = $this->pembeli_model->tukar_ikuti($this->me['id'], $s['id']);

        return $this->jawab($aktif,
            $aktif ? 'Kamu mengikuti ' . $s['name'] . '. Produk barunya muncul di halaman Favorit.'
                   : 'Berhenti mengikuti ' . $s['name'] . '.',
            array('jumlah' => $this->pembeli_model->jumlah_pengikut($s['id'])));
    }

    /* ------------------------------------------------------------------ */

    protected function wajib_post()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    }

    protected function jawab($aktif, $pesan, array $tambahan = array(), $kode = 200)
    {
        if ($this->input->is_ajax_request()) {
            return $this->output->set_status_header($kode)
                ->set_content_type('application/json')
                ->set_output(json_encode(array_merge(array(
                    'ok'        => $kode < 400,
                    'aktif'     => $aktif,
                    'pesan'     => $pesan,
                    'csrf_name' => $this->security->get_csrf_token_name(),
                    'csrf_hash' => $this->security->get_csrf_hash(),
                ), $tambahan)));
        }

        $this->session->set_flashdata($kode < 400 ? 'sukses' : 'error', $pesan);
        // Kembali ke halaman asal, tapi hanya kalau masih di situs ini.
        $balik = $this->auth_lib->tujuan_aman($this->input->post('balik', TRUE));
        return redirect($balik ?: 'favorit');
    }
}
