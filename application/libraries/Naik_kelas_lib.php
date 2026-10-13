<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Naik_kelas_lib - skor "Naik Kelas" toko, lencana, dan langkah awal.
 *
 * Simpan di: application/libraries/Naik_kelas_lib.php
 *
 * Skor dihitung dari data yang SUDAH ada (profil, produk, ulasan, pesanan,
 * pembukuan, legalitas, akademi) - tidak ada angka yang diisi tangan dan
 * tidak ada tabel tambahan. Setiap kriteria membawa saran dan tautan ke
 * halaman tempat memperbaikinya, jadi penjual tahu PERSIS langkah berikutnya.
 *
 * Level:
 *   0-39   Pemula      - baru mulai berjualan online
 *   40-69  Berkembang  - toko lengkap & mulai rutin terjual
 *   70-100 Unggul      - legal, terpercaya, tercatat rapi (siap KUR)
 */
class Naik_kelas_lib
{
    protected $CI;
    protected $_cache = array();

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function level($skor)
    {
        if ($skor >= 70) {
            return array('kode' => 'unggul', 'nama' => 'Unggul', 'ikon' => '🏆', 'berikut' => NULL, 'batas' => 100);
        }
        if ($skor >= 40) {
            return array('kode' => 'berkembang', 'nama' => 'Berkembang', 'ikon' => '🌿', 'berikut' => 'Unggul', 'batas' => 70);
        }
        return array('kode' => 'pemula', 'nama' => 'Pemula', 'ikon' => '🌱', 'berikut' => 'Berkembang', 'batas' => 40);
    }

    /**
     * @return array ['skor', 'level', 'kriteria' => [...], 'saran' => [3 terdekat]]
     */
    public function skor($store_id)
    {
        $store_id = (int) $store_id;
        if (isset($this->_cache[$store_id])) {
            return $this->_cache[$store_id];
        }
        $db = $this->CI->db;
        $s  = $db->where('id', $store_id)->get('stores')->row_array();
        if ( ! $s) {
            return NULL;
        }

        $produk_aktif = $db->where('store_id', $store_id)->where('is_active', 1)->count_all_results('products');
        $tanpa_desk   = $db->where('store_id', $store_id)->where('is_active', 1)
            ->group_start()->where('description IS NULL', NULL, FALSE)->or_where('description', '')->group_end()
            ->count_all_results('products');
        $lunas = $db->where('store_id', $store_id)->where('payment_status', 'paid')
            ->where('order_status !=', 'cancelled')->count_all_results('orders');
        $batal_toko = $db->where('store_id', $store_id)->where('payment_status', 'paid')
            ->where('order_status', 'cancelled')->count_all_results('orders');
        $ulang = count($db->select('customer_phone')->where('store_id', $store_id)->where('payment_status', 'paid')
            ->where('order_status !=', 'cancelled')->group_by('customer_phone')->having('COUNT(*) >', 1)
            ->get('orders')->result_array());

        $ulasan = (int) $s['rating_count'];
        $dibalas = $db->table_exists('reviews')
            ? $db->where('store_id', $store_id)->where('balasan IS NOT NULL', NULL, FALSE)->count_all_results('reviews') : 0;

        $dok = array();
        if ($db->table_exists('store_documents')) {
            foreach ($db->where('store_id', $store_id)->get('store_documents')->result_array() as $d) {
                $dok[$d['jenis']] = $d['status'];
            }
        }
        $kas30 = $db->table_exists('kas')
            ? $db->where('store_id', $store_id)->where('tanggal >=', date('Y-m-d', strtotime('-30 days')))->count_all_results('kas') : 0;
        $modul = $db->table_exists('akademi_progres')
            ? $db->where('store_id', $store_id)->count_all_results('akademi_progres') : 0;

        $k = array();
        $tambah = function ($kelompok, $judul, $ok, $poin, $saran, $tautan) use (&$k) {
            $k[] = compact('kelompok', 'judul', 'ok', 'poin', 'saran', 'tautan');
        };

        // ---- Profil (20) --------------------------------------------------
        $tambah('Profil', 'Foto/logo toko', ! empty($s['avatar']), 4, 'Pasang foto atau logo toko.', 'seller/profile');
        $tambah('Profil', 'Deskripsi toko', mb_strlen((string) $s['description']) >= 30, 4, 'Ceritakan tokomu minimal 1-2 kalimat.', 'seller/profile');
        $tambah('Profil', 'Titik lokasi di peta', $s['latitude'] !== NULL, 4, 'Tandai lokasi toko supaya muncul di "Toko terdekat".', 'seller/profile');
        $tambah('Profil', 'Rekening pencairan', ! empty($s['bank_nomor']), 4, 'Isi rekening supaya uang hasil penjualan bisa dicairkan.', 'seller/pendapatan');
        $tambah('Profil', 'Disetujui admin', (bool) $s['is_active'], 4, 'Tunggu persetujuan admin (biasanya 1x24 jam).', 'seller/profile');

        // ---- Produk (15) --------------------------------------------------
        $tambah('Produk', 'Minimal 5 produk tayang', $produk_aktif >= 5, 8, 'Tambah produk sampai minimal 5 - bisa sekaligus lewat Excel.', 'seller/massal');
        $tambah('Produk', 'Semua produk punya deskripsi', $produk_aktif > 0 && $tanpa_desk === 0, 7, 'Lengkapi deskripsi produk (bisa dibantu AI).', 'seller');

        // ---- Penjualan & layanan (25) -------------------------------------
        $tambah('Penjualan', '10 pesanan lunas', $lunas >= 10, 8, 'Bagikan produk ke grup WhatsApp dan pasang QR toko di tempat usaha.', 'seller/qr');
        $tambah('Penjualan', 'Ada pembeli yang kembali', $ulang >= 1, 5, 'Beri voucher untuk pelanggan lama supaya kembali belanja.', 'seller/voucher');
        $tambah('Layanan', 'Rating 4,5 dari minimal 5 ulasan', $ulasan >= 5 && (float) $s['rating_avg'] >= 4.5, 6, 'Kirim pesanan rapi dan cepat, lalu minta pembeli memberi ulasan.', 'seller/ulasan');
        $tambah('Layanan', 'Membalas ulasan pembeli', $ulasan > 0 && $dibalas >= ceil($ulasan * 0.8), 3, 'Balas minimal 8 dari 10 ulasan.', 'seller/ulasan');
        $tambah('Layanan', 'Jarang membatalkan pesanan', $lunas > 0 && $batal_toko <= max(1, floor($lunas * 0.05)), 3, 'Jaga stok tetap benar supaya tidak membatalkan pesanan.', 'seller');

        // ---- Legalitas (25) ------------------------------------------------
        $tambah('Legalitas', 'NIB terverifikasi', ($dok['nib'] ?? '') === 'terverifikasi', 12, 'Urus NIB gratis di OSS, lalu unggah di menu Legalitas.', 'seller/legalitas');
        $tambah('Legalitas', 'P-IRT atau Halal terverifikasi',
            ($dok['pirt'] ?? '') === 'terverifikasi' || ($dok['halal'] ?? '') === 'terverifikasi', 13,
            'Untuk produk pangan: urus P-IRT di Dinas Kesehatan atau sertifikat Halal gratis (SEHATI).', 'seller/legalitas');

        // ---- Keuangan & belajar (15) --------------------------------------
        $tambah('Keuangan', 'Rutin mencatat pembukuan', $kas30 >= 5, 8, 'Catat minimal 5 uang masuk/keluar dalam sebulan.', 'seller/pembukuan');
        $tambah('Belajar', 'Selesaikan 3 modul Akademi', $modul >= 3, 7, 'Belajar 5 menit per hari di Akademi UMKM.', 'seller/akademi');

        $skor = 0;
        foreach ($k as $x) {
            if ($x['ok']) {
                $skor += $x['poin'];
            }
        }

        // Saran: kriteria yang belum terpenuhi, yang poinnya paling besar dulu.
        $belum = array_values(array_filter($k, function ($x) { return ! $x['ok']; }));
        usort($belum, function ($a, $b) { return $b['poin'] - $a['poin']; });

        return $this->_cache[$store_id] = array(
            'skor'     => min(100, $skor),
            'level'    => $this->level($skor),
            'kriteria' => $k,
            'saran'    => array_slice($belum, 0, 3),
            'dokumen'  => $dok,
        );
    }

    /**
     * Lencana di halaman toko: level (mulai Berkembang) + dokumen yang
     * sudah diverifikasi admin. Format: [[kelas_css, teks], ...]
     */
    public function lencana_toko($store_id)
    {
        $s = $this->skor($store_id);
        if ( ! $s) {
            return array();
        }
        $out = array();
        if ($s['level']['kode'] !== 'pemula') {
            $out[] = array($s['level']['kode'] === 'unggul' ? 'lencana-emas' : 'lencana-hijau',
                           $s['level']['ikon'] . ' Toko ' . $s['level']['nama']);
        }
        $nama = array('nib' => 'NIB', 'pirt' => 'P-IRT', 'halal' => 'Halal');
        foreach ($nama as $j => $l) {
            if (($s['dokumen'][$j] ?? '') === 'terverifikasi') {
                $out[] = array('lencana-hijau', $l . ' ✓');
            }
        }
        return $out;
    }

    /**
     * Langkah awal "Toko siap jualan". Selesai semua = panduan disembunyikan.
     * @return array ['langkah' => [...], 'selesai' => int, 'total' => int]
     */
    public function langkah_awal($store_id)
    {
        $db = $this->CI->db;
        $s = $db->where('id', (int) $store_id)->get('stores')->row_array();
        $produk = $db->where('store_id', (int) $store_id)->count_all_results('products');

        $modul = function ($slug) use ($db) {
            if ( ! $db->table_exists('akademi_modul')) {
                return NULL;
            }
            $m = $db->select('slug')->where('slug', $slug)->where('is_active', 1)->get('akademi_modul')->row_array();
            return $m ? 'seller/akademi/modul/' . $m['slug'] : NULL;
        };

        $l = array(
            array('Pasang foto/logo & deskripsi toko', ! empty($s['avatar']) && ! empty($s['description']), 'seller/profile',
                  'Pembeli lebih percaya toko yang jelas wajah dan ceritanya.', NULL),
            array('Atur ongkos kirim & titik lokasi', $s['latitude'] !== NULL && ((int) $s['ongkir_kecamatan'] > 0 || (int) $s['ongkir_kota'] > 0),
                  'seller/profile', 'Tentukan tarif antar ke kecamatan, kota, dan luar kota.', NULL),
            array('Tambah produk pertama', $produk > 0, 'seller/form',
                  'Foto dari HP sudah cukup. Tombol AI bisa menuliskan deskripsinya.', $modul('foto-produk-pakai-hp')),
            array('Isi rekening pencairan', ! empty($s['bank_nomor']), 'seller/pendapatan',
                  'Ke rekening inilah hasil penjualanmu dikirim.', NULL),
            array('Pasang balasan cepat chat', ! $db->table_exists('chat_templates')
                  || $db->where('store_id', (int) $store_id)->count_all_results('chat_templates') > 0, 'seller/template',
                  'Jawab pertanyaan pembeli dalam satu ketukan.', $modul('membalas-chat-pembeli')),
        );

        $out = array();
        $selesai = 0;
        foreach ($l as $x) {
            $out[] = array('judul' => $x[0], 'ok' => (bool) $x[1], 'tautan' => $x[2], 'ket' => $x[3], 'video' => $x[4]);
            $selesai += $x[1] ? 1 : 0;
        }
        return array('langkah' => $out, 'selesai' => $selesai, 'total' => count($out));
    }
}
