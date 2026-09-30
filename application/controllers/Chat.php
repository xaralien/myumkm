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

        if (in_array($method, array('toko', 'conv', 'conv_baru', 'conv_kirim'), TRUE)) {
            return $this->$method($p0, $p1);
        }

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

    /* =====================================================================
       TANYA KE TOKO - percakapan tanpa pesanan
       ===================================================================== */

    /**
     * Mulai atau lanjutkan percakapan dengan sebuah toko.
     * POST chat/toko/{slug_toko}   (product_id opsional)
     *
     * Butuh akun: percakapan ini tidak menempel pada pesanan, jadi tidak ada
     * access_token yang bisa membuktikan haknya. Tamu diarahkan ke halaman
     * masuk lalu kembali ke sini.
     */
    public function toko($slug = NULL, $x = NULL)
    {
        $this->load->library('auth_lib');

        $toko = $this->db->where('slug', $slug)->where('is_active', 1)
                         ->get('stores')->row_array();
        if ( ! $toko) {
            show_404();
        }

        if ( ! $this->auth_lib->id()) {
            $this->session->set_flashdata('info', 'Masuk dulu untuk mengirim pesan ke toko.');
            return redirect('auth/login?next=' . rawurlencode('shop?store=' . $toko['id']));
        }

        // Pemilik toko tidak perlu mengirim pesan ke tokonya sendiri.
        if ((int) $toko['user_id'] === (int) $this->auth_lib->id()) {
            $this->session->set_flashdata('error', 'Ini tokomu sendiri.');
            return redirect('shop?store=' . $toko['id']);
        }

        $conv = $this->chat_model->conv($toko['id'], $this->auth_lib->id());

        // Kartu produk hanya dikirim sekali, saat percakapan dimulai dari
        // halaman produk - bukan tiap kali ruangnya dibuka lagi.
        $pid = (int) $this->input->post('product_id');
        if ($pid) {
            $p = $this->db->select('id')->where('id', $pid)
                          ->where('store_id', (int) $toko['id'])
                          ->where('is_active', 1)
                          ->get('products')->row_array();
            if ($p) {
                $this->chat_model->kirim_conv(
                    $conv, 'customer', 'teks',
                    trim((string) $this->input->post('isi', TRUE)) ?: NULL,
                    NULL, $this->auth_lib->id(), $p['id']
                );
            }
        }

        return redirect('chat/conv/' . $conv);
    }

    /** Halaman percakapan. GET chat/conv/{id} */
    public function conv($id = NULL, $x = NULL)
    {
        $c = $this->conv_milik($id);

        $this->chat_model->tandai_dibaca_conv($c['id'], 'customer');

        $data = array(
            'conv'  => $c,
            'pesan' => $this->chat_model->pesan_conv($c['id']),
        );
        $data['pages'] = 'v_chat_conv';
        $this->load->view('index', $data);
    }

    /** GET chat/conv_baru/{id}?sejak=N&dibaca=1 [AJAX] */
    public function conv_baru($id = NULL, $x = NULL)
    {
        $c = $this->conv_milik($id);
        $sejak = (int) $this->input->get('sejak');

        $baru = $this->chat_model->pesan_conv($c['id'], $sejak);

        if ($baru && $this->input->get('dibaca') === '1') {
            $this->chat_model->tandai_dibaca_conv($c['id'], 'customer');
        }

        return $this->json(array(
            'ok'    => TRUE,
            'pesan' => $baru,
            'belum' => $this->chat_model->belum_dibaca_conv($c['id'], 'customer'),
        ));
    }

    /** POST chat/conv_kirim/{id} [AJAX] */
    public function conv_kirim($id = NULL, $x = NULL)
    {
        $c   = $this->conv_milik($id);
        $isi = trim((string) $this->input->post('isi', TRUE));

        if ($isi === '') {
            return $this->json(array('ok' => FALSE, 'pesan' => 'Pesan kosong.'), 422);
        }

        $this->chat_model->kirim_conv(
            $c['id'], 'customer', 'teks', mb_substr($isi, 0, 1000),
            NULL, $this->auth_lib->id()
        );
        return $this->json(array('ok' => TRUE));
    }

    /**
     * Wadah percakapan milik akun yang sedang masuk, atau 404.
     *
     * Dicocokkan ke user_id - bukan sekadar id percakapan. Tanpa itu,
     * menaikkan angka di alamat cukup untuk membaca percakapan orang lain.
     */
    protected function conv_milik($id)
    {
        $this->load->library('auth_lib');

        if ( ! $this->auth_lib->id()) {
            redirect('auth/login?next=' . rawurlencode(uri_string()));
        }

        $c = $this->chat_model->conv_row($id);

        if ( ! $c || (int) $c['user_id'] !== (int) $this->auth_lib->id()) {
            show_404();
        }
        return $c;
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
