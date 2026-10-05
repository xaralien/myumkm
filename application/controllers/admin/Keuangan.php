<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Keuangan - pengembalian dana & pencairan ke toko.
 * Simpan di: application/controllers/admin/Keuangan.php
 *
 *   GET  admin/keuangan                daftar pencairan + toko yang siap
 *   POST admin/keuangan/cairkan/{toko} buat pencairan
 *   POST admin/keuangan/selesai/{id}   tandai sudah ditransfer
 *   POST admin/keuangan/gagal/{id}     transfer gagal, pesanan dilepas lagi
 *   GET  admin/keuangan/refund         daftar refund
 *   POST admin/keuangan/refund_selesai/{id}
 */
class Keuangan extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('pendapatan_lib');
        $this->load->model('refund_model');
    }

    public function index()
    {
        /* Toko yang punya pendapatan siap cair. Dihitung dengan satu query
           agregat, bukan memanggil pendapatan_lib per toko: dengan ratusan
           toko, cara kedua berarti ratusan query untuk satu halaman. */
        $siap = $this->db
            ->select('s.id, s.name, s.bank_nama, s.bank_nomor, s.bank_atas_nama,
                      COUNT(o.id) AS pesanan, COALESCE(SUM(o.net_store), 0) AS jumlah', FALSE)
            ->from('stores s')
            ->join('orders o', "o.store_id = s.id AND o.payment_status = 'paid'
                                AND o.order_status = 'delivered' AND o.payout_id IS NULL", 'inner')
            ->group_by('s.id, s.name, s.bank_nama, s.bank_nomor, s.bank_atas_nama')
            ->having('jumlah > 0')
            ->order_by('jumlah', 'DESC')
            ->get()->result_array();

        $data = array(
            'siap'   => $siap,
            'payout' => $this->db
                ->select('p.*, s.name AS toko', FALSE)
                ->from('payouts p')
                ->join('stores s', 's.id = p.store_id', 'left')
                ->order_by('p.created_at', 'DESC')
                ->limit(50)
                ->get()->result_array(),
            'refund_baru' => count($this->refund_model->daftar_admin('disetujui')),
        );

        $this->render('admin/v_keuangan', $data);
    }

    public function cairkan($store_id = NULL)
    {
        $this->wajib_post();

        $hasil = $this->pendapatan_lib->cairkan($store_id, $this->input->post('catatan', TRUE));

        $this->session->set_flashdata($hasil['ok'] ? 'sukses' : 'error', $hasil['pesan']);
        return redirect('admin/keuangan');
    }

    public function selesai($payout_id = NULL)
    {
        $this->wajib_post();

        $ok = $this->pendapatan_lib->selesaikan($payout_id, $this->input->post('bukti', TRUE));

        $this->session->set_flashdata($ok ? 'sukses' : 'error',
            $ok ? 'Pencairan ditandai selesai.' : 'Pencairan tidak ditemukan atau sudah selesai.');
        return redirect('admin/keuangan');
    }

    public function gagal($payout_id = NULL)
    {
        $this->wajib_post();

        $ok = $this->pendapatan_lib->gagalkan($payout_id, $this->input->post('catatan', TRUE));

        /* Pesanannya dilepas supaya bisa dicairkan ulang - kalau tidak,
           uang penjual tersangkut di pencairan gagal selamanya. */
        $this->session->set_flashdata($ok ? 'sukses' : 'error',
            $ok ? 'Pencairan ditandai gagal. Pesanannya bisa dicairkan ulang.' : 'Pencairan tidak ditemukan.');
        return redirect('admin/keuangan');
    }

    public function refund()
    {
        $this->render('admin/v_refund', array(
            'daftar' => $this->refund_model->daftar_admin($this->input->get('status') ?: NULL),
            'status' => $this->input->get('status'),
            'jumlah' => array(
                'disetujui' => count($this->refund_model->daftar_admin('disetujui')),
                'diminta'   => count($this->refund_model->daftar_admin('diminta')),
            ),
        ));
    }

    public function refund_selesai($id = NULL)
    {
        $this->wajib_post();

        $ok = $this->refund_model->selesaikan($id, $this->input->post('bukti', TRUE));

        $this->session->set_flashdata($ok ? 'sukses' : 'error',
            $ok ? 'Pengembalian dana ditandai selesai.' : 'Permintaan tidak ditemukan atau belum disetujui penjual.');
        return redirect('admin/keuangan/refund');
    }

    /* ------------------------------------------------------------------ */

    protected function wajib_post()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
    }

    protected function render($view, $data = array())
    {
        $data['me'] = $this->me;
        $this->load->view('admin/v_admin_header', $data);
        $this->load->view($view, $data);
        $this->load->view('admin/v_admin_footer');
    }
}
