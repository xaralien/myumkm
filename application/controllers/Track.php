<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Track - lacak pesanan tanpa login.
 * Simpan di: application/controllers/Track.php
 *
 *   GET  track          formulir
 *   POST track/cari     cari pakai nomor pesanan + nomor HP
 */
class Track extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('session', 'form_validation'));
        $this->load->model('order_model');
        $this->load->helper(array('url', 'form', 'money'));
    }

    public function index()
    {
        $data = array();
        $data['pages'] = 'v_track_form';
        $this->load->view('index', $data);
    }

    public function cari()
    {
        $this->form_validation->set_rules('order_number', 'Nomor pesanan', 'required|trim');
        $this->form_validation->set_rules('phone', 'Nomor WhatsApp', 'required|trim');
        $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');

        if ($this->form_validation->run() === FALSE) {
            return $this->index();
        }

        $nomor = strtoupper(trim($this->input->post('order_number', TRUE)));
        $phone = $this->normalize_phone($this->input->post('phone', TRUE));

        $order = $this->order_model->find_for_guest($nomor, $phone);

        if (! $order) {
            // Pesan sengaja tidak memisahkan "nomor salah" dan "HP salah".
            // Kalau dipisah, orang bisa menebak nomor pesanan orang lain.
            $this->session->set_flashdata(
                'error',
                'Pesanan tidak ditemukan. Periksa lagi nomor pesanan dan nomor WhatsApp-nya.'
            );
            return $this->index();
        }

        /* Nama & WhatsApp toko untuk tombol bantuan. Pesanan lama mungkin
           belum punya store_id, jadi tetap ditangani kalau kosong. */
        $toko = NULL;
        if (! empty($order['store_id'])) {
            $toko = $this->db->select('name, phone')
                ->where('id', (int) $order['store_id'])
                ->get('stores')->row_array();
        }

        $data = array(
            'order' => $order,
            'items' => $this->order_model->items($order['id']),
            'logs'  => $this->order_model->logs($order['id']),
            'toko'  => $toko,

            /* Boleh melanjutkan pembayaran kalau: metodenya online, belum
               lunas, dan pesanannya belum dibatalkan.
               'failed' IKUT diperbolehkan - itu berarti transaksinya belum
               pernah terbentuk di Duitku, bukan pembayaran yang ditolak.
               Payment::pay() akan membuatkan transaksi baru dari pesanan
               yang sudah tersimpan. */
            'bisa_bayar' => $order['payment_method'] === 'duitku'
                && in_array($order['payment_status'], array('unpaid', 'failed'), TRUE)
                && $order['order_status'] !== 'cancelled',

            /* Link bertoken. Pembeli sudah membuktikan kepemilikan lewat
               nomor pesanan + nomor HP, jadi tidak perlu mencari linknya
               lagi di riwayat WhatsApp. */
            'url_bayar' => site_url('payment/pay/' . $order['order_number']
                . '/' . $order['access_token']),
            'url_pesanan' => site_url('checkout/done/' . $order['order_number']
                . '/' . $order['access_token']),
        );

        $data['pages'] = 'v_track_result';
        $this->load->view('index', $data);
    }

    protected function normalize_phone($phone)
    {
        $p = preg_replace('/[^0-9]/', '', $phone);

        if (substr($p, 0, 1) === '0') {
            return '62' . substr($p, 1);
        }
        if (substr($p, 0, 2) === '62') {
            return $p;
        }
        return '62' . $p;
    }
}
