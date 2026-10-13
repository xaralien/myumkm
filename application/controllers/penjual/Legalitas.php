<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Legalitas - unggah dokumen usaha untuk diverifikasi admin.
 * Alamat: seller/legalitas
 *
 *   GET  seller/legalitas                  daftar dokumen + panduan
 *   POST seller/legalitas/simpan           unggah / ganti satu jenis dokumen
 *   GET  seller/legalitas/berkas/{id}      lihat berkas sendiri
 *
 * Dokumen terverifikasi memberi lencana di halaman toko & produk, dan
 * menambah skor Naik Kelas.
 */
class Legalitas extends Seller_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('dokumen_lib');
    }

    public function index()
    {
        $dok = array();
        foreach ($this->db->where('store_id', (int) $this->store['id'])->get('store_documents')->result_array() as $d) {
            $dok[$d['jenis']] = $d;
        }
        $this->render('penjual/v_legalitas', array('dok' => $dok, 'jenis' => Dokumen_lib::$jenis));
    }

    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $jenis = (string) $this->input->post('jenis');
        if ( ! isset(Dokumen_lib::$jenis[$jenis])) {
            show_404();
        }
        $nomor = mb_substr(trim((string) $this->input->post('nomor', TRUE)), 0, 80);
        $lama  = $this->db->where('store_id', (int) $this->store['id'])->where('jenis', $jenis)->get('store_documents')->row_array();

        $pesan = '';
        $berkas = $this->dokumen_lib->unggah('berkas', $pesan);
        if ($berkas === FALSE) {
            $this->session->set_flashdata('error', $pesan);
            return redirect('seller/legalitas');
        }
        if ( ! $berkas && ! $lama) {
            $this->session->set_flashdata('error', 'Unggah foto atau PDF dokumennya.');
            return redirect('seller/legalitas');
        }
        if ($nomor === '' && $jenis !== 'lainnya') {
            $this->session->set_flashdata('error', 'Tulis nomor dokumennya.');
            return redirect('seller/legalitas');
        }

        $tgl = (string) $this->input->post('berlaku_sampai');
        $data = array(
            'nomor'          => $nomor ?: NULL,
            'berlaku_sampai' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl) ? $tgl : NULL,
            // Setiap perubahan diperiksa ulang - nomor/berkas baru belum tentu benar.
            'status'         => 'menunggu',
            'catatan_admin'  => NULL,
            'diperiksa_oleh' => NULL,
            'diperiksa_at'   => NULL,
        );
        if ($berkas) {
            $data['berkas'] = $berkas;
        }

        if ($lama) {
            $this->db->where('id', (int) $lama['id'])->update('store_documents', $data);
            if ($berkas && $lama['berkas']) {
                $this->dokumen_lib->hapus($lama['berkas']);
            }
        } else {
            $this->db->insert('store_documents', $data + array('store_id' => (int) $this->store['id'], 'jenis' => $jenis));
        }

        $this->session->set_flashdata('sukses', Dokumen_lib::$jenis[$jenis][0] . ' terkirim. Admin akan memeriksanya dalam 1-3 hari kerja.');
        return redirect('seller/legalitas');
    }

    public function berkas($id = NULL)
    {
        $d = $this->db->where('id', (int) $id)->where('store_id', (int) $this->store['id'])->get('store_documents')->row_array();
        if ( ! $d) {
            show_404();
        }
        return $this->dokumen_lib->kirim($d['berkas']);
    }
}
