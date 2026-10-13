<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Voucher - voucher PLATFORM (ditanggung marketplace).
 *
 *   GET  admin/voucher             daftar
 *   GET  admin/voucher/form[/id]   formulir
 *   POST admin/voucher/simpan
 *   POST admin/voucher/tukar/{id}
 */
class Voucher extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('voucher_model');
        $this->load->library('voucher_lib');
    }

    public function index()
    {
        $this->load->library('pengaturan_lib');
        $this->render('parts/voucher_daftar', array(
            'judul'   => 'Voucher platform',
            'sub'     => 'Berlaku di semua toko dan ditanggung marketplace - toko tetap menerima harga penuh. '
                       . 'Subsidi ongkir otomatis diatur di Pengaturan > Promo (sekarang: '
                       . ((int) $this->pengaturan_lib->get('subsidi_ongkir_maks') ? 's.d. ' . rupiah($this->pengaturan_lib->get('subsidi_ongkir_maks')) . ' per pesanan' : 'tidak aktif') . ').',
            'basis'   => 'admin/voucher',
            'voucher' => $this->voucher_model->daftar(NULL),
        ));
    }

    public function form($id = NULL)
    {
        $v = $id ? $this->voucher_model->ambil($id, NULL) : NULL;
        if ($id && ! $v) {
            show_404();
        }
        $this->render('parts/voucher_form', array('v' => $v, 'basis' => 'admin/voucher', 'platform' => TRUE));
    }

    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        if ($id && ! $this->voucher_model->ambil($id, NULL)) {
            show_404();
        }
        $h = $this->voucher_model->simpan($id ?: NULL, NULL, (array) $this->input->post(NULL, TRUE));
        $this->session->set_flashdata($h['ok'] ? 'sukses' : 'error', $h['pesan']);
        return redirect($h['ok'] ? 'admin/voucher' : 'admin/voucher/form' . ($id ? '/' . $id : ''));
    }

    public function tukar($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $hasil = $this->voucher_model->tukar($id, NULL);
        if ($hasil === NULL) {
            show_404();
        }
        $this->session->set_flashdata('sukses', $hasil ? 'Voucher diaktifkan.' : 'Voucher dinonaktifkan.');
        return redirect('admin/voucher');
    }
}
