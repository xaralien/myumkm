<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Sengketa - menengahi pembeli & penjual.
 *
 *   GET  admin/sengketa?status=aktif|selesai
 *   GET  admin/sengketa/lihat/{id}
 *   POST admin/sengketa/balas/{id}
 *   POST admin/sengketa/putus/{id}    status, keputusan, jumlah
 */
class Sengketa extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('sengketa_model', 'chat_model', 'order_model'));
    }

    public function index()
    {
        $aktif = $this->input->get('status') !== 'selesai';
        $this->db->select('g.*, o.order_number, o.total, s.name AS store_name', FALSE)->from('sengketa g')
            ->join('orders o', 'o.id = g.order_id')->join('stores s', 's.id = g.store_id', 'left');
        $aktif ? $this->db->where_in('g.status', array('terbuka', 'ditinjau')) : $this->db->where_not_in('g.status', array('terbuka', 'ditinjau'));
        $this->render('admin/v_sengketa', array(
            'aktif'  => $aktif,
            'daftar' => $this->db->order_by('g.created_at', 'ASC')->limit(200)->get()->result_array(),
            'g'      => NULL,
        ));
    }

    public function lihat($id = NULL)
    {
        $g = $this->sengketa_model->ambil($id);
        if ( ! $g) {
            show_404();
        }
        $this->render('admin/v_sengketa', array(
            'g'      => $g,
            'pesan'  => $this->sengketa_model->pesan($g['id']),
            'chat'   => $this->chat_model->pesan($g['order_id']),
            'items'  => $this->order_model->items($g['order_id']),
            'refund' => $this->db->where('order_id', (int) $g['order_id'])->order_by('id', 'DESC')->get('refunds')->result_array(),
            'logs'   => $this->order_model->logs($g['order_id']),
        ));
    }

    public function balas($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->sengketa_model->kirim((int) $id, 'admin', $this->input->post('isi', TRUE));
        $this->db->where('id', (int) $id)->where('status', 'terbuka')->update('sengketa', array('status' => 'ditinjau'));
        return redirect('admin/sengketa/lihat/' . (int) $id);
    }

    public function putus($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $h = $this->sengketa_model->putus((int) $id, $this->input->post('status'), $this->input->post('keputusan', TRUE),
            (int) preg_replace('/[^0-9]/', '', (string) $this->input->post('jumlah')));
        $this->session->set_flashdata($h['ok'] ? 'sukses' : 'error', $h['pesan']);
        return redirect('admin/sengketa/lihat/' . (int) $id);
    }
}
