<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Checkout - marketplace.
 * Simpan di: application/controllers/Checkout.php
 *
 *   GET  checkout                         formulir
 *   POST checkout/place                   simpan pesanan -> bayar
 *   GET  checkout/done/{nomor}/{token}    halaman pesanan
 *   POST checkout/terima/{nomor}/{token}  pembeli menandai pesanan diterima
 *
 * Tidak ada lagi tanggal & jam antar, kartu ucapan, atau mode kejutan -
 * semuanya khas toko bunga. Pembeli mengisi alamat, penjual memproses
 * dalam waktu_proses_hari lalu mengirim dengan nomor resi.
 */
class Checkout extends CI_Controller
{
    protected $langsung = FALSE;

    const KUNCI_VOUCHER = 'checkout_voucher';

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('cart_lib', 'session', 'form_validation', 'duitku', 'auth_lib', 'rincian_lib'));
        $this->load->model(array('order_model', 'region_model'));
        $this->load->helper(array('url', 'form', 'money'));

        /* Mode "beli sekarang": checkout memakai wadah terpisah berisi satu
           barang. Penandanya dikirim ulang lewat kolom tersembunyi di
           formulir, supaya tetap dikenali saat pesanan disimpan (POST). */
        $this->langsung = ($this->input->get_post('langsung') == '1');
        if ($this->langsung) {
            $this->cart_lib->mode('langsung');
        }
    }

    /* ------------------------------------------------------------ formulir */

    public function index()
    {
        $per_toko = $this->cart_lib->per_toko();
        if ( ! $per_toko) {
            return redirect($this->langsung ? 'shop' : 'cart');
        }

        $akun = $this->auth_lib->row();

        $data = array(
            'items'     => $this->cart_lib->items(),
            'akun'      => $akun,
            'langsung'  => $this->langsung,
            'provinces' => $this->region_model->provinces(),

            /* Di marketplace, alamat pengiriman hampir selalu alamat pembeli
               sendiri - jadi alamat akun dipakai sebagai isian awal. (Di
               sistem bunga dulu sebaliknya: penerimanya orang lain.) */
            'wilayah_awal' => array(
                'province' => $akun ? (int) $akun['province_id'] : 0,
                'regency'  => $akun ? (int) $akun['regency_id']  : 0,
                'district' => $akun ? (int) $akun['district_id'] : 0,
            ),

            // Rincian awal tanpa wilayah: daftar toko, barang, dan voucher.
            'rincian'   => $this->rincian(0, (array) $this->input->post('tanggal_kirim')),
            'kode_voucher' => $this->kode_voucher(),
        );

        /* Buku alamat, hanya untuk yang sudah login. Tamu tetap mengisi
           manual - memaksa mendaftar dulu di tengah membeli adalah cara
           tercepat kehilangan pesanan. */
        $data['alamat_saya'] = array();
        $data['alamat_pilih'] = NULL;

        $uid = $this->auth_lib->id();

        if ($uid) {
            $this->load->model('address_model');

            $data['alamat_saya'] = $this->address_model->daftar($uid);

            $pilih = (int) $this->input->get('alamat');

            $data['alamat_pilih'] = $pilih
                ? $this->address_model->milik($pilih, $uid)
                : $this->address_model->utama($uid);
        }

        $data['pages'] = 'v_checkout';
        $this->load->view('index', $data);
    }

    /**
     * Hitung ulang rincian.  POST checkout/hitung  [AJAX]
     *
     * Dipanggil setiap wilayah, tanggal kirim, atau voucher berubah. Angka
     * yang tampil di halaman berasal dari fungsi yang SAMA dengan yang
     * dipakai saat menyimpan pesanan.
     */
    public function hitung()
    {
        $r = $this->rincian((int) $this->input->post('district_id'), (array) $this->input->post('tanggal_kirim'));
        return $this->json($this->untuk_json($r));
    }

    /**
     * Pasang / lepas voucher.  POST checkout/voucher  [AJAX atau form]
     * aksi=pasang&kode=...  |  aksi=lepas&kode=...
     */
    public function voucher()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $this->load->library('voucher_lib');

        $kode  = $this->voucher_lib->rapikan_kode($this->input->post('kode', TRUE));
        $daftar = $this->kode_voucher();

        if ($this->input->post('aksi') === 'lepas') {
            $daftar = array_values(array_diff($daftar, array($kode)));
        } elseif ($kode !== '') {
            if ( ! $this->voucher_lib->cari($kode)) {
                return $this->json(array('ok' => FALSE, 'pesan' => 'Kode ' . $kode . ' tidak ditemukan.'), 422);
            }
            $daftar[] = $kode;
            $daftar = array_values(array_unique($daftar));
        }
        $this->session->set_userdata(self::KUNCI_VOUCHER, array_slice($daftar, 0, 5));

        $r = $this->rincian((int) $this->input->post('district_id'), (array) $this->input->post('tanggal_kirim'));
        $j = $this->untuk_json($r);

        // Voucher yang baru dipasang tapi ditolak langsung dilepas lagi,
        // supaya tidak terus muncul sebagai galat di setiap hitungan.
        if ($this->input->post('aksi') !== 'lepas' && $kode !== '' && isset($r['voucher'][$kode]) && ! $r['voucher'][$kode]['ok']) {
            $this->session->set_userdata(self::KUNCI_VOUCHER, array_values(array_diff($daftar, array($kode))));
            $j['ok'] = FALSE;
            $j['pesan'] = $r['voucher'][$kode]['pesan'];
            return $this->json($j, 422);
        }
        return $this->json($j);
    }

    protected function kode_voucher()
    {
        $v = $this->session->userdata(self::KUNCI_VOUCHER);
        return is_array($v) ? $v : array();
    }

    protected function rincian($district, array $tanggal = array())
    {
        $akun = $this->auth_lib->row();
        return $this->rincian_lib->hitung(
            $this->cart_lib->per_toko(),
            (int) $district,
            $this->kode_voucher(),
            array(
                'user_id' => $this->auth_lib->id(),
                'phone'   => $akun ? $akun['phone'] : NULL,
            ),
            $tanggal
        );
    }

    /** Rincian dalam bentuk yang aman dikirim ke browser (tanpa baris DB). */
    protected function untuk_json(array $r)
    {
        $kel = array();
        foreach ($r['kelompok'] as $sid => $k) {
            $kel[] = array(
                'store_id'       => (int) $sid,
                'nama'           => $k['toko']['name'],
                'subtotal'       => $k['subtotal'],
                'ongkir'         => $k['ongkir'],
                'ongkir_ok'      => $k['ongkir_ok'],
                'ongkir_pesan'   => $k['ongkir_pesan'],
                'diskon_toko'    => $k['diskon_toko'],
                'diskon_platform' => $k['diskon_platform'],
                'subsidi_ongkir' => $k['subsidi_ongkir'],
                'total'          => $k['total'],
                'preorder'       => $k['preorder']['ada'],
                'tanggal_min'    => $k['tanggal_min'],
                'dp_persen'      => $k['dp_persen'],
                'dp_jumlah'      => $k['dp_jumlah'],
                'galat'          => $k['galat'],
            );
        }
        return array(
            'ok'       => $r['ok'],
            'kelompok' => $kel,
            'subtotal' => $r['subtotal'],
            'ongkir'   => $r['ongkir'],
            'potongan' => $r['potongan'],
            'total'    => $r['total'],
            'bayar_sekarang' => $r['bayar_sekarang'],
            'voucher'  => $r['voucher'],
            'pesan'    => $r['pesan'],
            'csrf_name' => $this->security->get_csrf_token_name(),
            'csrf_hash' => $this->security->get_csrf_hash(),
        );
    }

    protected function json($data, $kode = 200)
    {
        return $this->output->set_status_header($kode)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    /* -------------------------------------------------------- simpan pesanan */

    public function place()
    {
        if ($this->cart_lib->is_empty()) {
            return redirect($this->langsung ? 'shop' : 'cart');
        }

        $this->set_rules();
        if ($this->form_validation->run() === FALSE) {
            return $this->index();
        }

        $kec_id = (int) $this->input->post('recipient_district_id');
        $tujuan = $this->region_model->district_full($kec_id);
        if ( ! $tujuan) {
            $this->session->set_flashdata('error', 'Kecamatan tujuan tidak valid.');
            return $this->index();
        }

        $r = $this->rincian($kec_id, (array) $this->input->post('tanggal_kirim'));

        /* Voucher yang ditolak (kedaluwarsa sejak dipasang, kuota habis)
           TIDAK diam-diam dilewati - pembeli harus tahu harganya berubah. */
        foreach ($r['voucher'] as $kd => $v) {
            if ( ! $v['ok']) {
                $r['ok'] = FALSE;
                $r['pesan'][] = $v['pesan'];
            }
        }
        if ( ! $r['ok']) {
            $this->session->set_flashdata('error', implode(' ', array_unique($r['pesan'])) ?: 'Periksa lagi isian checkout.');
            return redirect('checkout' . ($this->langsung ? '?langsung=1' : ''));
        }

        $akun  = $this->auth_lib->row();
        $phone = $this->normalize_phone($this->input->post('customer_phone', TRUE) ?: $this->input->post('recipient_phone', TRUE));

        $dasar = array(
            'user_id'           => $this->auth_lib->id(),

            /* Data pemesan diambil dari penerima kalau kolomnya tidak
               dikirim. Kolom ini tetap terisi di database karena dipakai
               halaman lacak, notifikasi, dan invoice Duitku - dibiarkan
               kosong berarti pesanan tanpa identitas pemesan sama sekali. */
            'customer_name'     => $this->input->post('customer_name', TRUE)
                                    ?: $this->input->post('recipient_name', TRUE),
            'customer_phone'    => $phone,
            'customer_email'    => $this->input->post('customer_email', TRUE)
                                    ?: ($akun ? $akun['email'] : NULL),

            'recipient_name'        => $this->input->post('recipient_name', TRUE),
            'recipient_phone'       => $this->normalize_phone($this->input->post('recipient_phone', TRUE)),
            'recipient_address'     => $this->input->post('recipient_address', TRUE),
            'recipient_city'        => $tujuan['regency_name'],
            'recipient_province_id' => (int) $tujuan['province_id'],
            'recipient_regency_id'  => (int) $tujuan['regency_id'],
            'recipient_district_id' => (int) $tujuan['district_id'],

            // Catatan untuk penjual: warna, ukuran, patokan rumah, dsb.
            'recipient_notes'   => $this->input->post('recipient_notes', TRUE) ?: NULL,

            'payment_method'    => 'duitku',
            'payment_status'    => 'unpaid',
            'order_status'      => 'pending',
        );

        $kolom_order = array_flip($this->db->list_fields('orders'));
        $this->load->library('voucher_lib');

        /* Semua pesanan (satu per toko) dan pemakaian vouchernya disimpan
           dalam SATU transaksi: kalau kuota voucher ternyata baru saja habis
           direbut pembeli lain, tidak ada pesanan setengah jadi. */
        $this->db->trans_begin();
        $tersimpan = array();
        $gagal = NULL;
        $grup = bin2hex(random_bytes(8));   // penanda checkout untuk kuota voucher

        foreach ($r['kelompok'] as $sid => $k) {
            $order = $dasar + array(
                'store_id'        => (int) $sid,
                'subtotal'        => $k['subtotal'],
                'shipping_fee'    => $k['ongkir'],
                'diskon_toko'     => $k['diskon_toko'],
                'diskon_platform' => $k['diskon_platform'],
                'subsidi_ongkir'  => $k['subsidi_ongkir'],
                'voucher_toko'    => $k['voucher_toko'] ? $k['voucher_toko']['kode'] : NULL,
                'voucher_platform' => $k['voucher_platform'] ? $k['voucher_platform']['kode'] : NULL,
                'total'           => $k['total'],
                'tanggal_kirim'   => $k['tanggal_kirim'],
                'dp_jumlah'       => $k['dp_jumlah'],
            );
            $order = array_intersect_key($order, $kolom_order);

            $saved = $this->order_model->create($order, array_values($k['items']));
            if ( ! $saved) {
                $gagal = 'Pesanan gagal disimpan. Coba lagi sebentar lagi.';
                break;
            }

            foreach (array('voucher_toko' => 'diskon_toko', 'voucher_platform' => 'diskon_platform') as $vk => $dk) {
                if ($k[$vk] && $k[$dk] > 0
                    && ! $this->voucher_lib->pakai($k[$vk], $saved['id'], $k[$dk], $dasar['user_id'], $phone, $grup)) {
                    $gagal = 'Kuota voucher ' . $k[$vk]['kode'] . ' baru saja habis. Lepas vouchernya lalu coba lagi.';
                    break 2;
                }
            }
            $tersimpan[] = $saved;
        }

        if ($gagal || $this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', $gagal ?: 'Pesanan gagal disimpan. Coba lagi sebentar lagi.');
            return redirect('checkout' . ($this->langsung ? '?langsung=1' : ''));
        }
        $this->db->trans_commit();

        /* Keranjang dikosongkan DI SINI, begitu pesanan tersimpan - bukan
           menunggu invoice Duitku berhasil dibuat.

           Dulu keranjang sengaja dibiarkan kalau pembuatan invoice gagal,
           karena halaman pesanan tidak punya tombol bayar untuk pesanan tanpa
           invoice. Akibatnya pembeli melihat barang yang sama masih di
           keranjang padahal sudah jadi pesanan - dan kalau checkout lagi,
           terbentuk pesanan ganda. Sekarang halaman pesanan selalu punya
           tombol "Bayar sekarang" selama belum lunas, dan Payment::pay
           membuat ulang invoice-nya. Keranjang tidak perlu ditahan lagi. */
        $this->cart_lib->clear();
        $this->session->unset_userdata(self::KUNCI_VOUCHER);

        $orders = array();
        foreach ($tersimpan as $t) {
            $orders[] = $this->order_model->get_by_number($t['order_number']);
        }

        /* Beberapa toko, atau ada uang muka pre-order: satu TAGIHAN yang
           mencakup semuanya. Satu toko dibayar penuh: alur lama. */
        $pakai_tagihan = count($orders) > 1 || ! empty($orders[0]['dp_jumlah']);

        if ($pakai_tagihan) {
            $this->load->library('tagihan_lib');
            $tg = $this->tagihan_lib->buat(count($orders) > 1 ? 'grup' : 'dp', $orders);
            if ( ! $tg) {
                $this->session->set_flashdata('error', 'Pesanan tersimpan, tapi tagihan gagal dibuat. Buka pesananmu untuk membayar.');
                return redirect('checkout/done/' . $orders[0]['order_number'] . '/' . $orders[0]['access_token']);
            }
            return redirect('payment/tagihan/' . $tg['kode'] . '/' . $tg['access_token']);
        }

        $saved = $orders[0];
        $done = 'checkout/done/' . $saved['order_number'] . '/' . $saved['access_token'];

        $inv = $this->duitku->create_invoice($saved, $this->baris_duitku(array_values(reset($r['kelompok'])['items'])));

        if (isset($inv['error']) || empty($inv['reference'])) {
            $this->order_model->mark_failed(
                $saved['order_number'],
                'failed',
                isset($inv['error']) ? $inv['error'] : 'Gagal membuat transaksi'
            );
            $this->session->set_flashdata('error',
                'Pesanan tersimpan, tapi halaman pembayaran belum bisa dibuka. '
                . 'Tekan "Bayar sekarang" untuk mencoba lagi.');
            return redirect($done);
        }

        $this->order_model->set_duitku_info($saved['id'], $inv);

        return redirect('payment/pay/' . $saved['order_number'] . '/' . $saved['access_token']);
    }

    /**
     * Rincian untuk Duitku: quantity SELALU 1, price = total baris.
     * Kalau dikirim harga satuan x jumlah, Duitku kadang menafsirkan price
     * sebagai total dan menolak dengan "Payment amount must be equal to the
     * total item price".
     */
    protected function baris_duitku(array $items)
    {
        $out = array();
        foreach ($items as $it) {
            $out[] = array(
                'name'     => $it['product_name']
                              . ($it['variant_name'] ? ' - ' . $it['variant_name'] : '')
                              . ((int) $it['qty'] > 1 ? ' (x' . (int) $it['qty'] . ')' : ''),
                'price'    => (int) $it['line_total'],
                'quantity' => 1,
            );
        }
        return $out;
    }

    protected function set_rules()
    {
        $v = $this->form_validation;

        /* Kolom "Data pemesan" dinonaktifkan di v_checkout.php, jadi tiga
           nilai ini tidak lagi dikirim formulir - aturannya TIDAK boleh
           mewajibkannya. Kalau tetap diwajibkan, setiap checkout ditolak
           validasi dan pembeli dipantulkan kembali ke formulir tanpa tahu
           sebabnya, karena kolom yang salah pun tidak terlihat.

           Kalau blok itu diaktifkan lagi, kembalikan aturan required-nya. */
        $v->set_rules('customer_name',  'Nama pemesan',   'trim|max_length[100]');
        $v->set_rules('customer_phone', 'Nomor WhatsApp', 'trim');
        $v->set_rules('customer_email', 'Email',          'trim|valid_email|max_length[150]');

        $v->set_rules('recipient_name',        'Nama penerima', 'required|trim|max_length[100]');
        $v->set_rules('recipient_phone',       'HP penerima',   'required|trim|callback_valid_phone');
        $v->set_rules('recipient_address',     'Alamat',        'required|trim|max_length[500]');
        $v->set_rules('recipient_district_id', 'Kecamatan',     'required|integer');
        $v->set_rules('recipient_notes',       'Catatan',       'trim|max_length[255]');

        $v->set_message('required',    '{field} wajib diisi.');
        $v->set_message('valid_email', '{field} tidak valid.');
        $v->set_message('max_length',  '{field} terlalu panjang.');
        $v->set_error_delimiters('<p class="field-error">', '</p>');
    }

    /** Callback validasi. WAJIB public - dipanggil dari luar kelas. */
    public function valid_phone($str)
    {
        if (preg_match('/^628[0-9]{7,11}$/', $this->normalize_phone($str))) {
            return TRUE;
        }
        $this->form_validation->set_message('valid_phone', '{field} tidak valid. Contoh: 081234567890');
        return FALSE;
    }

    protected function normalize_phone($phone)
    {
        $p = preg_replace('/[^0-9]/', '', (string) $phone);
        if (substr($p, 0, 1) === '0')  { return '62' . substr($p, 1); }
        if (substr($p, 0, 2) === '62') { return $p; }
        return '62' . $p;
    }

    /* ------------------------------------------------------- halaman pesanan */

    public function done($order_number = NULL, $token = NULL)
    {
        $order = $this->order_model->find_by_token($order_number, $token);
        if ( ! $order) {
            show_404();
        }

        $toko = $order['store_id']
            ? $this->db->select('name, phone, waktu_proses_hari')
                       ->where('id', (int) $order['store_id'])->get('stores')->row_array()
            : NULL;

        $data = array(
            'order'    => $order,
            'items'    => $this->order_model->items($order['id']),
            'toko'     => $toko,
            'whatsapp' => $toko ? $toko['phone'] : '',
        );
        /* Status refund untuk ditampilkan, dan apakah tombolnya perlu
           muncul. Hanya pesanan yang sudah dibayar dan belum selesai -
           setelah barang diterima, keluhan diselesaikan lewat chat dulu. */
        $this->load->model('refund_model');

        $data['refund'] = $this->refund_model->terakhir($order['id']);
        $data['bisa_refund'] = ($order['payment_status'] === 'paid')
            && in_array($order['order_status'], array('pending', 'confirmed', 'preparing', 'delivering'), TRUE);

        $data['sengketa'] = NULL;
        $data['sengketa_pesan'] = array();
        $data['boleh_sengketa'] = FALSE;
        if ($this->db->table_exists('sengketa')) {
            $this->load->model('sengketa_model');
            $data['sengketa'] = $this->sengketa_model->aktif($order['id']);
            $data['sengketa_pesan'] = $data['sengketa'] ? $this->sengketa_model->pesan($data['sengketa']['id']) : array();
            $data['boleh_sengketa'] = $this->sengketa_model->boleh_dibuka($order);
        }

        $data['pages'] = 'v_order_done';
        $this->load->view('index', $data);
    }

    /**
     * Pembeli menandai pesanan sudah diterima. Hanya dari status "Dikirim".
     *
     * Inilah yang menutup transaksi di marketplace - penjual tidak bisa
     * mengklaim "sudah sampai" sepihak tanpa ada yang mengonfirmasi.
     */
    /**
     * Pembeli mengajukan pengembalian dana.
     * POST checkout/refund/{nomor}/{token}
     */
    public function refund($order_number = NULL, $token = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order = $this->order_model->get_by_number($order_number);

        if ( ! $order || ! hash_equals((string) $order['access_token'], (string) $token)) {
            show_404();
        }

        $this->load->model('refund_model');
        $this->load->library('auth_lib');

        $hasil = $this->refund_model->ajukan(
            $order,
            $this->input->post('alasan', TRUE),
            $this->auth_lib->id()
        );

        $this->session->set_flashdata($hasil['ok'] ? 'sukses' : 'error', $hasil['pesan']);

        return redirect('checkout/done/' . $order_number . '/' . $token);
    }

    /**
     * Pembeli membatalkan pesanannya sendiri.
     * POST checkout/batal/{nomor}/{token}
     *
     * POST, bukan tautan: tautan pembatalan bisa terpicu pratinjau tautan
     * di aplikasi chat, dan pesanan orang batal tanpa dia menyentuh apa pun.
     */
    public function batal($order_number = NULL, $token = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order = $this->order_model->get_by_number($order_number);

        /* Token dibandingkan dengan hash_equals, bukan '==': perbandingan
           biasa berhenti di karakter pertama yang berbeda, dan selisih
           waktunya bisa dipakai menebak token huruf demi huruf. */
        if ( ! $order || ! hash_equals((string) $order['access_token'], (string) $token)) {
            show_404();
        }

        $hasil = $this->order_model->batalkan(
            $order, 'customer',
            trim((string) $this->input->post('alasan', TRUE))
        );

        $this->session->set_flashdata($hasil['ok'] ? 'sukses' : 'error', $hasil['pesan']);

        return redirect('checkout/done/' . $order_number . '/' . $token);
    }

    /**
     * Bayar sisa pre-order.  POST checkout/lunasi/{nomor}/{token}
     * Membuat (atau memakai ulang) tagihan pelunasan, lalu ke halaman bayar.
     */
    public function lunasi($order_number = NULL, $token = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $order = $this->order_model->find_by_token($order_number, $token);
        if ( ! $order) {
            show_404();
        }
        $done = 'checkout/done/' . $order['order_number'] . '/' . $order['access_token'];

        if (empty($order['dp_jumlah']) || ! empty($order['lunas_at'])
            || $order['payment_status'] !== 'paid' || $order['order_status'] === 'cancelled') {
            $this->session->set_flashdata('error', 'Pesanan ini tidak punya sisa pembayaran.');
            return redirect($done);
        }

        $this->load->library('tagihan_lib');
        $tg = $this->tagihan_lib->pelunasan_aktif($order['id']);
        if ($tg && $tg['status'] === 'failed') {
            $this->tagihan_lib->buat_invoice($tg);
            $tg = $this->tagihan_lib->ambil($tg['kode']);
        }
        if ( ! $tg) {
            $tg = $this->tagihan_lib->buat('pelunasan', array($order));
        }
        if ( ! $tg || $tg['status'] !== 'unpaid') {
            $this->session->set_flashdata('error', 'Tagihan pelunasan belum bisa dibuat. Coba lagi sebentar lagi.');
            return redirect($done);
        }
        return redirect('payment/tagihan/' . $tg['kode'] . '/' . $tg['access_token']);
    }

    /**
     * Buka sengketa / tanggapi sengketa.
     * POST checkout/sengketa/{nomor}/{token}       alasan, tuntutan, jumlah, cerita
     * POST checkout/sengketa_balas/{nomor}/{token} isi
     */
    public function sengketa($order_number = NULL, $token = NULL)
    {
        $order = $this->pesanan_post($order_number, $token);
        $this->load->model('sengketa_model');
        $done = 'checkout/done/' . $order['order_number'] . '/' . $order['access_token'];
        if ( ! $this->sengketa_model->boleh_dibuka($order)) {
            $this->session->set_flashdata('error', 'Sengketa tidak bisa dibuka untuk pesanan ini.');
            return redirect($done);
        }
        $h = $this->sengketa_model->buka($order, (array) $this->input->post(NULL, TRUE));
        $this->session->set_flashdata($h['ok'] ? 'sukses' : 'error', $h['pesan']);
        return redirect($done . '#sengketa');
    }

    public function sengketa_balas($order_number = NULL, $token = NULL)
    {
        $order = $this->pesanan_post($order_number, $token);
        $this->load->model('sengketa_model');
        $g = $this->sengketa_model->aktif($order['id']);
        if ($g && in_array($g['status'], array('terbuka', 'ditinjau'), TRUE)) {
            $this->sengketa_model->kirim($g['id'], 'pembeli', $this->input->post('isi', TRUE));
        }
        return redirect('checkout/done/' . $order['order_number'] . '/' . $order['access_token'] . '#sengketa');
    }

    protected function pesanan_post($nomor, $token)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $order = $this->order_model->find_by_token($nomor, $token);
        if ( ! $order) {
            show_404();
        }
        return $order;
    }

    public function terima($order_number = NULL, $token = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $order = $this->order_model->find_by_token($order_number, $token);
        if ( ! $order) {
            show_404();
        }

        $done = 'checkout/done/' . $order['order_number'] . '/' . $order['access_token'];

        /* Syarat status ada di dalam UPDATE, bukan cuma dicek di PHP. Kalau
           penjual membatalkan di detik yang sama, update ini tidak mengenai
           baris apa pun - pesanan batal tidak diam-diam jadi "Selesai". */
        $this->db->where('id', (int) $order['id'])
                 ->where('order_status', 'delivering')
                 ->update('orders', array(
                     'order_status' => 'delivered',
                     'completed_at' => date('Y-m-d H:i:s'),
                 ));

        if ($this->db->affected_rows() < 1) {
            $this->session->set_flashdata('error', 'Status pesanan sudah berubah. Muat ulang halaman.');
            return redirect($done);
        }

        $this->load->model('chat_model');
        $this->chat_model->sistem($order['id'], 'Pembeli mengonfirmasi pesanan sudah diterima.');

        $this->session->set_flashdata('sukses', 'Terima kasih! Pesanan ditandai selesai.');
        return redirect($done);
    }
}
