<?php
defined('BASEPATH') or exit('No direct script access allowed');

/* =============================================================================
 |  KONFIGURASI DUITKU + TOKO
 |  Simpan di: application/config/duitku.php
 |  Dimuat dengan: $this->config->load('duitku', TRUE);
 |
 |  JANGAN commit file ini ke Git kalau sudah diisi kredensial asli.
 |  Tambahkan ke .gitignore, atau baca dari environment variable.
 | ========================================================================== */

// --- Kredensial dari portal Duitku ------------------------------------------
$config['duitku']['merchant_code'] = getenv('DUITKU_MERCHANT_CODE') ?: 'DS33852';
$config['duitku']['api_key']       = getenv('DUITKU_API_KEY')       ?: '8b24f26e74ce53317f01f5007dce04fb';

// TRUE = sandbox (untuk uji coba), FALSE = production (uang sungguhan)
$config['duitku']['sandbox'] = TRUE;

/* --- URL callback & return -------------------------------------------------
 | PENYEBAB PALING SERING "gagal membuat transaksi" DI LOCALHOST.
 |
 | Secara bawaan keduanya diambil dari site_url(), yang di XAMPP berisi
 | http://localhost/... - dan Duitku MENOLAK alamat yang tidak bisa
 | dijangkau dari internet. Pesan errornya sering samar, jadi kelihatannya
 | seperti masalah kunci API padahal bukan.
 |
 | Isi dua nilai di bawah dengan alamat publik apa pun yang sah selama
 | pengembangan (boleh alamat ngrok, boleh domain yang belum dipakai).
 | Callback-nya memang tidak akan sampai, tapi itu tidak masalah -
 | status tetap tertangkap oleh polling dan penyapu berkala.
 |
 | Kosongkan lagi setelah situsnya punya domain sendiri.
 | ------------------------------------------------------------------------ */
$config['duitku']['callback_url'] = getenv('DUITKU_CALLBACK_URL') ?: '';
$config['duitku']['return_url']   = getenv('DUITKU_RETURN_URL')   ?: '';

// Batas waktu bayar, dalam menit. 1440 = 24 jam.
$config['duitku']['expiry_period'] = 1440;

/* --- Alamat API & script POP ------------------------------------------------
 | POP memakai host yang BERBEDA dari API v2 lama:
 |   API v2 lama : sandbox.duitku.com/webapi      (masih dipakai cek status)
 |   POP         : api-sandbox.duitku.com         (buat invoice)
 |   Script popup: app-sandbox.duitku.com
 |
 | Signature POP: sha256(merchantCode + timestamp + apiKey) - SHA256 BIASA,
 | bukan HMAC, dan dikirim lewat header, bukan di dalam body.
 | ------------------------------------------------------------------------- */
$config['duitku']['pop_url'] = array(
  'sandbox'    => 'https://api-sandbox.duitku.com/api/merchant/createInvoice',
  'production' => 'https://api-prod.duitku.com/api/merchant/createInvoice',
);

$config['duitku']['pop_js'] = array(
  'sandbox'    => 'https://app-sandbox.duitku.com/lib/js/duitku.js',
  'production' => 'https://app-prod.duitku.com/lib/js/duitku.js',
);

// Host lama, masih dipakai untuk endpoint cek status transaksi.
$config['duitku']['webapi_url'] = array(
  'sandbox'    => 'https://sandbox.duitku.com/webapi',
  'production' => 'https://passport.duitku.com/webapi',
);

// Bahasa tampilan popup: 'id' atau 'en'
$config['duitku']['pop_language'] = 'id';

/* --- Pemeriksaan status tanpa callback --------------------------------------
 | Callback Duitku butuh URL yang bisa diakses dari internet. Di localhost
 | itu mustahil tanpa ngrok, jadi status ditanyakan sendiri ke Duitku
 | lewat endpoint transactionStatus.
 |
 | TIGA LAPIS, saling menutup lubang masing-masing:
 |   1. Polling di halaman  - jalan selama pembeli membuka halaman bayar
 |   2. Penyapu berkala     - untuk yang menutup tab setelah membayar
 |   3. Callback            - tetap hidup, jadi tinggal dipakai di produksi
 |
 | Lapis 2 yang paling penting: tanpa itu, pembeli yang bayar lewat VA
 | tiga jam kemudian tidak akan pernah tercatat lunas.
 | ------------------------------------------------------------------------ */

// Jeda polling di halaman (detik). Mulai cepat, lalu melambat.
$config['duitku']['poll_awal']  = 3;
$config['duitku']['poll_akhir'] = 12;

// Berhenti memantau setelah sekian detik, supaya tab yang ditinggal
// terbuka tidak menanyai Duitku sepanjang hari.
$config['duitku']['poll_maks_detik'] = 600;   // 10 menit

// Jarak minimum antar pemeriksaan untuk SATU pesanan, berapa pun banyaknya
// tab yang terbuka. Tanpa ini, membuka 5 tab berarti 5x permintaan.
$config['duitku']['jeda_cek_detik'] = 3;

/* Kunci untuk menjalankan penyapu lewat cron:
 |     curl "https://situsmu.com/payment/sweep?key=KUNCI"
 | atau di XAMPP, Task Scheduler memanggil URL yang sama.
 |
 | GANTI dengan nilai acak yang panjang. Tanpa kunci, siapa pun bisa
 | memicu ratusan permintaan ke Duitku dari luar. */
$config['duitku']['sweep_key'] = getenv('DUITKU_SWEEP_KEY') ?: 'GANTI_DENGAN_ACAK_PANJANG';

// Pesanan lebih tua dari ini berhenti disapu - invoice-nya pasti kedaluwarsa.
$config['duitku']['sweep_jam'] = 48;

/* =============================================================================
 |  PENGATURAN TOKO
 | ========================================================================== */

// $config['shop']['name'] = 'Toko Bunga';

// Ongkos kirim per kota. 'default' dipakai kalau kota tidak terdaftar.
// $config['shop']['shipping'] = array(
//   'default'   => 25000,
//   'Batam'     => 20000,
//   'Tanjungpinang' => 45000,
// );

// Gratis ongkir kalau subtotal >= nilai ini. Isi 0 untuk menonaktifkan.
// $config['shop']['free_shipping_above'] = 500000;

// Batas jam pesan untuk pengiriman hari ini (format 24 jam).
// $config['shop']['same_day_cutoff'] = 14;

/* --- Jam pengiriman ---------------------------------------------------------
 | Sejak memakai <input type="time">, pembeli bebas memilih jam - jadi
 | batasnya harus dijaga di sini, bukan lagi lewat daftar slot.
 | Format WAJIB "HH:MM" 24 jam dengan angka nol di depan. Selain enak
 | dibaca, format itu bisa dibandingkan langsung sebagai teks:
 | "09:30" < "14:00" hasilnya benar tanpa perlu diubah jadi angka. */
// $config['shop']['jam_buka']  = '08:00';
// $config['shop']['jam_tutup'] = '20:00';

/* Jeda minimum dari sekarang untuk pengiriman HARI INI. Bunga harus
   dirangkai dulu lalu diantar - tanpa jeda ini, pembeli bisa memesan
   pukul 14:00 untuk dikirim pukul 14:05. */
// $config['shop']['jeda_persiapan_menit'] = 120;

// Berapa hari ke depan pembeli boleh memilih tanggal.
// $config['shop']['max_days_ahead'] = 30;

// Nominal maksimum yang boleh COD. Di atas ini wajib bayar online.
// $config['shop']['cod_max_total'] = 1000000;

// Nomor WhatsApp toko (format 62...), untuk tombol bantuan di halaman sukses.
// $config['shop']['whatsapp'] = '628123456789';


/* =============================================================================
   KREDENSIAL LOKAL

   Kode merchant dan API key TIDAK disimpan di berkas ini lagi. Repo ini
   publik - apa pun yang di-commit bisa dibaca siapa saja, dan tetap
   tersimpan di riwayat git walau nanti dihapus.

   Isi di application/config/duitku_lokal.php (sudah masuk .gitignore).
   Salin dari duitku_lokal.example.php.
   ========================================================================== */
if (is_file(__DIR__ . '/duitku_lokal.php')) {
  include __DIR__ . '/duitku_lokal.php';
}
