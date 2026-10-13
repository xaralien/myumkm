<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Pengaturan - pengaturan sistem, notifikasi keluar, dan log audit.
 * Simpan di: application/controllers/admin/Pengaturan.php
 *
 *   GET  admin/pengaturan             formulir pengaturan
 *   POST admin/pengaturan/simpan      simpan
 *   POST admin/pengaturan/tes_wa      kirim WA uji ke nomor admin
 *   GET  admin/pengaturan/notifikasi  notifikasi WA/email yang keluar
 *   GET  admin/pengaturan/audit       log aktivitas admin
 */
class Pengaturan extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('pengaturan_lib');
    }

    public function index()
    {
        $this->render('admin/v_pengaturan', array(
            'daftar' => $this->pengaturan_lib->daftar(),
            'nilai'  => $this->pengaturan_lib->semua(),
            'p'      => $this->pengaturan_lib,
            'tab'    => 'pengaturan',
        ));
    }

    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $ubah = $this->pengaturan_lib->simpan((array) $this->input->post(NULL, TRUE));

        $this->session->set_flashdata('sukses', $ubah
            ? 'Pengaturan tersimpan (' . count($ubah) . ' diubah).'
            : 'Tidak ada yang berubah.');
        return redirect('admin/pengaturan');
    }

    public function tes_wa()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->load->library('notifikasi_lib');

        $nomor = $this->input->post('nomor', TRUE) ?: $this->pengaturan_lib->get('wa_admin');
        if ( ! $nomor) {
            $this->session->set_flashdata('error', 'Isi nomor tujuan uji, atau isi WhatsApp admin dulu.');
            return redirect('admin/pengaturan');
        }

        $ok = $this->notifikasi_lib->wa($nomor,
            'Uji notifikasi dari ' . $this->pengaturan_lib->get('nama_brand') . '. Kalau pesan ini sampai, gerbang WA sudah benar.',
            'uji');

        $this->session->set_flashdata($ok ? 'sukses' : 'error', $ok
            ? 'Pesan uji terkirim ke ' . $nomor . '.'
            : 'Pesan uji tidak terkirim. Lihat alasannya di tab Notifikasi keluar.');
        return redirect('admin/pengaturan');
    }

    public function notifikasi()
    {
        $status = $this->input->get('status', TRUE);
        if (in_array($status, array('terkirim', 'gagal', 'dilewati'), TRUE)) {
            $this->db->where('status', $status);
        }
        $this->render('admin/v_pengaturan_log', array(
            'tab'    => 'notifikasi',
            'status' => $status,
            'baris'  => $this->db->order_by('id', 'DESC')->limit(200)->get('notifikasi_keluar')->result_array(),
        ));
    }

    public function audit()
    {
        $cari = trim((string) $this->input->get('q', TRUE));
        if ($cari !== '') {
            $this->db->group_start()->like('aksi', $cari)->or_like('nama', $cari)->or_like('detail', $cari)->group_end();
        }
        $this->render('admin/v_pengaturan_log', array(
            'tab'   => 'audit',
            'cari'  => $cari,
            'baris' => $this->db->order_by('id', 'DESC')->limit(300)->get('audit_logs')->result_array(),
        ));
    }
}
