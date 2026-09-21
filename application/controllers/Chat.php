<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Chat - percakapan pesanan dari sisi CUSTOMER.
 * Simpan di: application/controllers/Chat.php
 */
class Chat extends CI_Controller
{
    protected $order;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model(array('order_model', 'chat_model'));
        $this->load->helper(array('url', 'form', 'money'));
    }

    /** Ambil pesanan dari nomor + token, atau 404. */
    protected function ambil($nomor, $token)
    {
        $o = $this->order_model->find_by_token($nomor, $token);
        if (!$o) {
            show_404();
        }
        return $o;
    }

    // URL: domain.com/chat/index/{nomor}/{token} atau domain.com/chat/{nomor}/{token}
    public function index($nomor = NULL, $token = NULL)
    {
        $order = $this->ambil($nomor, $token);
        $this->chat_model->tandai_dibaca($order['id'], 'customer');

        $toko = $order['store_id']
            ? $this->db->where('id', (int) $order['store_id'])->get('stores')->row_array()
            : NULL;

        $data = array(
            'order'       => $order,
            'nomor_order' => $nomor,
            'token' => $token,
            'toko'        => $toko,
            'pesan'       => $this->chat_model->pesan($order['id']),
            'sisa_revisi' => $toko
                ? max(0, (int) $toko['maks_revisi'] - (int) $order['revisi_terpakai'])
                : 0,
            'terkunci'    => (bool) $order['card_locked_at'],
        );

        $data['pages'] = 'v_chat_customer';
        $this->load->view('index', $data);
    }

    // URL: domain.com/chat/kirim/{nomor}/{token}
    public function kirim($nomor = NULL, $token = NULL)
    {
        $order = $this->ambil($nomor, $token);
        $isi   = trim((string) $this->input->post('isi', TRUE));

        if ($isi === '') {
            return $this->json(array('ok' => FALSE, 'pesan' => 'Pesan kosong.'), 422);
        }

        $this->chat_model->kirim($order['id'], 'customer', 'teks', $isi);
        return $this->json(array('ok' => TRUE));
    }

    // URL: domain.com/chat/baru/{nomor}/{token}
    public function baru($nomor = NULL, $token = NULL)
    {
        $order = $this->ambil($nomor, $token);
        $sejak = (int) $this->input->get('sejak');

        $baru = $this->chat_model->pesan($order['id'], $sejak);

        /* Ditandai dibaca HANYA kalau panel sedang terbuka.
 
           Versi lama menandainya setiap kali polling menemukan pesan baru -
           padahal polling tetap jalan saat panel tertutup. Akibatnya pesan
           penjual ditandai sudah dibaca padahal customer belum melihatnya,
           dan lencananya tidak pernah muncul. */
        if ($baru && $this->input->get('dibaca') === '1') {
            $this->chat_model->tandai_dibaca($order['id'], 'customer');
        }

        $toko = $order['store_id']
            ? $this->db->where('id', (int) $order['store_id'])->get('stores')->row_array()
            : NULL;

        $maks = $toko ? (int) $toko['maks_revisi'] : 0;

        return $this->json(array(
            'ok'         => TRUE,
            'pesan'      => $baru,
            'acc_status' => $order['acc_status'],
            'terkunci'   => (bool) $order['card_locked_at'],

            // Dihitung SESUDAH penandaan, jadi angkanya selalu mutakhir.
            'belum'      => $this->chat_model->belum_dibaca($order['id'], 'customer'),

            /* Sisa jatah revisi. Tanpa ini, tulisan "(2x)" di tombol tidak
               pernah berubah walau jatahnya sudah terpakai - dan customer
               baru tahu habis setelah menekannya. */
            'sisa_revisi' => max(0, $maks - (int) $order['revisi_terpakai']),
        ));
    }

    // URL: domain.com/chat/setuju/{nomor}/{token}
    public function setuju($nomor = NULL, $token = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order = $this->ambil($nomor, $token);

        if (!in_array($order['acc_status'], array('menunggu', 'revisi'), TRUE)) {
            return $this->json(array('ok' => FALSE, 'pesan' => 'Tidak ada foto yang menunggu persetujuan.'), 422);
        }

        $this->db->where('id', (int) $order['id'])
            ->where_in('acc_status', array('menunggu', 'revisi'))
            ->update('orders', array(
                'acc_status'     => 'disetujui',
                'card_locked_at' => date('Y-m-d H:i:s'),
            ));

        if ($this->db->affected_rows() < 1) {
            return $this->json(array('ok' => FALSE, 'pesan' => 'Status sudah berubah. Muat ulang halaman.'), 409);
        }

        $this->chat_model->sistem(
            $order['id'],
            'Customer menyetujui rangkaian. Kata-kata papan dikunci dan bunga siap dikirim.'
        );

        return $this->json(array('ok' => TRUE, 'acc_status' => 'disetujui'));
    }

    // URL: domain.com/chat/revisi/{nomor}/{token}
    public function revisi($nomor = NULL, $token = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order   = $this->ambil($nomor, $token);
        $catatan = trim((string) $this->input->post('catatan', TRUE));

        if ($order['acc_status'] !== 'menunggu') {
            return $this->json(array('ok' => FALSE, 'pesan' => 'Tidak ada foto yang bisa direvisi.'), 422);
        }
        if (mb_strlen($catatan) < 5) {
            return $this->json(array(
                'ok'    => FALSE,
                'pesan' => 'Tulis apa yang perlu diperbaiki, minimal 5 karakter.'
            ), 422);
        }

        $toko = $this->db->where('id', (int) $order['store_id'])->get('stores')->row_array();
        $maks = $toko ? (int) $toko['maks_revisi'] : 0;

        if ((int) $order['revisi_terpakai'] >= $maks) {
            return $this->json(array(
                'ok'    => FALSE,
                'pesan' => 'Jatah revisi sudah habis (' . $maks . 'x). Silakan hubungi toko lewat percakapan ini.'
            ), 422);
        }

        $this->db->where('id', (int) $order['id'])
            ->where('acc_status', 'menunggu')
            ->set('revisi_terpakai', 'revisi_terpakai + 1', FALSE)
            ->update('orders', array('acc_status' => 'revisi'));

        if ($this->db->affected_rows() < 1) {
            return $this->json(array('ok' => FALSE, 'pesan' => 'Status sudah berubah. Muat ulang halaman.'), 409);
        }

        $this->chat_model->kirim($order['id'], 'customer', 'teks', $catatan);

        $sisa = $maks - ((int) $order['revisi_terpakai'] + 1);
        $this->chat_model->sistem(
            $order['id'],
            'Customer meminta perbaikan. Sisa jatah revisi: ' . $sisa . 'x.'
        );

        // return $this->json(array('ok' => TRUE, 'sisa_revisi' => $sisa));
        return $this->json(array(
            'ok'          => TRUE,
            'sisa_revisi' => max(0, $sisa),
            'acc_status'  => 'revisi',
        ));
    }

    protected function json($data, $kode = 200)
    {
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();

        return $this->output->set_status_header($kode)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}
