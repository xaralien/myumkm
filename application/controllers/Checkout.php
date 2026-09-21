<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Checkout - simpan di application/controllers/Checkout.php
 *
 *   GET  checkout          formulir
 *   POST checkout/place    proses pesanan
 *   GET  checkout/done/{nomor}/{token}   halaman berhasil
 */
class Checkout extends CI_Controller
{

    protected $toko;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('cart_lib', 'session', 'form_validation', 'duitku'));
        $this->load->model(array('order_model', 'region_model'));
        $this->load->helper(array('url', 'form', 'money'));

        /* Pengaturan diambil dari TOKO pemilik keranjang, bukan dari
           config/duitku.php. Di marketplace, satu nilai untuk semua toko
           itu salah: jam kerja, ongkir, dan batas COD berbeda tiap toko.
           config sekarang hanya berisi kredensial Duitku. */
        $this->toko = $this->cart_lib->store();
    }

    /* ------------------------------------------------------- formulir --- */

    public function index()
    {
        if ($this->cart_lib->is_empty() || ! $this->toko) {
            return redirect('cart');
        }

        $subtotal = $this->cart_lib->subtotal();
        $t        = $this->toko;

        $data = array(
            'items'      => $this->cart_lib->items(),
            'subtotal'   => $subtotal,
            'toko'       => $t,

            // Provinsi dimuat di awal; kabupaten & kecamatan lewat AJAX,
            // karena data kecamatan Indonesia ada ~7.277 baris.
            'provinces'  => $this->region_model->provinces(),

            'jam_buka'   => $this->jam($t['open']),
            'jam_tutup'  => $this->jam($t['close']),
            'jam_min'    => $this->jam_paling_awal($this->input->post('delivery_date', TRUE)),
            'jeda_menit' => (int) $t['jeda_persiapan_menit'],
            'tgl_min'    => $this->earliest_date(),
            'tgl_max'    => date('Y-m-d', strtotime('+' . (int) $t['maks_hari_kedepan'] . ' days')),
            'batas_hari_ini' => $this->jam_paling_awal(date('Y-m-d')),

            // cod_max 0 berarti toko ini memang tidak menerima COD.
            // 'boleh_cod'  => (int) $t['cod_max'] > 0 && $subtotal <= (int) $t['cod_max'],
            // 'cod_max'    => (int) $t['cod_max'],
            'free_above' => (int) $t['gratis_ongkir_min'],

            // Dipakai JavaScript untuk menampilkan ongkir tanpa memuat ulang.
            'ongkir_json' => json_encode(array(
                'kecamatan'   => (int) $t['ongkir_kecamatan'],
                'kota'        => (int) $t['ongkir_kota'],
                'provinsi'    => $t['ongkir_provinsi'] === NULL ? NULL : (int) $t['ongkir_provinsi'],
                'toko_dis'    => (int) $t['district_id'],
                'toko_reg'    => (int) $t['regency_id'],
                'toko_prov'   => (int) $t['province_id'],
                'gratis_min'  => (int) $t['gratis_ongkir_min'],
                'subtotal'    => $subtotal,
                'nama_toko'   => $t['name'],
                'nama_kota'   => $t['regency_name'],
                'nama_prov'   => $t['province_name'],
            )),
        );

        $data['pages'] = 'v_checkout';
        $this->load->view('index', $data);
    }

    /** Kolom TIME MySQL keluar sebagai "HH:MM:SS" - dipendekkan jadi "HH:MM". */
    protected function jam($nilai)
    {
        return substr((string) $nilai, 0, 5);
    }

    /* -------------------------------------------------- jadwal kirim ---
     |  Tidak ada lagi kolom cutoff_jam. Batas pemesanan hari ini
     |  DITURUNKAN dari jam tutup dikurangi jeda persiapan:
     |
     |      hari ini masih bisa  <=>  sekarang + jeda <= jam tutup
     |
     |  Satu sumber kebenaran. Kalau cutoff disimpan terpisah, penjual yang
     |  memajukan jam tutup jadi 17:00 tapi lupa menurunkan cutoff akan
     |  menerima pesanan yang tidak mungkin dikerjakan.
     | ------------------------------------------------------------------ */

    /**
     * Jam paling awal yang boleh dipilih untuk tanggal tertentu.
     * @return string|NULL "HH:MM", NULL kalau tanggal itu sudah tidak mungkin
     */
    protected function jam_paling_awal($tanggal = NULL)
    {
        if (! $this->toko) {
            return NULL;
        }

        $buka  = $this->jam($this->toko['open']);
        $tutup = $this->jam($this->toko['close']);

        if ($tanggal !== date('Y-m-d')) {
            return $buka;   // hari lain: mulai dari jam buka
        }

        $siap = date('H:i', time() + ((int) $this->toko['jeda_persiapan_menit'] * 60));

        if ($siap > $tutup) {
            return NULL;    // sudah terlalu sore untuk hari ini
        }
        return max($buka, $siap);
    }

    /** Tanggal paling awal yang boleh dipilih. */
    protected function earliest_date()
    {
        if (! $this->toko) {
            return date('Y-m-d');
        }
        return $this->jam_paling_awal(date('Y-m-d')) === NULL
            ? date('Y-m-d', strtotime('+1 day'))
            : date('Y-m-d');
    }

    /* --------------------------------------------------------- proses --- */

    public function place()
    {
        if ($this->cart_lib->is_empty()) {
            return redirect('cart');
        }

        $this->set_rules();

        if ($this->form_validation->run() === FALSE) {
            return $this->index();
        }

        $bayar   = $this->input->post('payment_method', TRUE);
        $kec_id  = (int) $this->input->post('recipient_district_id');

        // Kecamatan diverifikasi ke database - id dari form bisa disunting.
        $tujuan = $this->region_model->district_full($kec_id);
        if (! $tujuan) {
            $this->session->set_flashdata('error', 'Kecamatan tujuan tidak valid.');
            return $this->index();
        }

        // ---- Semua nominal dihitung ULANG di sini. Yang dikirim browser
        //      diabaikan sepenuhnya. Ini penjagaan utama terhadap manipulasi harga.
        $items    = $this->cart_lib->items();
        $subtotal = $this->cart_lib->subtotal();

        /* shipping_fee() mengembalikan array, bukan angka. "Toko tidak
           melayani wilayah ini" BUKAN sama dengan "ongkir nol" - kalau
           dipaksa jadi angka, pesanan ke luar jangkauan akan lolos dengan
           ongkir 0 dan penjual baru sadar setelah harus mengantarnya. */
        $kirim = $this->cart_lib->shipping_fee($kec_id, $subtotal);

        if (! $kirim['ok']) {
            $this->session->set_flashdata('error', $kirim['pesan']);
            return $this->index();
        }

        $ongkir = (int) $kirim['ongkir'];
        $total  = $subtotal + $ongkir;

        // ---- Pemeriksaan yang tidak bisa ditangani form_validation
        $tanggal = $this->input->post('delivery_date', TRUE);
        if ($tanggal < $this->earliest_date()) {
            $this->session->set_flashdata(
                'error',
                'Tanggal pengiriman sudah tidak memungkinkan. Sisa waktu hari ini '
                    . 'tidak cukup untuk merangkai dan mengantar. Pilih tanggal berikutnya.'
            );
            return $this->index();
        }
        if ($bayar === 'cod' && $total > (int) $this->toko['cod_max']) {
            $this->session->set_flashdata(
                'error',
                'Pesanan di atas ' . rupiah($this->toko['cod_max'])
                    . ' tidak bisa COD. Silakan pilih pembayaran online.'
            );
            return $this->index();
        }

        $order = array(
            'store_id'          => $this->cart_lib->store_id(),
            'customer_name'     => $this->input->post('customer_name', TRUE),
            'customer_phone'    => $this->normalize_phone($this->input->post('customer_phone', TRUE)),
            'customer_email'    => $this->input->post('customer_email', TRUE) ?: NULL,

            'recipient_name'    => $this->input->post('recipient_name', TRUE),
            'recipient_phone'   => $this->normalize_phone($this->input->post('recipient_phone', TRUE)),
            'recipient_address' => $this->input->post('recipient_address', TRUE),
            // Nama kecamatan tetap disimpan sebagai teks supaya nota lama
            // tidak ikut berubah kalau data wilayah nanti diperbarui.
            'recipient_city'        => $tujuan['regency_name'],
            'recipient_province_id' => (int) $tujuan['province_id'],
            'recipient_regency_id'  => (int) $tujuan['regency_id'],
            'recipient_district_id' => (int) $tujuan['district_id'],
            'recipient_notes'   => $this->input->post('recipient_notes', TRUE) ?: NULL,

            'delivery_date'     => $tanggal,
            // Kolomnya masih bernama delivery_slot, isinya sekarang jam tunggal.
            'delivery_slot'     => $this->rapikan_jam($this->input->post('delivery_time', TRUE)),

            'card_message'      => $this->input->post('card_message', TRUE) ?: NULL,
            'card_from'         => $this->input->post('card_from', TRUE) ?: NULL,
            'is_anonymous'      => $this->input->post('is_anonymous') ? 1 : 0,
            'surprise_mode'     => $this->input->post('surprise_mode') ? 1 : 0,

            'subtotal'          => $subtotal,
            'shipping_fee'      => $ongkir,
            'total'             => $total,

            'payment_method'    => $bayar,
            'payment_status'    => 'unpaid',
            'order_status'      => 'pending',
        );

        $saved = $this->order_model->create($order, $items);
        if (! $saved) {
            $this->session->set_flashdata(
                'error',
                'Pesanan gagal disimpan. Coba lagi sebentar lagi.'
            );
            return $this->index();
        }

        /* ---- COD: selesai di sini. Keranjang dikosongkan, tunggu konfirmasi toko. */
        if ($bayar === 'cod') {
            $this->cart_lib->clear();
            return redirect('checkout/done/' . $saved['order_number'] . '/' . $saved['access_token']);
        }

        /* ---- Duitku POP -------------------------------------------------
         | Keranjang dikosongkan SETELAH invoice berhasil dibuat. Kalau
         | dikosongkan lebih dulu dan pembuatan invoice gagal, pembeli
         | kehilangan isi keranjangnya tanpa punya pesanan yang bisa dibayar.
         | ---------------------------------------------------------------- */
        $duitku_items = array();
        foreach ($items as $it) {
            $nama = $it['product_name'] . ($it['variant_name'] ? ' - ' . $it['variant_name'] : '');
            $duitku_items[] = array(
                'name'     => $nama,
                'price'    => $it['unit_price'],
                'quantity' => $it['qty'],
            );
        }

        $inv = $this->duitku->create_invoice($saved, $duitku_items);

        if (isset($inv['error']) || empty($inv['reference'])) {
            // Pesanan sudah tersimpan, jangan dihapus. Tandai gagal supaya
            // pembeli bisa mengulang pembayaran dari halaman pesanannya.
            $this->order_model->mark_failed(
                $saved['order_number'],
                'failed',
                isset($inv['error']) ? $inv['error'] : 'Gagal membuat transaksi'
            );
            $this->session->set_flashdata(
                'error',
                'Sistem pembayaran sedang bermasalah. Pesanan kamu tersimpan dengan nomor '
                    . $saved['order_number'] . '. Hubungi kami lewat WhatsApp untuk melanjutkan.'
            );
            return redirect('checkout/done/' . $saved['order_number'] . '/' . $saved['access_token']);
        }

        $this->order_model->set_duitku_info($saved['id'], $inv);
        $this->cart_lib->clear();

        // Popup dibuka di halaman tersendiri, bukan langsung di sini.
        // Halaman itu bisa dibuka ulang kalau pembeli menutup popupnya.
        return redirect('payment/pay/' . $saved['order_number'] . '/' . $saved['access_token']);
    }

    /* ----------------------------------------------------------- aturan --- */

    protected function set_rules()
    {
        $this->form_validation->set_rules('customer_name',  'Nama pemesan',  'required|trim|max_length[100]');
        $this->form_validation->set_rules('customer_phone', 'Nomor WhatsApp', 'required|trim|callback_valid_phone');
        $this->form_validation->set_rules('customer_email', 'Email',         'trim|valid_email|max_length[150]');

        $this->form_validation->set_rules('recipient_name',    'Nama penerima',  'required|trim|max_length[100]');
        $this->form_validation->set_rules('recipient_phone',   'HP penerima',    'required|trim|callback_valid_phone');
        $this->form_validation->set_rules('recipient_address', 'Alamat',         'required|trim|max_length[500]');
        /* Kecamatan penerima, bukan lagi nama kota dari daftar config.
           Daftar teks itu rapuh: "Batam" dan "Kota Batam" dianggap dua kota
           berbeda, dan kota yang tidak terdaftar diam-diam memakai tarif
           bawaan tanpa ada yang sadar. */
        $this->form_validation->set_rules('recipient_district_id', 'Kecamatan', 'required|integer');
        $this->form_validation->set_rules('recipient_notes',   'Catatan',        'trim|max_length[255]');

        $this->form_validation->set_rules('delivery_date', 'Tanggal kirim', 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');
        $this->form_validation->set_rules('delivery_time', 'Jam kirim',     'required|trim|callback_valid_time');

        $this->form_validation->set_rules('card_message', 'Pesan kartu', 'trim|max_length[500]');
        $this->form_validation->set_rules('card_from',    'Dari',        'trim|max_length[100]');

        $this->form_validation->set_rules('payment_method', 'Metode pembayaran', 'required|in_list[cod,duitku]');

        $this->form_validation->set_message('required',    '{field} wajib diisi.');
        $this->form_validation->set_message('regex_match', '{field} tidak valid. Contoh: 081234567890');
        $this->form_validation->set_message('valid_email', '{field} tidak valid.');
        $this->form_validation->set_message('in_list',     'Pilihan {field} tidak valid.');
        $this->form_validation->set_error_delimiters('<p class="field-error">', '</p>');
    }

    /**
     * Validasi jam kirim. Dipakai lewat 'callback_valid_time'.
     * WAJIB public - CI memanggilnya dari luar kelas validasi.
     */
    public function valid_time($str)
    {
        $jam = $this->rapikan_jam($str);

        if ($jam === NULL) {
            $this->form_validation->set_message(
                'valid_time',
                '{field} tidak valid. Contoh: 14:30'
            );
            return FALSE;
        }

        $buka  = $this->jam($this->toko['open']);
        $tutup = $this->jam($this->toko['close']);

        /* Perbandingan teks langsung. Aman KARENA formatnya "HH:MM" dengan
           nol di depan - "09:30" < "14:00" hasilnya benar. Kalau formatnya
           "9:30" tanpa nol, perbandingan ini akan salah total. Itu sebabnya
           rapikan_jam() menormalkan dulu. */
        if ($jam < $buka || $jam > $tutup) {
            $this->form_validation->set_message(
                'valid_time',
                'Pengiriman hanya antara ' . $buka . ' dan ' . $tutup . '.'
            );
            return FALSE;
        }

        /* Untuk pengiriman HARI INI, jamnya harus cukup jauh dari sekarang.
           Bunga dirangkai dulu lalu diantar - tanpa penjagaan ini pembeli
           bisa memesan pukul 14:00 untuk dikirim pukul 14:05. */
        $tanggal = $this->input->post('delivery_date', TRUE);

        if ($tanggal === date('Y-m-d')) {
            $paling_awal = $this->jam_paling_awal($tanggal);

            if ($paling_awal === NULL) {
                $this->form_validation->set_message(
                    'valid_time',
                    'Pengiriman hari ini sudah tidak memungkinkan - sisa waktunya '
                        . 'tidak cukup untuk merangkai dan mengantar. Pilih tanggal berikutnya.'
                );
                return FALSE;
            }
            if ($jam < $paling_awal) {
                $this->form_validation->set_message(
                    'valid_time',
                    'Untuk hari ini, pengiriman paling cepat pukul ' . $paling_awal . '.'
                );
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Normalkan jam jadi "HH:MM", atau NULL kalau tidak sah.
     *
     * Browser mengirim "HH:MM", TAPI sebagian mengirim "HH:MM:SS" kalau
     * atribut step bernilai kurang dari 60. Kalau detiknya tidak dibuang,
     * nilai "14:30:00" akan gagal dibandingkan dengan "20:00" dan ditolak
     * padahal masih dalam jam buka.
     */
    protected function rapikan_jam($str)
    {
        $str = trim((string) $str);

        if (! preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])(?::[0-5][0-9])?$/', $str, $m)) {
            return NULL;
        }
        return $m[1] . ':' . $m[2];
    }

    /**
     * Jam paling awal yang boleh dipilih untuk tanggal tertentu.
     * @return string|NULL "HH:MM", atau NULL kalau hari ini sudah lewat
     */
    // protected function jam_paling_awal($tanggal = NULL)
    // {
    //     if (! $this->toko) {
    //         return NULL;
    //     }

    //     $buka = $this->jam($this->toko['open']);

    //     if ($tanggal !== date('Y-m-d')) {
    //         return $buka;   // hari lain: mulai dari jam buka
    //     }

    //     $siap = date('H:i', time() + ((int) $this->toko['jeda_persiapan_menit'] * 60));

    //     if ($siap > $this->jam($this->toko['close'])) {
    //         return NULL;    // sudah terlalu sore untuk hari ini
    //     }
    //     return max($buka, $siap);
    // }

    /**
     * Aturan validasi nomor HP. Dipakai lewat 'callback_valid_phone'.
     * WAJIB public - CI memanggilnya dari luar kelas validasi.
     *
     * Kenapa callback, bukan regex_match: CI memecah string aturan pada
     * karakter '|', dan penjaganya gagal kalau regex mengandung '[' bersarang
     * seperti [0-9]. Aturan jadi terbelah dan errornya membingungkan.
     *
     * Bonus: nomor dinormalkan dulu, jadi 08xx, +62xx, dan 62xx dinilai
     * dengan panjang yang sama - hal yang tidak bisa dilakukan satu regex
     * tanpa jadi rumit.
     */
    public function valid_phone($str)
    {
        $p = $this->normalize_phone($str);

        // Setelah dinormalkan semua jadi 628xxxxxxxxx.
        // Nomor seluler Indonesia: 10-14 digit termasuk awalan.
        if (preg_match('/^628[0-9]{7,11}$/', $p)) {
            return TRUE;
        }

        $this->form_validation->set_message(
            'valid_phone',
            '{field} tidak valid. Contoh: 081234567890'
        );
        return FALSE;
    }

    /** 08xx / +62xx / 62xx -> semuanya jadi 62xx, supaya siap dipakai WhatsApp. */
    protected function normalize_phone($phone)
    {
        $p = preg_replace('/[^0-9]/', '', $phone);
        if (substr($p, 0, 1) === '0') {
            return '62' . substr($p, 1);          // 08xx -> 628xx
        }
        if (substr($p, 0, 2) === '62') {
            return $p;                            // sudah benar
        }
        return '62' . $p;                         // 8xx  -> 628xx
    }

    /* --------------------------------------------------------- selesai --- */

    public function done($order_number = NULL, $token = NULL)
    {
        $order = $this->order_model->find_by_token($order_number, $token);
        if (! $order) {
            show_404();
        }

        $data = array(
            'order'    => $order,
            'items'    => $this->order_model->items($order['id']),
            'whatsapp' => $order['store_id']
                ? ($this->db->select('phone')->where('id', $order['store_id'])
                    ->get('stores')->row_array()['phone'] ?? '')
                : '',
        );

        $data['pages'] = 'v_order_done';
        $this->load->view('index', $data);
    }
}
