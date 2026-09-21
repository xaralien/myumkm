<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Payment - simpan di application/controllers/Payment.php
 *
 *   POST payment/callback   dipanggil server Duitku (server-to-server)
 *   GET  payment/finish     tempat pembeli mendarat setelah bayar
 *
 * ============================ WAJIB DIBACA ==================================
 * Callback datang dari server Duitku, BUKAN dari browser pembeli. Artinya
 * tidak ada token CSRF di dalamnya. Kalau CSRF aktif (config['csrf_protection']
 * = TRUE), CodeIgniter akan menolak callback dengan HTTP 403 dan pembayaran
 * yang berhasil tidak pernah tercatat - tanpa pesan error apa pun yang terlihat.
 *
 * Buka application/config/config.php dan tambahkan:
 *
 *     $config['csrf_exclude_uris'] = array('payment/callback');
 *
 * Ini kesalahan nomor satu pada integrasi payment gateway di CodeIgniter.
 * ========================================================================== */
class Payment extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('duitku', 'session'));
        $this->load->model('order_model');
        $this->load->helper(array('url', 'money'));

        $this->load->model('chat_model');
    }

    /* ------------------------------------------------------------- POP --- */

    /**
     * Halaman yang membuka popup Duitku.
     *
     * Dibuat terpisah dari checkout dengan sengaja: pembeli yang menutup
     * popup tanpa membayar bisa kembali ke URL ini dan mencoba lagi tanpa
     * mengisi ulang formulir atau membuat pesanan kedua.
     *
     * Reference dipakai ulang, tidak membuat invoice baru. Duitku menolak
     * pembuatan invoice kedua dengan merchantOrderId yang sama
     * (HTTP 409, atau 400 kalau nominalnya berbeda).
     */
    public function pay($order_number = NULL, $token = NULL)
    {
        $order = $this->order_model->find_by_token($order_number, $token);
        if (! $order) {
            show_404();
        }

        $done = 'checkout/done/' . $order['order_number'] . '/' . $order['access_token'];

        // Sudah lunas atau memang COD - tidak ada yang perlu dibayar di sini.
        if ($order['payment_status'] === 'paid' || $order['payment_method'] === 'cod') {
            return redirect($done);
        }
        /* Belum punya reference, atau pembuatannya pernah gagal -> coba lagi.
           Sebelumnya di sini jalan buntu: pesanan sudah tersimpan tapi tidak
           ada cara membuat pembayarannya, dan pembeli hanya diminta
           menghubungi WhatsApp. */
        if (empty($order['duitku_reference']) || $order['payment_status'] === 'failed') {
            $order = $this->buat_ulang_invoice($order);

            if (! $order) {
                return redirect($done);   // pesan sudah di-flashdata
            }
        }

        $data = array(
            'order'     => $order,
            'reference' => $order['duitku_reference'],
            'js_url'    => $this->duitku->js_url(),
            'language'  => $this->duitku->language(),
            'done_url'  => site_url($done),
            'fallback'  => $order['duitku_payment_url'],   // kalau popup gagal dimuat
            'poll_awal' => $this->cfg('poll_awal', 3),
            'poll_akhir' => $this->cfg('poll_akhir', 12),
            'poll_maks' => $this->cfg('poll_maks_detik', 600),
        );

        $data['pages'] = 'v_pay';
        $this->load->view('index', $data);
    }

    /**
     * Buat ulang transaksi Duitku untuk pesanan yang sudah tersimpan.
     * @return array|FALSE data pesanan terbaru, atau FALSE kalau tetap gagal
     */
    protected function buat_ulang_invoice(array $order)
    {
        $items = $this->order_model->items($order['id']);

        if (! $items) {
            $this->session->set_flashdata('error', 'Rincian pesanan tidak ditemukan.');
            return FALSE;
        }

        $duitku_items = array();
        foreach ($items as $it) {
            $nama = $it['product_name'] . ($it['variant_name'] ? ' - ' . $it['variant_name'] : '');
            $duitku_items[] = array(
                'name'     => $nama,
                'price'    => (int) $it['unit_price'],
                'quantity' => (int) $it['qty'],
            );
        }

        $inv = $this->duitku->create_invoice($order, $duitku_items);

        if (isset($inv['error']) || empty($inv['reference'])) {
            $this->session->set_flashdata(
                'error',
                'Sistem pembayaran masih bermasalah: '
                    . (isset($inv['error']) ? $inv['error'] : 'tidak diketahui')
                    . '. Hubungi kami lewat WhatsApp.'
            );
            return FALSE;
        }

        $this->order_model->set_duitku_info($order['id'], $inv);

        /* Status dikembalikan ke 'unpaid'. Kalau dibiarkan 'failed', polling
           langsung berhenti dan pembayaran yang baru saja dibuat tidak akan
           pernah terpantau. */
        $this->db->where('id', $order['id'])
            ->update('orders', array('payment_status' => 'unpaid'));

        $this->order_model->log_callback(
            $order['order_number'],
            json_encode($inv),
            TRUE,
            'Transaksi dibuat ulang'
        );

        return $this->order_model->get_by_number($order['order_number']);
    }

    /* ------------------------------------------- CEK STATUS TANPA CALLBACK ---
     |  Callback butuh URL yang bisa diakses dari internet - mustahil di
     |  localhost tanpa ngrok. Dua method di bawah menanyakan status
     |  langsung ke Duitku, jadi pembayaran tetap tercatat tanpa callback.
     | ---------------------------------------------------------------------- */

    /**
     * Dipanggil berkala oleh JavaScript di halaman bayar & halaman pesanan.
     *
     * GET payment/status/{nomor}/{token}
     */
    public function status($order_number = NULL, $token = NULL)
    {
        $order = $this->order_model->find_by_token($order_number, $token);
        if (! $order) {
            return $this->json(array('ok' => FALSE, 'pesan' => 'Pesanan tidak ditemukan.'), 404);
        }

        /* Kondisi AKHIR (paid, failed, expired) dijawab ok = TRUE dengan
           selesai = TRUE, BUKAN ok = FALSE. Kalau dijawab sebagai galat,
           JavaScript menganggapnya gangguan sementara lalu terus memantau
           sampai batas 10 menit - padahal statusnya tidak akan berubah. */
        if ($order['payment_status'] !== 'unpaid' || $order['payment_method'] === 'cod') {
            return $this->json($this->ringkas($order, FALSE));
        }

        /* Jeda minimum per pesanan. Pembeli bisa membuka halaman yang sama
           di beberapa tab, dan tanpa penjagaan ini tiap tab menghasilkan
           permintaan sendiri ke Duitku. */
        $kunci = 'cek_' . $order['order_number'];
        $lalu  = (int) $this->session->userdata($kunci);
        $jeda  = (int) $this->cfg('jeda_cek_detik', 3);

        if ($lalu && (time() - $lalu) < $jeda) {
            return $this->json($this->ringkas($order, FALSE));
        }
        $this->session->set_userdata($kunci, time());

        $berubah = $this->tanya_duitku($order);

        if ($berubah) {
            $order = $this->order_model->get_by_number($order['order_number']);
        }
        return $this->json($this->ringkas($order, $berubah));
    }

    /**
     * Penyapu berkala - untuk pembeli yang MENUTUP TAB setelah membayar.
     *
     * Ini lapis yang paling sering dilupakan. Polling di halaman hanya
     * jalan selama halaman terbuka; pembayaran lewat virtual account
     * sering baru dilakukan berjam-jam kemudian, saat tidak ada tab
     * yang terbuka sama sekali. Tanpa penyapu, pesanan itu tetap
     * 'unpaid' selamanya walaupun uangnya sudah masuk.
     *
     * Jalankan lewat cron atau Task Scheduler, tiap 5-10 menit:
     *     curl "https://situsmu.com/payment/sweep?key=KUNCI"
     *
     * TIDAK butuh URL publik - servernya yang menghubungi Duitku,
     * bukan sebaliknya. Itu sebabnya cara ini jalan di localhost.
     */
    public function sweep()
    {
        $kunci_benar = (string) $this->cfg('sweep_key', '');
        $kunci_kirim = (string) $this->input->get('key', TRUE);

        if ($kunci_benar === '' || $kunci_benar === 'GANTI_DENGAN_ACAK_PANJANG') {
            return $this->plain('sweep_key belum diatur di config/duitku.php', 500);
        }
        if (! hash_equals($kunci_benar, $kunci_kirim)) {
            return $this->plain('Kunci salah', 403);
        }

        /* Batas kedaluwarsa DITURUNKAN dari expiry_period, bukan angka
           terpisah. Sebelumnya invoice Duitku mati setelah 24 jam
           (expiry_period 1440 menit) tapi penyapu baru menandainya di jam
           ke-48 - selama 24 jam itu pesanan yang sudah tidak bisa dibayar
           tetap tampil sebagai "menunggu pembayaran". */
        $jam_expired = (int) ceil((int) $this->cfg('expiry_period', 1440) / 60);

        // Diperiksa sedikit lebih lama dari masa berlaku invoice, untuk
        // menangkap pembayaran yang masuk tepat di menit-menit terakhir.
        $jam_periksa = $jam_expired + 2;

        $daftar = $this->order_model->belum_lunas($jam_periksa, 50);

        $lunas = 0;
        foreach ($daftar as $o) {
            if ($this->tanya_duitku($o)) {
                $lunas++;
            }
            // Beri jeda supaya Duitku tidak dibanjiri permintaan beruntun.
            usleep(200000);   // 0,2 detik
        }

        // Dijalankan SETELAH pengecekan, supaya pembayaran yang masuk di
        // menit terakhir tidak keburu dibatalkan.
        $kedaluwarsa = $this->order_model->tandai_kedaluwarsa($jam_expired);

        // return $this->plain(sprintf(
        //     "diperiksa=%d lunas=%d kedaluwarsa=%d (batas %d jam)\n",
        //     count($daftar),
        //     $lunas,
        //     $kedaluwarsa,
        //     $jam_expired
        // ));

        $acc = 0;
        foreach ($this->chat_model->acc_kedaluwarsa(50) as $o) {
            if ($this->chat_model->setujui_otomatis($o['id'])) {
                $acc++;
            }
        }

        // Tambahkan $acc ke baris keluaran:

        return $this->plain(sprintf(
            "diperiksa=%d lunas=%d kedaluwarsa=%d acc_otomatis=%d\n",
            count($daftar),
            $lunas,
            $kedaluwarsa,
            $jam_expired,
            $acc
        ));
    }

    /* ------------------------------------------------------------------ */

    /**
     * Tanya status ke Duitku, tandai lunas kalau memang sudah.
     * @return bool TRUE kalau status berubah jadi lunas pada panggilan ini
     */
    protected function tanya_duitku(array $order)
    {
        $res = $this->duitku->check_transaction($order['order_number']);

        if (! isset($res['statusCode'])) {
            return FALSE;
        }

        if ($res['statusCode'] === '00') {
            /* Nominal WAJIB dicocokkan, sama seperti di callback. Jawaban
               yang sah pun belum menjamin nominalnya benar - misalnya kalau
               invoice pernah dibuat ulang dengan nilai berbeda. */
            if (isset($res['amount']) && (int) $res['amount'] !== (int) $order['total']) {
                log_message('error', 'Duitku status: nominal beda untuk ' . $order['order_number']);
                $this->order_model->log_callback(
                    $order['order_number'],
                    json_encode($res),
                    FALSE,
                    'Nominal beda saat cek status'
                );
                return FALSE;
            }

            $berubah = $this->order_model->mark_paid(
                $order['order_number'],
                isset($res['reference']) ? $res['reference'] : NULL
            );
            $this->order_model->log_callback(
                $order['order_number'],
                json_encode($res),
                TRUE,
                'Lunas (cek status, bukan callback)'
            );
            return $berubah;
        }

        // 02 = dibatalkan atau kedaluwarsa
        if ($res['statusCode'] === '02') {
            $this->order_model->mark_failed(
                $order['order_number'],
                'expired',
                'Kedaluwarsa menurut Duitku'
            );
        }
        return FALSE;
    }

    protected function ringkas(array $order, $berubah)
    {
        $st = $order['payment_status'];

        return array(
            'ok'             => TRUE,
            'berubah'        => (bool) $berubah,
            'payment_status' => $st,
            'order_status'   => $order['order_status'],

            'selesai'        => $st !== 'unpaid',
            'lunas'          => $st === 'paid',
            'gagal'          => in_array($st, array('failed', 'expired'), TRUE),

            /* 'failed' berarti transaksinya BELUM PERNAH terbentuk di Duitku -
               bukan pembayaran yang ditolak. Pesanannya masih utuh, jadi
               pembeli boleh mencoba membuat pembayarannya lagi. */
            'bisa_ulang'     => $st === 'failed',
            'url_ulang'      => site_url('payment/pay/' . $order['order_number']
                . '/' . $order['access_token']),
            'label_bayar'    => label_bayar($st),
            'label_status'   => label_status($order['order_status']),
        );
    }

    protected function json($data, $kode = 200)
    {
        return $this->output
            ->set_status_header($kode)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    protected function cfg($nama, $bawaan = NULL)
    {
        $this->config->load('duitku', TRUE);
        $c = $this->config->item('duitku', 'duitku');
        return isset($c[$nama]) ? $c[$nama] : $bawaan;
    }

    /* -------------------------------------------------------- callback --- */

    public function callback()
    {
        $post = $this->input->post(NULL, FALSE);   // form-encoded, bukan JSON
        $raw  = http_build_query((array) $post);

        if (! $post) {
            $this->order_model->log_callback(NULL, $raw, FALSE, 'Isi callback kosong');
            return $this->plain('Bad Request', 400);
        }

        $nomor = isset($post['merchantOrderId']) ? $post['merchantOrderId'] : NULL;

        // 1. Signature dulu. Tanpa ini siapa pun bisa mengirim POST palsu
        //    ke URL ini dan menandai pesanannya sendiri lunas.
        if (! $this->duitku->valid_callback($post)) {
            $this->order_model->log_callback($nomor, $raw, FALSE, 'Signature tidak cocok');
            log_message('error', 'Duitku callback signature salah untuk ' . $nomor);
            return $this->plain('Invalid signature', 400);
        }

        $order = $this->order_model->get_by_number($nomor);
        if (! $order) {
            $this->order_model->log_callback($nomor, $raw, FALSE, 'Pesanan tidak ditemukan');
            return $this->plain('Order not found', 404);
        }

        // 2. Nominal harus sama persis dengan yang tersimpan. Signature yang
        //    sah pun tidak menjamin nominalnya benar.
        if ((int) $post['amount'] !== (int) $order['total']) {
            $this->order_model->log_callback(
                $nomor,
                $raw,
                FALSE,
                'Nominal beda: callback ' . $post['amount'] . ' vs pesanan ' . $order['total']
            );
            log_message('error', 'Duitku callback nominal tidak cocok untuk ' . $nomor);
            return $this->plain('Amount mismatch', 400);
        }

        $result = isset($post['resultCode']) ? $post['resultCode'] : '';

        if ($result === '00') {
            // mark_paid aman dipanggil berulang - Duitku mengulang callback
            // kalau balasan pertama tidak sampai.
            $this->order_model->mark_paid(
                $nomor,
                isset($post['reference']) ? $post['reference'] : NULL,
                isset($post['vaNumber'])  ? $post['vaNumber']  : NULL
            );
            $this->order_model->log_callback($nomor, $raw, TRUE, 'Lunas');
        } else {
            $this->order_model->mark_failed($nomor, 'failed', 'resultCode ' . $result);
            $this->order_model->log_callback($nomor, $raw, TRUE, 'Gagal, resultCode ' . $result);
        }

        // 3. Duitku menganggap callback berhasil hanya kalau isi balasannya
        //    persis "SUCCESS". Kalau tidak, callback akan terus diulang.
        return $this->plain('SUCCESS', 200);
    }

    protected function plain($text, $code = 200)
    {
        return $this->output
            ->set_status_header($code)
            ->set_content_type('text/plain')
            ->set_output($text);
    }

    /* ---------------------------------------------------------- return --- */

    /**
     * Pembeli mendarat di sini setelah menyelesaikan pembayaran.
     *
     * Halaman ini TIDAK BOLEH dipakai untuk menandai pesanan lunas - pembeli
     * bisa saja menutup tab sebelum sampai sini, atau mengetik URL-nya
     * langsung. Yang menentukan status hanyalah callback.
     *
     * Yang dilakukan di sini cuma jaring pengaman: kalau callback belum
     * masuk (misal server sempat down), kita tanyakan langsung ke Duitku.
     */
    public function finish()
    {
        $nomor = $this->input->get('merchantOrderId', TRUE);
        if (! $nomor) {
            return redirect('/');
        }

        $order = $this->order_model->get_by_number($nomor);
        if (! $order) {
            show_404();
        }

        if ($order['payment_status'] !== 'paid') {
            $status = $this->duitku->check_transaction($nomor);
            if (
                isset($status['statusCode']) && $status['statusCode'] === '00'
                && (int) $status['amount'] === (int) $order['total']
            ) {
                $this->order_model->mark_paid(
                    $nomor,
                    isset($status['reference']) ? $status['reference'] : NULL
                );
                $order = $this->order_model->get_by_number($nomor);
            }
        }

        return redirect('checkout/done/' . $order['order_number'] . '/' . $order['access_token']);
    }
}
