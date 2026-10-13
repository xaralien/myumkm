<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pendamping - panel pendamping UMKM.
 *
 *   GET  pendamping                 toko binaan + skor Naik Kelas
 *   GET  pendamping/toko/{id}       rincian satu toko + riwayat catatan
 *   POST pendamping/catat/{id}      catatan kunjungan / telepon / kelas
 *   GET  pendamping/berkas/{id}     dokumen legalitas toko binaan
 *
 * Bisa dibuka akun berperan 'pendamping' (hanya toko binaannya) dan admin
 * (semua toko yang punya pendamping).
 */
class Pendamping extends CI_Controller
{
    protected $me;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('auth_lib', 'session', 'naik_kelas_lib'));
        $this->load->helper(array('url', 'form', 'money'));
        $this->me = $this->auth_lib->user();
        if ( ! $this->me) {
            $this->session->set_flashdata('error', 'Silakan masuk dulu.');
            redirect('auth/login?next=pendamping');
        }
        // Sudah masuk tapi bukan pendamping: JANGAN ke halaman masuk lagi -
        // halaman masuk akan mengembalikannya ke sini dan berputar terus.
        if ( ! in_array($this->me['role'], array('pendamping', 'admin'), TRUE)) {
            $this->session->set_flashdata('error', 'Halaman pendamping hanya untuk akun pendamping UMKM.');
            redirect('');
        }
    }

    public function index()
    {
        $toko = $this->binaan()->get()->result_array();
        foreach ($toko as &$t) {
            $t['nk'] = $this->naik_kelas_lib->skor($t['id']);
            $t['akhir'] = $this->db->select('tanggal')->where('store_id', (int) $t['id'])->order_by('tanggal', 'DESC')
                ->limit(1)->get('pendampingan_catatan')->row_array();
        }
        unset($t);
        usort($toko, function ($a, $b) { return $a['nk']['skor'] - $b['nk']['skor']; });
        $this->render('pendamping/v_index', array('toko' => $toko));
    }

    public function toko($id = NULL)
    {
        $t = $this->binaan()->where('s.id', (int) $id)->get()->row_array();
        if ( ! $t) {
            show_404();
        }
        $this->load->model('keuangan_model');
        $this->render('pendamping/v_toko', array(
            't'       => $t,
            'nk'      => $this->naik_kelas_lib->skor($t['id']),
            'jual'    => $this->keuangan_model->penjualan($t['id'], date('Y-m-d', strtotime('-29 days')), date('Y-m-d')),
            'bulan'   => $this->keuangan_model->per_bulan($t['id'], 3),
            'dok'     => $this->db->where('store_id', (int) $t['id'])->get('store_documents')->result_array(),
            'catatan' => $this->db->select('c.*, u.name AS penulis', FALSE)->from('pendampingan_catatan c')
                ->join('users u', 'u.id = c.pendamping_id', 'left')->where('c.store_id', (int) $t['id'])
                ->order_by('c.tanggal', 'DESC')->order_by('c.id', 'DESC')->get()->result_array(),
        ));
    }

    public function catat($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $t = $this->binaan()->where('s.id', (int) $id)->get()->row_array();
        if ( ! $t) {
            show_404();
        }
        $isi = trim((string) $this->input->post('catatan', TRUE));
        if (mb_strlen($isi) < 5) {
            $this->session->set_flashdata('error', 'Tulis catatannya dulu.');
            return redirect('pendamping/toko/' . (int) $t['id']);
        }
        $tgl = (string) $this->input->post('tanggal');
        $this->db->insert('pendampingan_catatan', array(
            'store_id'      => (int) $t['id'],
            'pendamping_id' => (int) $this->me['id'],
            'tanggal'       => preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl) ? $tgl : date('Y-m-d'),
            'jenis'         => in_array($this->input->post('jenis'), array('kunjungan', 'telepon', 'kelas', 'lainnya'), TRUE) ? $this->input->post('jenis') : 'kunjungan',
            'catatan'       => mb_substr($isi, 0, 4000),
            'tindak_lanjut' => mb_substr(trim((string) $this->input->post('tindak_lanjut', TRUE)), 0, 255) ?: NULL,
        ));
        $this->session->set_flashdata('sukses', 'Catatan tersimpan dan bisa dibaca pemilik toko di halaman Naik Kelas.');
        return redirect('pendamping/toko/' . (int) $t['id']);
    }

    public function berkas($id = NULL)
    {
        $d = $this->db->where('id', (int) $id)->get('store_documents')->row_array();
        if ( ! $d || ! $this->binaan()->where('s.id', (int) $d['store_id'])->count_all_results()) {
            show_404();
        }
        $this->load->library('dokumen_lib');
        return $this->dokumen_lib->kirim($d['berkas']);
    }

    /** Query toko binaan, dikunci ke pendamping yang sedang masuk. */
    protected function binaan()
    {
        $this->db->select('s.*, u.name AS pemilik, d.name AS district_name, r.name AS regency_name, p.name AS pendamping_nama', FALSE)
            ->from('stores s')->join('users u', 'u.id = s.user_id', 'left')
            ->join('users p', 'p.id = s.pendamping_id', 'left')
            ->join('districts d', 'd.id = s.district_id', 'left')->join('regencies r', 'r.id = s.regency_id', 'left');
        if ($this->me['role'] === 'admin') {
            $this->db->where('s.pendamping_id IS NOT NULL', NULL, FALSE);
        } else {
            $this->db->where('s.pendamping_id', (int) $this->me['id']);
        }
        return $this->db;
    }

    protected function render($view, $data = array())
    {
        $data['me'] = $this->me;
        $this->load->view('admin/v_admin_header', $data);
        $this->load->view($view, $data);
        $this->load->view('admin/v_admin_footer');
    }
}
