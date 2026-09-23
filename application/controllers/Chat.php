<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Chat - percakapan pesanan dari sisi PEMBELI.
 * Simpan di: application/controllers/Chat.php
 *
 *   GET  chat/{nomor}/{token}          halaman percakapan
 *   GET  chat/index/{nomor}/{token}    (bentuk lama, tetap diterima)
 *   POST chat/kirim/{nomor}/{token}    kirim pesan
 *   GET  chat/baru/{nomor}/{token}     ambil pesan baru
 *
 * Alur persetujuan foto (setuju / revisi) sudah dihapus - itu khas toko
 * bunga. Di marketplace, percakapan dipakai untuk tanya jawab pesanan.
 *
 * Pembeli tamu tidak punya akun; haknya dibuktikan lewat access_token di
 * URL, sama seperti halaman pesanan.
 */
class Chat extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model(array('order_model', 'chat_model'));
        $this->load->helper(array('url', 'form', 'money'));
    }

    /**
     * CodeIgniter membaca segmen kedua URL sebagai nama method, jadi
     * chat/BNG-2026.../token dicari sebagai method "BNG-2026..." lalu 404.
     * Daftar aksi ditulis tegas, bukan method_exists() - nomor pesanan yang
     * kebetulan sama dengan nama method internal tidak boleh memanggilnya.
     */
    public function _remap($method, $params = array())
    {
        $p0 = isset($params[0]) ? $params[0] : NULL;
        $p1 = isset($params[1]) ? $params[1] : NULL;

        if (in_array($method, array('kirim', 'baru', 'index'), TRUE)) {
            return $this->$method($p0, $p1);
        }
        return $this->index($method, $p0);
    }

    protected function ambil($nomor, $token)
    {
        $o = $this->order_model->find_by_token($nomor, $token);
        if ( ! $o) {
            show_404();
        }
        return $o;
    }

    public function index($nomor = NULL, $token = NULL)
    {
        $order = $this->ambil($nomor, $token);
        $this->chat_model->tandai_dibaca($order['id'], 'customer');

        $data = array(
            'order' => $order,
            'toko'  => $order['store_id']
                ? $this->db->select('name, phone')->where('id', (int) $order['store_id'])
                           ->get('stores')->row_array()
                : NULL,
            'pesan' => $this->chat_model->pesan($order['id']),
        );

        $data['pages'] = 'v_chat_customer';
        $this->load->view('index', $data);
    }

    public function kirim($nomor = NULL, $token = NULL)
    {
        $order = $this->ambil($nomor, $token);
        $isi   = trim((string) $this->input->post('isi', TRUE));

        if ($isi === '') {
            return $this->json(array('ok' => FALSE, 'pesan' => 'Pesan kosong.'), 422);
        }

        $this->chat_model->kirim($order['id'], 'customer', 'teks', mb_substr($isi, 0, 1000));
        return $this->json(array('ok' => TRUE));
    }

    public function baru($nomor = NULL, $token = NULL)
    {
        $order = $this->ambil($nomor, $token);
        $sejak = (int) $this->input->get('sejak');

        $baru = $this->chat_model->pesan($order['id'], $sejak);

        /* Ditandai dibaca HANYA kalau panel sedang terbuka. Polling tetap
           jalan saat panel tertutup - kalau ditandai juga di situ, pesan
           penjual dianggap sudah dibaca padahal pembeli belum melihatnya. */
        if ($baru && $this->input->get('dibaca') === '1') {
            $this->chat_model->tandai_dibaca($order['id'], 'customer');
        }

        return $this->json(array(
            'ok'           => TRUE,
            'pesan'        => $baru,
            'order_status' => $order['order_status'],
            'belum'        => $this->chat_model->belum_dibaca($order['id'], 'customer'),
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
