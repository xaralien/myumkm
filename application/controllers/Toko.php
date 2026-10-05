<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Toko - halaman publik satu toko.
 * Simpan di: application/controllers/Toko.php
 *
 *   GET toko/{slug}            produk toko
 *   GET toko/{slug}?tab=ulasan ulasan toko
 *
 * Dibuat sebagai halaman sendiri, bukan katalog yang disaring: halaman toko
 * punya isinya sendiri (rating, jumlah produk, tombol chat) dan alamatnya
 * pantas dibagikan apa adanya.
 */
class Toko extends CI_Controller {

    const PER_HAL = 12;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('review_model'));
        $this->load->helper(array('url', 'form', 'money'));
        $this->load->library(array('auth_lib'));
    }

    public function index($slug = NULL)
    {
        $toko = $this->db->where('slug', $slug)->where('is_active', 1)
                         ->get('stores')->row_array();
        if ( ! $toko) {
            show_404();
        }

        // Keterangan wilayah untuk di bawah nama toko.
        $wil = $this->db
            ->select('d.name AS district_name, r.name AS regency_name, p.name AS province_name', FALSE)
            ->from('stores s')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->join('regencies r', 'r.id = s.regency_id', 'left')
            ->join('provinces p', 'p.id = s.province_id', 'left')
            ->where('s.id', $toko['id'])
            ->get()->row_array();

        $toko = array_merge($toko, (array) $wil);

        $tab = ($this->input->get('tab') === 'ulasan') ? 'ulasan' : 'produk';

        $data = array(
            'toko'      => $toko,
            'tab'       => $tab,
            'statistik' => $this->statistik($toko),
            'pages'     => 'v_toko',
        );

        if ($tab === 'ulasan') {
            $hal = max(1, (int) $this->input->get('page'));

            $opsi = array(
                'rating' => (int) $this->input->get('bintang') ?: NULL,
                'media'  => $this->input->get('media') === '1',
                'limit'  => 10,
                'offset' => ($hal - 1) * 10,
            );

            $data['ringkasan'] = $this->review_model->ringkasan('toko', $toko['id']);
            $data['ulasan']    = $this->review_model->daftar('toko', $toko['id'], $opsi);
            $data['media']     = $this->review_model->media('toko', $toko['id'], 8);
            $data['filter']    = $opsi;
            $data['hal']       = $hal;
            $data['hal_total'] = max(1, (int) ceil($this->review_model->hitung('toko', $toko['id'], $opsi) / 10));
        } else {
            $data = array_merge($data, $this->daftar_produk($toko));
        }

        $this->load->view('index', $data);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Angka di kepala halaman.
     *
     * Rating diambil dari kolom yang sudah disimpan, bukan dihitung ulang:
     * nilainya sudah diperbarui setiap ada ulasan baru, dan menghitung
     * AVG() di sini berarti menjumlahkan seluruh ulasan toko tiap kali
     * halamannya dibuka.
     */
    protected function statistik(array $toko)
    {
        return array(
            'produk' => (int) $this->db->where('store_id', $toko['id'])
                                       ->where('is_active', 1)
                                       ->count_all_results('products'),

            /* "Terjual" = pesanan yang sudah DIBAYAR, apa pun status
               pengirimannya. Yang ingin diketahui pembeli dari angka ini
               adalah "toko ini sudah pernah melayani berapa pembeli",
               bukan status kirimnya.

               Yang belum dibayar tidak dihitung: angkanya akan terlihat
               besar padahal uangnya belum ada, dan pembeli memakai angka
               ini untuk menilai apakah tokonya bisa dipercaya. */
            'terjual' => (int) $this->db->where('store_id', $toko['id'])
                                        ->where('payment_status', 'paid')
                                        ->count_all_results('orders'),

            'rating'  => (float) $toko['rating_avg'],
            'ulasan'  => (int) $toko['rating_count'],
        );
    }

    /** Produk toko ini, dengan saringan kategori dan urutan. */
    protected function daftar_produk(array $toko)
    {
        $kategori = trim((string) $this->input->get('kategori', TRUE));
        $urut     = $this->input->get('urut');
        $hal      = max(1, (int) $this->input->get('page'));

        /* Kategori yang BENAR-BENAR dipakai toko ini beserta jumlahnya.
           Menampilkan seluruh kategori marketplace di sini akan memberi
           banyak saringan yang hasilnya kosong. */
        $kategori_toko = $this->db
            ->select('c.id, c.name, c.slug, COUNT(p.id) AS jml', FALSE)
            ->from('categories c')
            ->join('products p', 'p.category_id = c.id AND p.store_id = ' . (int) $toko['id'] . ' AND p.is_active = 1', 'inner')
            ->group_by('c.id, c.name, c.slug')
            ->order_by('jml', 'DESC')
            ->get()->result_array();

        $this->db->from('products p')
                 ->join('categories c', 'c.id = p.category_id', 'left')
                 ->where('p.store_id', (int) $toko['id'])
                 ->where('p.is_active', 1);

        if ($kategori !== '') {
            $this->db->where('c.slug', $kategori);
        }

        // count_all_results(FALSE) menyimpan query-nya, jadi syarat di atas
        // tidak perlu ditulis dua kali untuk menghitung dan mengambil.
        $total = $this->db->count_all_results('', FALSE);

        switch ($urut) {
            case 'murah':  $this->db->order_by('p.price', 'ASC');  break;
            case 'mahal':  $this->db->order_by('p.price', 'DESC'); break;
            case 'rating': $this->db->order_by('p.rating_avg', 'DESC')
                                    ->order_by('p.rating_count', 'DESC'); break;
            default:       $this->db->order_by('p.created_at', 'DESC');
        }

        $produk = $this->db
            ->select('p.*, c.name AS category_name', FALSE)
            ->limit(self::PER_HAL, ($hal - 1) * self::PER_HAL)
            ->get()->result_array();

        return array(
            'produk'        => $produk,
            'kategori_toko' => $kategori_toko,
            'kategori'      => $kategori,
            'urut'          => $urut ?: 'baru',
            'total'         => $total,
            'hal'           => $hal,
            'hal_total'     => max(1, (int) ceil($total / self::PER_HAL)),
        );
    }
}
