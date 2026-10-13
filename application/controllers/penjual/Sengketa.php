<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Sengketa - sengketa yang melibatkan toko ini.  Alamat: seller/sengketa
 *
 *   GET  seller/sengketa
 *   GET  seller/sengketa/lihat/{id}
 *   POST seller/sengketa/balas/{id}
 */
class Sengketa extends Seller_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('sengketa_model');
    }

    public function index()
    {
        $this->render('penjual/v_sengketa', array(
            'daftar' => $this->db->select('g.*, o.order_number', FALSE)->from('sengketa g')->join('orders o', 'o.id = g.order_id')
                ->where('g.store_id', (int) $this->store['id'])->order_by('g.id', 'DESC')->get()->result_array(),
            'g' => NULL,
        ));
    }

    public function lihat($id = NULL)
    {
        $g = $this->milik($id);
        $this->render('penjual/v_sengketa', array(
            'daftar' => array(),
            'g'      => $g,
            'pesan'  => $this->sengketa_model->pesan($g['id']),
        ));
    }

    public function balas($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $g = $this->milik($id);
        if (in_array($g['status'], array('terbuka', 'ditinjau'), TRUE)) {
            $this->sengketa_model->kirim($g['id'], 'penjual', $this->input->post('isi', TRUE));
            $this->session->set_flashdata('sukses', 'Tanggapan terkirim. Admin membaca kedua sisi sebelum memutuskan.');
        }
        return redirect('seller/sengketa/lihat/' . (int) $g['id']);
    }

    protected function milik($id)
    {
        $g = $this->sengketa_model->ambil($id);
        if ( ! $g || (int) $g['store_id'] !== (int) $this->store['id']) {
            show_404();
        }
        return $g;
    }
}
