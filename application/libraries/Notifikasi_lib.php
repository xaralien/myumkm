<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Notifikasi_lib - kirim kabar lewat WhatsApp dan email.
 *
 * Simpan di: application/libraries/Notifikasi_lib.php
 * Pakai    : $this->load->library('notifikasi_lib');
 *            $this->notifikasi_lib->pesanan_dibayar($order);
 *
 * WhatsApp dikirim lewat library Api_Whatsapp milik sendiri
 * ($this->api_whatsapp->wa_notif($pesan, $nomor), di-autoload bila berkasnya
 * ada). Kalau library itu tidak ada, dipakai gerbang berformat Fonnte (POST
 * target + message, header Authorization berisi token) dari Pengaturan >
 * WhatsApp. Saklar "Kirim notifikasi WhatsApp" berlaku untuk keduanya.
 *
 * Tanpa gerbang, notifikasi DILEWATI dan dicatat sebagai 'dilewati' - tidak
 * ada halaman yang rusak, dan admin bisa melihat apa yang seharusnya
 * terkirim di Pengaturan > Notifikasi keluar.
 *
 * Kegagalan mengirim TIDAK PERNAH menggagalkan proses yang memicunya:
 * pembayaran yang sudah masuk tetap tercatat lunas walau WA-nya gagal.
 */
class Notifikasi_lib
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('pengaturan_lib');
        $this->CI->load->helper(array('url', 'money'));
    }

    /* ============================================================ kanal === */

    /**
     * @return bool TRUE kalau benar-benar terkirim
     */
    public function wa($nomor, $isi, $acara = NULL)
    {
        $nomor = $this->rapikan_nomor($nomor);
        if ($nomor === '') {
            return FALSE;
        }

        $p = $this->CI->pengaturan_lib;
        if ( ! $p->aktif('wa_aktif')) {
            $this->catat('wa', $nomor, NULL, $isi, 'dilewati', 'Notifikasi WA dimatikan di Pengaturan', $acara);
            return FALSE;
        }

        // Gerbang WA milik sendiri (application/libraries/Api_Whatsapp.php).
        if (isset($this->CI->api_whatsapp) && method_exists($this->CI->api_whatsapp, 'wa_notif')) {
            /* Api_Whatsapp membaca tabel `utility` (kolom server_wa) untuk
               memilih server. Tanpa tabel itu, db->get() di lingkungan
               pengembangan (db_debug aktif) menampilkan galat database lalu
               menghentikan SELURUH permintaan - termasuk checkout dan callback
               pembayaran. Jadi diperiksa dulu di sini. Lihat migrasi 33. */
            if ( ! $this->CI->db->table_exists('utility')) {
                $this->catat('wa', $nomor, NULL, $isi, 'gagal', 'Tabel utility (server_wa) belum ada - jalankan database/33_utility_wa.sql', $acara);
                return FALSE;
            }

            /* Keluaran dibungkus: peringatan PHP dari dalam gerbang (mis.
               variabel yang belum didefinisikan) dicetak CodeIgniter langsung
               ke halaman dan merusak balasan JSON seperti callback Duitku. */
            ob_start();
            try {
                $balas = $this->CI->api_whatsapp->wa_notif($isi, $nomor);
                $galat = $this->galat_balasan_wa($balas);
            } catch (\Throwable $e) {
                // Gerbang bermasalah tidak boleh menggagalkan checkout/pembayaran.
                $galat = get_class($e) . ': ' . $e->getMessage();
            }
            $bocor = trim(strip_tags((string) ob_get_clean()));
            if ($bocor !== '') {
                log_message('error', 'Api_Whatsapp mencetak keluaran: ' . $bocor);
            }

            $this->catat('wa', $nomor, NULL, $isi, $galat === NULL ? 'terkirim' : 'gagal',
                $galat === NULL ? NULL : mb_substr($galat, 0, 250), $acara);
            return $galat === NULL;
        }

        if ( ! $p->get('wa_token')) {
            $this->catat('wa', $nomor, NULL, $isi, 'dilewati', 'Gerbang WA belum diaktifkan', $acara);
            return FALSE;
        }

        if ( ! function_exists('curl_init')) {
            $this->catat('wa', $nomor, NULL, $isi, 'gagal', 'Ekstensi cURL tidak aktif', $acara);
            return FALSE;
        }

        $ch = curl_init($p->get('wa_url'));
        curl_setopt_array($ch, array(
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => http_build_query(array(
                'target'      => $nomor,
                'message'     => $isi,
                'countryCode' => '62',
            )),
            CURLOPT_HTTPHEADER     => array('Authorization: ' . $p->get('wa_token')),
            CURLOPT_RETURNTRANSFER => TRUE,
            // Pendek: notifikasi dikirim di tengah permintaan pengguna, dan
            // gerbang yang lambat tidak boleh membuat checkout ikut lambat.
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT        => 8,
        ));
        $balas = curl_exec($ch);
        $galat = curl_error($ch);
        $kode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = $balas ? json_decode($balas, TRUE) : NULL;
        // Fonnte menjawab 200 bahkan untuk kegagalan, dengan status=false.
        $ok = $kode >= 200 && $kode < 300 && ( ! is_array($json) || ! isset($json['status']) || $json['status']);

        $this->catat('wa', $nomor, NULL, $isi, $ok ? 'terkirim' : 'gagal',
            $ok ? NULL : mb_substr($galat ?: ($json['reason'] ?? ('HTTP ' . $kode)), 0, 250), $acara);
        return $ok;
    }

    /**
     * Baca balasan Api_Whatsapp::wa_notif(). NULL = dianggap terkirim.
     *
     * wa_notif() saat ini tidak mengembalikan apa-apa, jadi kegagalan dari
     * server WA belum bisa diketahui di sini - hanya galat PHP yang
     * tertangkap. Kalau nanti wa_notif() diberi `return`, balasan berbentuk
     * array/JSON dengan success/status = false otomatis tercatat 'gagal'.
     *
     * @return string|NULL pesan galat, atau NULL kalau tidak ada tanda gagal
     */
    protected function galat_balasan_wa($balas)
    {
        if ($balas === FALSE) {
            return 'wa_notif mengembalikan FALSE';
        }
        if (is_string($balas)) {
            $j = json_decode($balas, TRUE);
            $balas = is_array($j) ? $j : NULL;
        }
        if (is_array($balas)) {
            foreach (array('success', 'status') as $k) {
                if (array_key_exists($k, $balas) && ($balas[$k] === FALSE || $balas[$k] === 'false' || $balas[$k] === 0)) {
                    return (string) ($balas['message'] ?? $balas['reason'] ?? 'Server WA menolak');
                }
            }
        }
        return NULL;
    }

    public function email($ke, $judul, $isi, $acara = NULL)
    {
        $ke = trim((string) $ke);
        if ($ke === '' || ! filter_var($ke, FILTER_VALIDATE_EMAIL)) {
            return FALSE;
        }

        $p = $this->CI->pengaturan_lib;

        /* SMTP dari Admin > Pengaturan kalau diisi; kalau tidak, memakai
           config/surat.php - berkas yang sama dengan email reset sandi &
           verifikasi (Surat_lib). Jadi cukup mengatur SMTP di satu tempat. */
        $this->CI->config->load('surat', TRUE, TRUE);
        $surat = (array) $this->CI->config->item('surat', 'surat');
        $dari  = $p->get('email_dari') ?: (isset($surat['from_email']) ? $surat['from_email'] : '');

        if ( ! $p->aktif('email_aktif') || ! $dari) {
            $this->catat('email', $ke, $judul, $isi, 'dilewati', 'Email belum diaktifkan', $acara);
            return FALSE;
        }

        $cfg = array('mailtype' => 'text', 'charset' => 'utf-8', 'newline' => "\r\n", 'crlf' => "\r\n");
        if ($p->get('smtp_host')) {
            $cfg += array(
                'protocol'    => 'smtp',
                'smtp_host'   => $p->get('smtp_host'),
                'smtp_port'   => (int) $p->get('smtp_port'),
                'smtp_user'   => $p->get('smtp_user'),
                'smtp_pass'   => $p->get('smtp_pass'),
                'smtp_crypto' => ((int) $p->get('smtp_port') === 465) ? 'ssl' : 'tls',
                'smtp_timeout' => 8,
            );
        } elseif ( ! empty($surat['host'])) {
            $cfg += array(
                'protocol'    => 'smtp',
                'smtp_host'   => $surat['host'],
                'smtp_port'   => (int) (isset($surat['port']) ? $surat['port'] : 587),
                'smtp_user'   => isset($surat['user']) ? $surat['user'] : '',
                'smtp_pass'   => isset($surat['pass']) ? $surat['pass'] : '',
                'smtp_crypto' => ! empty($surat['crypto']) ? $surat['crypto'] : NULL,
                'smtp_timeout' => (int) (isset($surat['timeout']) ? $surat['timeout'] : 8),
            );
        }

        $this->CI->load->library('email');
        $this->CI->email->initialize($cfg);
        $this->CI->email->clear();
        $this->CI->email->from($dari, $p->get('nama_brand'));
        $this->CI->email->to($ke);
        $this->CI->email->subject($judul);
        $this->CI->email->message($isi);

        $ok = @$this->CI->email->send(FALSE);
        $this->catat('email', $ke, $judul, $isi, $ok ? 'terkirim' : 'gagal',
            $ok ? NULL : 'Server email menolak', $acara);
        return $ok;
    }

    /* =========================================================== acara === */

    /** Pembayaran masuk: kabari pembeli dan penjual. */
    public function pesanan_dibayar(array $order)
    {
        $toko  = $this->toko($order['store_id']);
        $brand = $this->CI->pengaturan_lib->get('nama_brand');
        $lacak = site_url('checkout/done/' . $order['order_number'] . '/' . $order['access_token']);

        $pembeli = 'Halo ' . $order['customer_name'] . ', pembayaran pesanan '
            . $order['order_number'] . ' sebesar ' . rupiah($order['total']) . ' sudah kami terima.' . "\n\n"
            . ($toko ? $toko['name'] . ' akan segera memprosesnya. ' : '')
            . 'Pantau pesananmu di: ' . $lacak . "\n\n- " . $brand;

        $this->wa($order['customer_phone'], $pembeli, 'pesanan_dibayar');
        if ( ! empty($order['customer_email'])) {
            $this->email($order['customer_email'], 'Pembayaran ' . $order['order_number'] . ' diterima', $pembeli, 'pesanan_dibayar');
        }

        if ($toko && $toko['phone']) {
            $items = $this->CI->db->select('product_name, variant_name, qty')
                ->where('order_id', (int) $order['id'])->get('order_items')->result_array();
            $baris = array();
            foreach ($items as $i) {
                $baris[] = '- ' . (int) $i['qty'] . 'x ' . $i['product_name']
                    . ($i['variant_name'] ? ' (' . $i['variant_name'] . ')' : '');
            }

            $penjual = 'Pesanan baru sudah dibayar! ' . $order['order_number'] . "\n"
                . implode("\n", $baris) . "\n"
                . 'Total ' . rupiah($order['total']) . "\n"
                . 'Kirim ke: ' . $order['recipient_name'] . ', ' . $order['recipient_city'] . "\n\n"
                . 'Proses di: ' . site_url('seller/orders');
            $this->wa($toko['phone'], $penjual, 'pesanan_baru_toko');
        }
    }

    /** Status pesanan berubah - hanya status yang ditunggu pembeli. */
    public function status_pesanan(array $order, $baru, $catatan = NULL)
    {
        $teks = array(
            'confirmed'  => 'sedang diproses penjual',
            'delivering' => 'sedang dikirim',
            'delivered'  => 'sudah selesai. Terima kasih sudah berbelanja!',
            'cancelled'  => 'dibatalkan',
        );
        if ( ! isset($teks[$baru])) {
            return;
        }

        $isi = 'Pesanan ' . $order['order_number'] . ' ' . $teks[$baru] . '.'
            . ($catatan ? "\n" . $catatan : '') . "\n\n"
            . 'Detail: ' . site_url('checkout/done/' . $order['order_number'] . '/' . $order['access_token']);

        $this->wa($order['customer_phone'], $isi, 'status_' . $baru);
        if ( ! empty($order['customer_email'])) {
            $this->email($order['customer_email'], 'Pesanan ' . $order['order_number'] . ' ' . $teks[$baru], $isi, 'status_' . $baru);
        }
    }

    /** Stok produk menipis setelah terjual. */
    public function stok_menipis($store_id, array $produk)
    {
        $toko = $this->toko($store_id);
        if ( ! $toko || ! $produk) {
            return;
        }
        $baris = array();
        foreach ($produk as $p) {
            $baris[] = '- ' . $p['name'] . ': sisa ' . (int) $p['stock'];
        }
        $this->wa($toko['phone'], 'Stok menipis di ' . $toko['name'] . ":\n" . implode("\n", $baris)
            . "\n\nTambah stok di: " . site_url('seller'), 'stok_menipis');
    }

    /* ========================================================= bantuan === */

    public function rapikan_nomor($nomor)
    {
        $p = preg_replace('/[^0-9]/', '', (string) $nomor);
        if ($p === '') {
            return '';
        }
        if ($p[0] === '0') {
            return '62' . substr($p, 1);
        }
        return substr($p, 0, 2) === '62' ? $p : '62' . $p;
    }

    protected function toko($store_id)
    {
        return $this->CI->db->select('id, name, phone')
            ->where('id', (int) $store_id)->get('stores')->row_array();
    }

    protected function catat($kanal, $tujuan, $judul, $isi, $status, $galat, $acara)
    {
        if ( ! $this->CI->db->table_exists('notifikasi_keluar')) {
            return;
        }
        $debug = $this->CI->db->db_debug;
        $this->CI->db->db_debug = FALSE;
        $this->CI->db->insert('notifikasi_keluar', array(
            'kanal'  => $kanal,
            'tujuan' => mb_substr($tujuan, 0, 150),
            'judul'  => $judul ? mb_substr($judul, 0, 150) : NULL,
            'isi'    => $isi,
            'status' => $status,
            'galat'  => $galat,
            'acara'  => $acara,
        ));
        $this->CI->db->db_debug = $debug;
    }
}
