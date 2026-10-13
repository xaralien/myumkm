<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Verifikasi - periksa dokumen legalitas toko.
 *
 *   GET  admin/verifikasi?status=menunggu
 *   GET  admin/verifikasi/berkas/{id}
 *   POST admin/verifikasi/putus/{id}     setuju=1|0, catatan
 */
class Verifikasi extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('dokumen_lib');
    }

    public function index()
    {
        $status = in_array($this->input->get('status'), array('menunggu', 'terverifikasi', 'ditolak'), TRUE)
            ? $this->input->get('status') : 'menunggu';
        $this->render('admin/v_verifikasi', array(
            'status' => $status,
            'jenis'  => Dokumen_lib::$jenis,
            'jumlah' => array_column($this->db->select('status, COUNT(*) AS n', FALSE)->group_by('status')
                                     ->get('store_documents')->result_array(), 'n', 'status'),
            'dok'    => $this->db->select('d.*, s.name AS store_name, s.slug AS store_slug, u.name AS pemilik', FALSE)
                ->from('store_documents d')->join('stores s', 's.id = d.store_id')->join('users u', 'u.id = s.user_id', 'left')
                ->where('d.status', $status)->order_by('d.updated_at IS NULL', 'DESC', FALSE)->order_by('d.created_at', 'ASC')
                ->limit(200)->get()->result_array(),
        ));
    }

    public function berkas($id = NULL)
    {
        $d = $this->db->where('id', (int) $id)->get('store_documents')->row_array();
        if ( ! $d) {
            show_404();
        }
        return $this->dokumen_lib->kirim($d['berkas']);
    }

    public function putus($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $d = $this->db->select('d.*, s.name AS store_name, s.phone AS store_phone', FALSE)
            ->from('store_documents d')->join('stores s', 's.id = d.store_id')
            ->where('d.id', (int) $id)->get()->row_array();
        if ( ! $d) {
            show_404();
        }
        $setuju  = $this->input->post('setuju') === '1';
        $catatan = mb_substr(trim((string) $this->input->post('catatan', TRUE)), 0, 255);
        if ( ! $setuju && $catatan === '') {
            $this->session->set_flashdata('error', 'Tulis alasan penolakan supaya penjual tahu apa yang harus diperbaiki.');
            return redirect('admin/verifikasi');
        }

        $this->db->where('id', (int) $d['id'])->update('store_documents', array(
            'status'         => $setuju ? 'terverifikasi' : 'ditolak',
            'catatan_admin'  => $catatan ?: NULL,
            'diperiksa_oleh' => (int) $this->me['id'],
            'diperiksa_at'   => date('Y-m-d H:i:s'),
        ));

        $nama = Dokumen_lib::$jenis[$d['jenis']][0];
        $this->load->library('notifikasi_lib');
        $this->notifikasi_lib->wa($d['store_phone'], $setuju
            ? $nama . ' ' . $d['store_name'] . ' sudah terverifikasi. Lencananya kini tampil di tokomu.'
            : $nama . ' ' . $d['store_name'] . ' belum bisa diverifikasi: ' . $catatan . '. Perbaiki di: ' . site_url('seller/legalitas'),
            'verifikasi_dokumen');

        $this->session->set_flashdata('sukses', $nama . ' ' . $d['store_name'] . ($setuju ? ' diverifikasi.' : ' ditolak.'));
        return redirect('admin/verifikasi');
    }
}
