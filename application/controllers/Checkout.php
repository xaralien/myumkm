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
    protected $toko;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('cart_lib', 'session', 'form_validation', 'duitku', 'auth_lib'));
        $this->load->model(array('order_model', 'region_model'));
        $this->load->helper(array('url', 'form', 'money'));

        // Pengaturan ongkir diambil dari TOKO pemilik keranjang.
        $this->toko = $this->cart_lib->store();
    }

    /* ------------------------------------------------------------ formulir */

    public function index()
    {
        if ($this->cart_lib->is_empty() || ! $this->toko) {
            return redirect('cart');
        }

        $subtotal = $this->cart_lib->subtotal();
        $t        = $this->toko;
        $akun     = $this->auth_lib->row();

        $data = array(
            'items'     => $this->cart_lib->items(),
            'subtotal'  => $subtotal,
            'toko'      => $t,
            'akun'      => $akun,
            'provinces' => $this->region_model->provinces(),

            /* Di marketplace, alamat pengiriman hampir selalu alamat pembeli
               sendiri - jadi alamat akun dipakai sebagai isian awal. (Di
               sistem bunga dulu sebaliknya: penerimanya orang lain.) */
            'wilayah_awal' => array(
                'province' => $akun ? (int) $akun['province_id'] : 0,
                'regency'  => $akun ? (int) $akun['regency_id']  : 0,
                'district' => $akun ? (int) $akun['district_id'] : 0,
            ),

            'free_above'  => (int) $t['gratis_ongkir_min'],
            'ongkir_json' => json_encode(array(
                'kecamatan'  => (int) $t['ongkir_kecamatan'],
                'kota'       => (int) $t['ongkir_kota'],
                'provinsi'   => $t['ongkir_provinsi'] === NULL ? NULL : (int) $t['ongkir_provinsi'],
                'toko_dis'   => (int) $t['district_id'],
                'toko_reg'   => (int) $t['regency_id'],
                'toko_prov'  => (int) $t['province_id'],
                'gratis_min' => (int) $t['gratis_ongkir_min'],
                'subtotal'   => $subtotal,
                'nama_toko'  => $t['name'],
                'nama_kota'  => $t['regency_name'],
                'nama_prov'  => $t['province_name'],
            )),
        );

        $data['pages'] = 'v_checkout';
        $this->load->view('index', $data);
    }

    /* -------------------------------------------------------- simpan pesanan */

    public function place()
    {
        if ($this->cart_lib->is_empty() || ! $this->toko) {
            return redirect('cart');
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

        $items    = $this->cart_lib->items();
        $subtotal = $this->cart_lib->subtotal();

        /* "Toko tidak melayani wilayah ini" BUKAN sama dengan "ongkir nol".
           Kalau dipaksa jadi angka, pesanan ke luar jangkauan lolos dengan
           ongkir 0 dan penjual baru sadar setelah harus mengirimnya. */
        $kirim = $this->cart_lib->shipping_fee($kec_id, $subtotal);
        if ( ! $kirim['ok']) {
            $this->session->set_flashdata('error', $kirim['pesan']);
            return $this->index();
        }

        $ongkir = (int) $kirim['ongkir'];

        $order = array(
            'store_id'          => $this->cart_lib->store_id(),
            'user_id'           => $this->auth_lib->id(),

            'customer_name'     => $this->input->post('customer_name', TRUE),
            'customer_phone'    => $this->normalize_phone($this->input->post('customer_phone', TRUE)),
            'customer_email'    => $this->input->post('customer_email', TRUE) ?: NULL,

            'recipient_name'        => $this->input->post('recipient_name', TRUE),
            'recipient_phone'       => $this->normalize_phone($this->input->post('recipient_phone', TRUE)),
            'recipient_address'     => $this->input->post('recipient_address', TRUE),
            'recipient_city'        => $tujuan['regency_name'],
            'recipient_province_id' => (int) $tujuan['province_id'],
            'recipient_regency_id'  => (int) $tujuan['regency_id'],
            'recipient_district_id' => (int) $tujuan['district_id'],

            // Catatan untuk penjual: warna, ukuran, patokan rumah, dsb.
            'recipient_notes'   => $this->input->post('recipient_notes', TRUE) ?: NULL,

            'subtotal'          => $subtotal,
            'shipping_fee'      => $ongkir,
            'total'             => $subtotal + $ongkir,
            'payment_method'    => 'duitku',
            'payment_status'    => 'unpaid',
            'order_status'      => 'pending',
        );

        $saved = $this->order_model->create($order, $items);
        if ( ! $saved) {
            $this->session->set_flashdata('error', 'Pesanan gagal disimpan. Coba lagi sebentar lagi.');
            return $this->index();
        }

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

        $done = 'checkout/done/' . $saved['order_number'] . '/' . $saved['access_token'];

        $inv = $this->duitku->create_invoice($saved, $this->baris_duitku($items));

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

        $v->set_rules('customer_name',  'Nama pemesan',   'required|trim|max_length[100]');
        $v->set_rules('customer_phone', 'Nomor WhatsApp', 'required|trim|callback_valid_phone');
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
        $data['pages'] = 'v_order_done';
        $this->load->view('index', $data);
    }

    /**
     * Pembeli menandai pesanan sudah diterima. Hanya dari status "Dikirim".
     *
     * Inilah yang menutup transaksi di marketplace - penjual tidak bisa
     * mengklaim "sudah sampai" sepihak tanpa ada yang mengonfirmasi.
     */
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
