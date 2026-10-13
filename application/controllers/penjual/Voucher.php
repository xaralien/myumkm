<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Voucher - voucher toko.  Alamat: seller/voucher (lihat routes.php)
 *
 *   GET  seller/voucher             daftar
 *   GET  seller/voucher/form[/id]   formulir
 *   POST seller/voucher/simpan      simpan
 *   POST seller/voucher/tukar/{id}  aktif / nonaktif
 */
class Voucher extends Seller_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('voucher_model');
        $this->load->library('voucher_lib');
    }

    public function index()
    {
        $this->render('parts/voucher_daftar', array(
            'judul'   => 'Voucher toko',
            'sub'     => 'Kode potongan khusus pembeli tokomu. Potongannya mengurangi pendapatanmu - pakai untuk pelanggan setia atau promo pembukaan.',
            'basis'   => 'seller/voucher',
            'voucher' => $this->voucher_model->daftar($this->store['id']),
        ));
    }

    public function form($id = NULL)
    {
        $v = $id ? $this->voucher_model->ambil($id, $this->store['id']) : NULL;
        if ($id && ! $v) {
            show_404();
        }
        $this->render('parts/voucher_form', array('v' => $v, 'basis' => 'seller/voucher', 'platform' => FALSE));
    }

    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $id = (int) $this->input->post('id');
        if ($id && ! $this->voucher_model->ambil($id, $this->store['id'])) {
            show_404();
        }
        $h = $this->voucher_model->simpan($id ?: NULL, (int) $this->store['id'], (array) $this->input->post(NULL, TRUE));
        $this->session->set_flashdata($h['ok'] ? 'sukses' : 'error', $h['pesan']);
        return redirect($h['ok'] ? 'seller/voucher' : 'seller/voucher/form' . ($id ? '/' . $id : ''));
    }

    public function tukar($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $hasil = $this->voucher_model->tukar($id, $this->store['id']);
        if ($hasil === NULL) {
            show_404();
        }
        $this->session->set_flashdata('sukses', $hasil ? 'Voucher diaktifkan.' : 'Voucher dinonaktifkan.');
        return redirect('seller/voucher');
    }
}
