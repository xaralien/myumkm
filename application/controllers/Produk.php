<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Produk - halaman detail produk.
 * Simpan di: application/controllers/Produk.php
 *
 *   GET produk/{slug-toko}/{slug-produk}
 *
 * KENAPA DUA SEGMEN
 * Sejak jadi marketplace, slug produk hanya unik di dalam satu toko
 * (uq_products_store_slug). Dua toko boleh sama-sama punya
 * "buket-mawar-merah", dan itu pasti terjadi. URL satu segmen akan
 * menampilkan produk toko yang salah, tanpa gejala apa pun.
 *
 * _remap dipakai supaya tidak perlu menambah baris di config/routes.php.
 * Tanpa itu, CodeIgniter membaca segmen kedua sebagai nama method -
 * /produk/bunga-batam-kota akan dicari sebagai method bernama
 * "bunga-batam-kota" lalu gagal 404.
 */
class Produk extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('cart_lib', 'session', 'location_lib'));
        $this->load->model('product_model');
        $this->load->helper(array('url', 'money'));
    }

    public function _remap($method, $params = array())
    {
        if (! $method) {
            show_404();
        }

        /* Bentuk baku: /produk/{slug-toko}/{slug-produk} */
        if (isset($params[0]) && $params[0] !== '') {
            return $this->tampil($method, $params[0]);
        }

        /* ---- URL lama satu segmen: /produk/{slug-produk} ---------------
         | Masih beredar dari tautan yang sudah dibagikan, dan dari halaman
         | yang belum diperbarui. Daripada 404, dialihkan ke bentuk baku.
         |
         | Pengalihan 301 (permanen) supaya mesin pencari mencatat alamat
         | yang benar dan tidak menyimpan dua alamat untuk satu produk. */
        $cocok = $this->product_model->cari_slug($method);

        if (count($cocok) === 1) {
            return redirect(
                'produk/' . $cocok[0]['store_slug'] . '/' . $cocok[0]['slug'],
                'location',
                301
            );
        }

        /* Lebih dari satu toko punya slug yang sama - tidak ada cara tahu
           yang mana yang dimaksud. Menebak berisiko menampilkan produk
           toko yang salah dengan harga yang salah, jadi lebih baik
           dikembalikan ke katalog dengan kata kuncinya. */
        if (count($cocok) > 1) {
            return redirect('shop?q=' . urlencode($cocok[0]['name']));
        }

        show_404();
    }

    protected function tampil($store_slug, $product_slug)
    {
        $p = $this->product_model->detail($store_slug, $product_slug);

        if (! $p) {
            show_404();
        }

        /* Kedekatan toko dengan lokasi pengunjung. Halaman detail bisa
           dibuka dari tautan yang dibagikan orang lain, jadi tokonya
           mungkin di provinsi berbeda - itu perlu diberitahukan sebelum
           pembeli terlanjur mengisi checkout. */
        $lokasi = $this->location_lib->get();
        $jarak  = NULL;

        if ($lokasi) {
            if ((int) $p['store_district_id'] === (int) $lokasi['district_id']) {
                $jarak = 1;
            } elseif ((int) $p['store_regency_id'] === (int) $lokasi['regency_id']) {
                $jarak = 2;
            } elseif ((int) $p['store_province_id'] === (int) $lokasi['province_id']) {
                $jarak = 3;
            } else {
                $jarak = 4;
            }
        }

        $data = array(
            'p'         => $p,
            'variants'  => $this->product_model->variants($p['id']),
            'addons'    => $this->product_model->addons_for_product($p['id']),
            'lainnya'   => $this->product_model->lain_dari_toko($p['store_id'], $p['id']),
            'lokasi'    => $lokasi,
            'jarak'     => $jarak,
            /* Semua jam berasal dari TOKO pemilik produk. Halaman ini bisa
               menampilkan produk toko mana pun, jadi satu nilai global
               justru akan menyesatkan pembeli.

               "Bisa kirim hari ini" diturunkan dari jam tutup dikurangi
               jeda persiapan, bukan dari kolom cutoff terpisah. */
            'same_day'  => date('H:i', time() + ((int) $p['store_jeda'] * 60))
                <= substr((string) $p['store_close'], 0, 5),
            'jam_siap'  => date('H:i', time() + ((int) $p['store_jeda'] * 60)),
            'jam_buka'  => substr((string) $p['store_open'], 0, 5),
            'jam_tutup' => substr((string) $p['store_close'], 0, 5),
            'wa_toko'   => $p['store_phone'],

            // Dipakai di <title> dan meta description.
            'judul'     => $p['name'] . ' - ' . $p['store_name'],
            'meta_desc' => $this->ringkas(
                $p['description'] ?: ($p['name'] . ' dari ' . $p['store_name']
                    . ', ' . $p['store_district']),
                155
            ),
        );

        $data['pages'] = 'v_produk';
        $this->load->view('index', $data);
    }

    protected function ringkas($teks, $maks)
    {
        $teks = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $teks)));
        if (function_exists('mb_strlen') && mb_strlen($teks) > $maks) {
            return rtrim(mb_substr($teks, 0, $maks - 1)) . '…';
        }
        return $teks;
    }
}
