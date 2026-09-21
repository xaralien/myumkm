<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Duitku - integrasi Duitku POP untuk CodeIgniter 3.
 *
 * Simpan di: application/libraries/Duitku.php
 * Pakai    : $this->load->library('duitku');
 *
 * ALUR POP
 *   1. Server memanggil createInvoice -> dapat `reference`
 *   2. Halaman bayar memuat duitku.js
 *   3. JavaScript memanggil checkout.process(reference)
 *   4. Pembeli memilih metode DI DALAM popup Duitku
 *   5. Duitku mengirim callback ke server kita
 *
 * Metode pembayaran TIDAK lagi dipilih di sisi kita. Parameter
 * `paymentMethod` sengaja tidak dikirim - kalau dikirim, popup langsung
 * meloncat ke metode itu dan pembeli kehilangan pilihan.
 *
 * TIGA RUMUS SIGNATURE, semuanya berbeda:
 *
 *   createInvoice (POP) : sha256(merchantCode + timestamp + apiKey)
 *                         -> dikirim di HEADER x-duitku-signature
 *   callback masuk      : md5(merchantCode + amount + merchantOrderId + apiKey)
 *   cek status          : md5(merchantCode + merchantOrderId + apiKey)
 *
 * Yang pertama SHA256 biasa, bukan HMAC. Ini berbeda dari sebagian contoh
 * lama yang masih beredar di internet.
 */
class Duitku
{

    protected $CI;
    protected $cfg;
    protected $env;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->config->load('duitku', TRUE);
        $this->cfg = $this->CI->config->item('duitku', 'duitku');
        $this->env = $this->cfg['sandbox'] ? 'sandbox' : 'production';
    }

    public function is_sandbox()
    {
        return (bool) $this->cfg['sandbox'];
    }

    /** URL script popup, dipasang di halaman bayar. */
    public function js_url()
    {
        return $this->cfg['pop_js'][$this->env];
    }

    public function language()
    {
        return $this->cfg['pop_language'];
    }

    /* =====================================================================
     |  BUAT INVOICE
     |  @return array ['reference','payment_url'] atau ['error' => '...']
     | ================================================================== */
    public function create_invoice(array $order, array $items)
    {
        $timestamp = (string) round(microtime(TRUE) * 1000);   // milidetik

        // SHA256 biasa. Urutan: kode merchant, timestamp, api key.
        $signature = hash('sha256', $this->cfg['merchant_code'] . $timestamp . $this->cfg['api_key']);

        /* ---- itemDetails --------------------------------------------------
         | quantity SELALU 1, price = total baris (harga satuan x jumlah).
         |
         | Alasannya: dokumentasi Duitku tidak tegas apakah `price` berarti
         | harga SATUAN yang lalu dikalikan `quantity`, atau sudah berupa
         | total baris. Kita mengirim harga satuan, Duitku menjumlahkannya
         | sebagai total baris - begitu ada produk berjumlah lebih dari satu,
         | hasilnya berbeda dan muncul:
         |
         |     "Payment amount must be equal to the total item price"
         |
         | Dengan quantity 1 dan price sudah dikalikan, KEDUA tafsiran itu
         | menghasilkan angka yang sama. Jumlahnya jadi tidak mungkin
         | diperselisihkan. Jumlah barang dipindah ke nama supaya pembeli
         | tetap melihatnya di halaman pembayaran.
         | ------------------------------------------------------------------ */
        $item_details = array();
        $cek = 0;

        foreach ($items as $it) {
            $jml   = max(1, (int) $it['quantity']);
            $baris = ((int) $it['price']) * $jml;

            $item_details[] = array(
                'name'     => $this->potong($it['name'] . ($jml > 1 ? ' x' . $jml : ''), 50),
                'price'    => $baris,
                'quantity' => 1,
            );
            $cek += $baris;
        }

        // Ongkir wajib ikut sebagai baris tersendiri, kalau tidak jumlahnya
        // tidak akan pernah sama dengan paymentAmount.
        if ((int) $order['shipping_fee'] > 0) {
            $item_details[] = array(
                'name'     => 'Ongkos kirim',
                'price'    => (int) $order['shipping_fee'],
                'quantity' => 1,
            );
            $cek += (int) $order['shipping_fee'];
        }

        if ($cek !== (int) $order['total']) {
            // Selisihnya disebutkan supaya ketahuan bagian mana yang meleset,
            // bukan sekadar "tidak cocok".
            $pesan = 'Rincian item ' . $cek . ' tidak sama dengan total '
                . $order['total'] . ' (selisih ' . ($cek - (int) $order['total']) . ').';
            log_message('error', 'Duitku: ' . $pesan);
            return array('error' => $pesan);
        }

        $nama = $this->pisah_nama($order['customer_name']);

        $params = array(
            'paymentAmount'    => (int) $order['total'],
            'merchantOrderId'  => $order['order_number'],
            'productDetails'   => 'Pesanan ' . $order['order_number'],
            'additionalParam'  => '',
            'merchantUserInfo' => '',
            'customerVaName'   => $this->ascii_name($order['customer_name']),
            'email'            => $order['customer_email'] ?: 'noreply@example.com',
            'phoneNumber'      => $order['customer_phone'],
            'itemDetails'      => $item_details,
            'customerDetail'   => array(
                'firstName'   => $nama[0],
                'lastName'    => $nama[1],
                'email'       => $order['customer_email'] ?: 'noreply@example.com',
                'phoneNumber' => $order['customer_phone'],
            ),
            /* Alamat publik dipakai kalau diisi di config. Duitku menolak
               URL yang tidak bisa dijangkau dari internet, dan site_url()
               di XAMPP berisi http://localhost/... */
            'callbackUrl'      => $this->cfg['callback_url'] ?: site_url('payment/callback'),
            'returnUrl'        => $this->cfg['return_url']   ?: site_url('payment/finish'),
            'expiryPeriod'     => (int) $this->cfg['expiry_period'],
            // 'paymentMethod' sengaja TIDAK diisi -> pembeli memilih di popup
        );

        $res  = $this->post_pop($params, $timestamp, $signature);
        $body = $res['body'];

        if (isset($body['statusCode']) && $body['statusCode'] === '00' && ! empty($body['reference'])) {
            return array(
                'reference'   => $body['reference'],
                'payment_url' => isset($body['paymentUrl']) ? $body['paymentUrl'] : NULL,
            );
        }

        $pesan = isset($body['statusMessage']) ? $body['statusMessage']
            : ($res['raw'] ?: 'HTTP ' . $res['http']);

        /* Seluruh isi balasan ikut dicatat. Pesan singkat dari Duitku sering
           tidak menyebutkan parameter mana yang bermasalah - jawabannya ada
           di balasan mentah. Lihat application/logs/. */
        log_message('error', 'Duitku createInvoice gagal (HTTP ' . $res['http'] . '): '
            . $pesan . ' | balasan: ' . $res['raw']
            . ' | callbackUrl: ' . $params['callbackUrl']
            . ' | paymentAmount: ' . $params['paymentAmount']);

        return array('error' => $pesan, 'http' => $res['http'], 'raw' => $res['raw']);
    }

    /* =====================================================================
     |  VERIFIKASI CALLBACK
     |  Callback dikirim server Duitku sebagai POST form biasa (bukan JSON),
     |  dan rumusnya MD5 - berbeda total dari createInvoice di atas.
     | ================================================================== */
    public function valid_callback(array $post)
    {
        foreach (array('merchantCode', 'amount', 'merchantOrderId', 'signature') as $k) {
            if (empty($post[$k])) {
                return FALSE;
            }
        }
        if ($post['merchantCode'] !== $this->cfg['merchant_code']) {
            return FALSE;
        }

        $harus = md5(
            $post['merchantCode'] . $post['amount'] . $post['merchantOrderId'] . $this->cfg['api_key']
        );

        return hash_equals($harus, (string) $post['signature']);
    }

    /* =====================================================================
     |  CEK STATUS TRANSAKSI
     |  Jaring pengaman kalau callback telat atau tidak sampai.
     |  Endpoint ini masih di host lama (webapi), bukan host POP.
     | ================================================================== */
    public function check_transaction($order_number)
    {
        $signature = md5($this->cfg['merchant_code'] . $order_number . $this->cfg['api_key']);

        $res = $this->post_json(
            $this->cfg['webapi_url'][$this->env] . '/api/merchant/transactionStatus',
            array(
                'merchantCode'    => $this->cfg['merchant_code'],
                'merchantOrderId' => $order_number,
                'signature'       => $signature,
            )
        );
        return $res['body'];
    }

    /* ------------------------------------------------------------------ */

    protected function post_pop(array $params, $timestamp, $signature)
    {
        $body = json_encode($params);

        return $this->kirim($this->cfg['pop_url'][$this->env], $body, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
            'x-duitku-signature: ' . $signature,
            'x-duitku-timestamp: ' . $timestamp,
            'x-duitku-merchantcode: ' . $this->cfg['merchant_code'],
        ));
    }

    protected function post_json($url, array $params)
    {
        return $this->kirim($url, json_encode($params), array('Content-Type: application/json'));
    }

    protected function kirim($url, $body, array $headers)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 10,

            // Sertifikat TETAP diperiksa. Banyak contoh di internet memakai
            // CURLOPT_SSL_VERIFYPEER => FALSE; itu membuka celah penyadapan
            // pada lalu lintas pembayaran. Kalau di XAMPP muncul
            // "SSL certificate problem", unduh cacert.pem lalu arahkan
            // curl.cainfo di php.ini - jangan matikan verifikasinya.
            CURLOPT_SSL_VERIFYPEER => TRUE,
            CURLOPT_SSL_VERIFYHOST => 2,
        ));

        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === FALSE) {
            log_message('error', 'Duitku cURL error: ' . $err);
            return array('http' => 0, 'body' => array(), 'raw' => $err);
        }

        $json = json_decode($raw, TRUE);
        return array(
            'http' => $code,
            'body' => is_array($json) ? $json : array(),
            'raw'  => $raw,
        );
    }

    protected function potong($teks, $maks)
    {
        return function_exists('mb_substr')
            ? mb_substr($teks, 0, $maks)
            : substr($teks, 0, $maks);
    }

    protected function pisah_nama($nama)
    {
        $bagian = preg_split('/\s+/', trim($nama), 2);
        return array(
            $this->potong($bagian[0], 50),
            isset($bagian[1]) ? $this->potong($bagian[1], 50) : '-',
        );
    }

    /** Sebagian bank menolak karakter non-ASCII di layar konfirmasi. */
    protected function ascii_name($nama)
    {
        $bersih = preg_replace('/[^A-Za-z0-9 ]/', '', $nama);
        $bersih = trim(preg_replace('/\s+/', ' ', $bersih));
        return $bersih === '' ? 'Pelanggan' : substr($bersih, 0, 20);
    }
}
