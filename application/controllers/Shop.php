<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Shop - katalog produk dengan pencarian, filter, dan paginasi.
 * Simpan di: application/controllers/Shop.php
 *
 *   GET shop
 *   GET shop?q=mawar&category=Buket&min=200000&max=500000&sort=murah&page=2
 *
 * Filter memakai query string, bukan segmen URL. Alasannya: filter bisa
 * kosong dalam kombinasi apa pun, dan URL seperti /shop//Buket//murah/2
 * jadi rapuh serta tidak bisa di-bookmark dengan enak.
 */
class Shop extends CI_Controller
{
    const PER_PAGE = 12;  // kelipatan 12 -> baris penuh di 2, 3, dan 4 kolom

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('pagination', 'cart_lib', 'session', 'location_lib'));
        $this->load->model(array('product_model', 'region_model'));
        $this->load->helper(array('url', 'form', 'money'));
    }

    public function index()
    {
        // ---- Baca & bersihkan input -------------------------------------
        $f = array(
            'q' => trim((string) $this->input->get('q', TRUE)),
            'category' => trim((string) $this->input->get('category', TRUE)),
            'min' => $this->angka($this->input->get('min')),
            'max' => $this->angka($this->input->get('max')),
            'sort' => (string) $this->input->get('sort', TRUE),
            'store_id' => (int) $this->input->get('store'),
            'strict' => (bool) $this->input->get('strict'),
        );

        /* Wilayah pengunjung disuntikkan dari server, BUKAN dari query string.
           Kalau id wilayah boleh datang dari URL, orang bisa memalsukannya -
           dan yang lebih sering terjadi, link yang dibagikan akan membawa
           lokasi pengirimnya, bukan lokasi penerima link. */
        $lokasi = $this->location_lib->get();
        if ($lokasi) {
            $f['province_id'] = (int) $lokasi['province_id'];
            $f['regency_id'] = (int) $lokasi['regency_id'];
            $f['district_id'] = (int) $lokasi['district_id'];
        }

        // Kalau min lebih besar dari max, orang jelas keliru mengisi.
        // Tukar saja, jangan tampilkan hasil kosong yang membingungkan.
        if ($f['min'] !== NULL && $f['max'] !== NULL && $f['min'] > $f['max']) {
            $tukar = $f['min'];
            $f['min'] = $f['max'];
            $f['max'] = $tukar;
        }

        // Nilai sort yang tidak dikenal dikembalikan ke default.
        if (!array_key_exists($f['sort'], $this->product_model->sort_options())) {
            $f['sort'] = 'baru';
        }

        // ---- Hitung total dulu, baru tentukan halaman --------------------
        $total = $this->product_model->count_filtered($f);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));

        $page = (int) $this->input->get('page');
        if ($page < 1) {
            $page = 1;
        }
        if ($page > $pages) {
            $page = $pages;
        }  // ?page=999 -> halaman terakhir

        $offset = ($page - 1) * self::PER_PAGE;

        $dekat = $this->session->userdata('dekat');

        $f['radius'] = (int) $this->input->get('radius');
        if (!in_array($f['radius'], array(3, 5, 10, 25, 50), TRUE)) {
            $f['radius'] = 10;  // nilai di luar daftar dikembalikan ke bawaan
        }

        // Mode dekat hanya aktif kalau titiknya ada DAN pembeli memilihnya.
        $mode_dekat = $dekat && $this->input->get('dekat') === '1';

        if ($mode_dekat) {
            $f['dekat_lat'] = $dekat['lat'];
            $f['dekat_lng'] = $dekat['lng'];
        }

        $data = array(
            'products' => $this->product_model->search($f, self::PER_PAGE, $offset),
            'total' => $total,
            'page' => $page,
            'pagess' => $pages,
            'per_page' => self::PER_PAGE,
            'from' => $total ? $offset + 1 : 0,
            'to' => min($offset + self::PER_PAGE, $total),
            'f' => $f,
            'categories' => $this->product_model->categories(isset($f['province_id']) ? $f['province_id'] : NULL),
            'range' => $this->product_model->price_range(isset($f['province_id']) ? $f['province_id'] : NULL),
            'lokasi' => $lokasi,
            'sebaran' => $this->product_model->count_by_proximity($f),
            'sorts' => $this->product_model->sort_options(),
            'pagination' => $this->build_pagination($total, $page),
            'ada_filter' => ($f['q'] !== '' ||
                $f['category'] !== '' ||
                $f['min'] !== NULL ||
                $f['max'] !== NULL),
            'mode_dekat' => $mode_dekat,
            'radius' => $f['radius'],
            'punya_titik' => (bool) $dekat,
        );

        $data['pages'] = 'v_shop';
        $this->load->view('index', $data);
    }

    /**
     * '' dan input non-angka jadi NULL, bukan 0 - beda arti.
     */
    protected function angka($v)
    {
        if ($v === NULL || $v === '' || !is_numeric($v)) {
            return NULL;
        }
        return max(0, (int) $v);
    }

    protected function build_pagination($total, $page)
    {
        /*
         * CI3 Pagination.php (~baris 524) menimpa 'cur_page' dari config:
         *
         *     if ($this->page_query_string === TRUE)
         *         $this->cur_page = $CI->input->get($this->query_string_segment);
         *     ...
         *     if ( ! ctype_digit($this->cur_page) OR ...)
         *
         * Kalau URL tidak punya ?page=, input->get() mengembalikan NULL dan
         * ctype_digit(NULL) memicu deprecation di PHP 8.1+.
         *
         * Menuliskan nomor halaman yang sudah dijepit ke $_GET sekaligus
         * menyelesaikan dua hal: peringatannya hilang, dan CI membaca nomor
         * halaman yang benar (nilai 'cur_page' di config memang diabaikan).
         *
         * Aman terhadap link paginasi: reuse_query_string membuang segmen
         * 'page' sebelum menyusun ulang query string, jadi tidak ada ?page
         * ganda.
         */
        $_GET['page'] = (string) $page;

        $cfg = array(
            'base_url' => site_url('shop'),
            'total_rows' => $total,
            'per_page' => self::PER_PAGE,
            'cur_page' => $page,  // diabaikan CI saat page_query_string aktif,
            // lihat penulisan ke $_GET di atas
            // Link jadi ?page=2, bukan /shop/12 (offset). Lebih mudah dibaca,
            // dan reuse_query_string menjaga q/category/min/max/sort ikut terbawa.
            'page_query_string' => TRUE,
            'query_string_segment' => 'page',
            'use_page_numbers' => TRUE,
            'reuse_query_string' => TRUE,
            'num_links' => 2,
            'full_tag_open' => '<ul class="pagination">',
            'full_tag_close' => '</ul>',
            'first_link' => 'Awal',
            'last_link' => 'Akhir',
            'prev_link' => '&larr;',
            'next_link' => '&rarr;',
            'first_tag_open' => '<li class="page-item">',
            'first_tag_close' => '</li>',
            'last_tag_open' => '<li class="page-item">',
            'last_tag_close' => '</li>',
            'prev_tag_open' => '<li class="page-item">',
            'prev_tag_close' => '</li>',
            'next_tag_open' => '<li class="page-item">',
            'next_tag_close' => '</li>',
            'num_tag_open' => '<li class="page-item">',
            'num_tag_close' => '</li>',
            'cur_tag_open' => '<li class="page-item is-active"><span class="page-link">',
            'cur_tag_close' => '</span></li>',
            'attributes' => array('class' => 'page-link'),
        );

        $this->pagination->initialize($cfg);
        return $this->pagination->create_links();
    }
}
