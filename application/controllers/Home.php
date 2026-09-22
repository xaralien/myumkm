<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Home - beranda marketplace UMKM.
 * Simpan di: application/controllers/Home.php
 *
 * Kalau controller beranda kamu bernama lain (Welcome.php, dll), pindahkan
 * isi index() ke sana - atau set $route['default_controller'] = 'home'.
 */
class Home extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('session', 'location_lib', 'cart_lib'));
        $this->load->model('product_model');
        $this->load->helper(array('url', 'money'));
    }

    public function index()
    {
        $lokasi = $this->location_lib->get();

        /* Produk: filter wilayah yang sama dengan katalog, supaya yang tampil
           di beranda memang bisa dikirim ke lokasi pengunjung. Kalau beranda
           menampilkan produk dari provinsi lain, pembeli baru tahu tidak
           bisa dikirim setelah sampai di checkout. */
        $f = array('sort' => 'baru');
        if ($lokasi) {
            $f['district_id'] = (int) $lokasi['district_id'];
            $f['regency_id']  = (int) $lokasi['regency_id'];
            $f['province_id'] = (int) $lokasi['province_id'];
        }
        $products = $this->product_model->search($f, 8, 0);

        $data = array(
            'lokasi'     => $lokasi,
            'products'   => $products,

            // Produk pertama jadi sorotan di panel hero - foto sungguhan,
            // bukan gambar dekorasi yang tidak bisa dibeli.
            'unggulan'   => $products ? $products[0] : NULL,

            'categories' => $this->kategori_teratas(6),
            'toko_dekat' => $this->toko_dekat($lokasi, 3),
            'wa_admin'   => $this->config->item('wa_admin'),
            'brand'      => $this->config->item('nama_brand') ?: 'Nama Brand',
        );

        $data['pages'] = 'v_home';
        $this->load->view('index', $data);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Kategori dengan produk terbanyak. Kategori kosong tetap ikut kalau
     * kurang dari $n, supaya susunan grid tidak bolong di awal peluncuran.
     */
    protected function kategori_teratas($n)
    {
        /* Kolom icon & keterangan datang dari migrasi 15_kategori_umkm.sql.
           Kalau migrasi itu belum dijalankan, beranda tetap tampil - hanya
           memakai ikon umum - alih-alih mati karena "Unknown column". */
        $tambahan = $this->db->field_exists('icon', 'categories')
            ? 'c.icon, c.keterangan'
            : 'NULL AS icon, NULL AS keterangan';

        return $this->db
            ->select('c.id, c.name, c.slug, ' . $tambahan . ', COUNT(p.id) AS jml', FALSE)
            ->from('categories c')
            ->join('products p', 'p.category_id = c.id AND p.is_active = 1', 'left')
            ->group_by('c.id')
            ->order_by('jml', 'DESC')
            ->order_by('c.id', 'ASC')
            ->limit((int) $n)
            ->get()->result_array();
    }

    /**
     * Toko terdekat.
     *
     * Dua cara, tergantung data yang tersedia:
     *   - pembeli pernah mengizinkan deteksi lokasi  -> jarak sungguhan (km)
     *   - hanya memilih wilayah dari dropdown        -> urutan kecamatan/kota
     *
     * Hanya toko yang punya produk aktif - toko kosong di beranda cuma
     * membuat pembeli mengklik lalu kecewa.
     */
    protected function toko_dekat($lokasi, $n)
    {
        $titik = $this->session->userdata('dekat');

        if ($titik && method_exists($this->product_model, 'toko_sekitar')) {
            $toko = $this->product_model->toko_sekitar($titik['lat'], $titik['lng'], 25, 12);
            $toko = array_values(array_filter($toko, function ($t) {
                return (int) $t['jml_produk'] > 0;
            }));
            $toko = array_slice($toko, 0, $n);

            foreach ($toko as &$t) {
                $t['label_jarak'] = number_format((float) $t['jarak_km'], 1, ',', '.') . ' km';
            }
            unset($t);

            return $this->lengkapi_foto($toko);
        }

        $this->db
            ->select('s.id, s.name, s.slug, s.avatar,
                      d.name AS district_name, r.name AS regency_name,
                      s.district_id, s.regency_id,
                      (SELECT COUNT(*) FROM products p
                        WHERE p.store_id = s.id AND p.is_active = 1) AS jml_produk', FALSE)
            ->from('stores s')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->join('regencies r', 'r.id = s.regency_id', 'left')
            ->where('s.is_active', 1)
            ->having('jml_produk >', 0);

        if ($lokasi) {
            $dis = (int) $lokasi['district_id'];
            $reg = (int) $lokasi['regency_id'];

            $this->db->where('s.province_id', (int) $lokasi['province_id'])
                     ->order_by("CASE WHEN s.district_id = {$dis} THEN 1
                                      WHEN s.regency_id  = {$reg} THEN 2
                                      ELSE 3 END", '', FALSE);
        }

        $toko = $this->db->order_by('jml_produk', 'DESC')
                         ->limit((int) $n)
                         ->get()->result_array();

        foreach ($toko as &$t) {
            if ( ! $lokasi) {
                $t['label_jarak'] = NULL;
            } elseif ((int) $t['district_id'] === (int) $lokasi['district_id']) {
                $t['label_jarak'] = 'Kecamatanmu';
            } elseif ((int) $t['regency_id'] === (int) $lokasi['regency_id']) {
                $t['label_jarak'] = 'Kotamu';
            } else {
                $t['label_jarak'] = 'Provinsimu';
            }
        }
        unset($t);

        return $this->lengkapi_foto($toko);
    }

    /** Tiga foto produk terbaru per toko, untuk deretan kecil di kartunya. */
    protected function lengkapi_foto(array $toko)
    {
        foreach ($toko as &$t) {
            $t['foto'] = array_column(
                $this->db->select('image')
                         ->where('store_id', (int) $t['id'])
                         ->where('is_active', 1)
                         ->where('image IS NOT NULL', NULL, FALSE)
                         ->order_by('id', 'DESC')
                         ->limit(3)
                         ->get('products')->result_array(),
                'image'
            );
        }
        unset($t);

        return $toko;
    }
}
