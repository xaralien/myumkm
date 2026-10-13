<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Libur - mode libur toko.  Alamat: seller/libur
 *
 *   GET  seller/libur           formulir
 *   POST seller/libur/simpan    atur libur
 *   POST seller/libur/buka      buka lagi sekarang
 */
class Libur extends Seller_Controller
{
    public function index()
    {
        $this->load->library('rincian_lib');
        $toko = $this->db->where('id', (int) $this->store['id'])->get('stores')->row_array();
        $this->render('penjual/v_libur', array(
            'toko'  => $toko,
            'libur' => $this->rincian_lib->libur($toko),
        ));
    }

    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $mulai  = (string) $this->input->post('libur_mulai', TRUE);
        $sampai = (string) $this->input->post('libur_sampai', TRUE);
        $valid  = function ($t) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', $t) && strtotime($t); };

        if ( ! $valid($mulai)) {
            $mulai = date('Y-m-d');
        }
        if ( ! $valid($sampai) || $sampai < $mulai || $sampai < date('Y-m-d')) {
            $this->session->set_flashdata('error', 'Tanggal buka kembali harus diisi dan tidak boleh sebelum tanggal mulai libur.');
            return redirect('seller/libur');
        }
        if ((strtotime($sampai) - strtotime($mulai)) / 86400 > 60) {
            $this->session->set_flashdata('error', 'Libur paling lama 60 hari. Kalau lebih lama, hubungi admin untuk menonaktifkan toko.');
            return redirect('seller/libur');
        }

        $this->db->where('id', (int) $this->store['id'])->update('stores', array(
            'libur_mulai'  => $mulai,
            'libur_sampai' => $sampai,
            'libur_pesan'  => mb_substr(trim((string) $this->input->post('libur_pesan', TRUE)), 0, 160) ?: NULL,
        ));

        $this->session->set_flashdata('sukses', 'Toko libur ' . tgl_id($mulai) . ' sampai ' . tgl_id($sampai)
            . '. Toko buka lagi otomatis setelahnya.');
        return redirect('seller/libur');
    }

    public function buka()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->db->where('id', (int) $this->store['id'])->update('stores', array(
            'libur_mulai' => NULL, 'libur_sampai' => NULL, 'libur_pesan' => NULL,
        ));
        $this->session->set_flashdata('sukses', 'Toko buka lagi. Pembeli sudah bisa memesan.');
        return redirect('seller/libur');
    }
}
