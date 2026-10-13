<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pembeli_model - wishlist, ikuti toko, terlaris, dan rekomendasi.
 * Simpan di: application/models/Pembeli_model.php
 *
 * Semua daftar produk di sini memakai syarat yang SAMA dengan katalog:
 * produk aktif, toko aktif, stok belum habis. Produk yang tidak bisa
 * dibeli tidak boleh muncul di rekomendasi.
 */
class Pembeli_model extends CI_Model
{
    /* ========================================================= wishlist === */

    public function favorit($user_id, $product_id)
    {
        return (bool) $this->db->where('user_id', (int) $user_id)
            ->where('product_id', (int) $product_id)
            ->count_all_results('wishlists');
    }

    /** @return bool status SESUDAH ditekan: TRUE = sekarang difavoritkan */
    public function tukar_favorit($user_id, $product_id)
    {
        if ($this->favorit($user_id, $product_id)) {
            $this->db->where('user_id', (int) $user_id)
                ->where('product_id', (int) $product_id)->delete('wishlists');
            return FALSE;
        }
        // INSERT IGNORE: dua klik cepat tidak menghasilkan galat kunci ganda.
        $this->db->query('INSERT IGNORE INTO wishlists (user_id, product_id) VALUES (?, ?)',
                         array((int) $user_id, (int) $product_id));
        return TRUE;
    }

    public function daftar_favorit($user_id)
    {
        /* Produk yang sudah tidak dijual TETAP ditampilkan, ditandai tidak
           tersedia - menghilangkannya diam-diam membuat pembeli mengira
           favoritnya terhapus. */
        return $this->db
            ->select($this->kolom_kartu() . ',
                      (p.is_active = 1 AND s.is_active = 1 AND (p.stock IS NULL OR p.stock > 0)) AS tersedia,
                      w.created_at AS disimpan_at', FALSE)
            ->from('wishlists w')
            ->join('products p', 'p.id = w.product_id')
            ->join('stores s', 's.id = p.store_id')
            ->join('categories cat', 'cat.id = p.category_id', 'left')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->where('w.user_id', (int) $user_id)
            ->order_by('w.created_at', 'DESC')
            ->get()->result_array();
    }

    public function jumlah_favorit($product_id)
    {
        return (int) $this->db->where('product_id', (int) $product_id)->count_all_results('wishlists');
    }

    /* ======================================================= ikuti toko === */

    public function mengikuti($user_id, $store_id)
    {
        return (bool) $this->db->where('user_id', (int) $user_id)
            ->where('store_id', (int) $store_id)
            ->count_all_results('store_follows');
    }

    public function tukar_ikuti($user_id, $store_id)
    {
        if ($this->mengikuti($user_id, $store_id)) {
            $this->db->where('user_id', (int) $user_id)
                ->where('store_id', (int) $store_id)->delete('store_follows');
            return FALSE;
        }
        $this->db->query('INSERT IGNORE INTO store_follows (user_id, store_id) VALUES (?, ?)',
                         array((int) $user_id, (int) $store_id));
        return TRUE;
    }

    public function jumlah_pengikut($store_id)
    {
        return (int) $this->db->where('store_id', (int) $store_id)->count_all_results('store_follows');
    }

    public function toko_diikuti($user_id)
    {
        return $this->db
            ->select('s.id, s.name, s.slug, s.avatar, s.rating_avg, s.rating_count,
                      d.name AS district_name, r.name AS regency_name, f.created_at AS sejak,
                      (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id AND p.is_active = 1) AS jml_produk', FALSE)
            ->from('store_follows f')
            ->join('stores s', 's.id = f.store_id')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->join('regencies r', 'r.id = s.regency_id', 'left')
            ->where('f.user_id', (int) $user_id)
            ->where('s.is_active', 1)
            ->order_by('f.created_at', 'DESC')
            ->get()->result_array();
    }

    /** Produk terbaru dari toko-toko yang diikuti. */
    public function baru_dari_langganan($user_id, $limit = 8)
    {
        $this->dasar_katalog();
        return $this->db
            ->join('store_follows f', 'f.store_id = p.store_id')
            ->where('f.user_id', (int) $user_id)
            ->order_by('p.id', 'DESC')
            ->limit((int) $limit)
            ->get()->result_array();
    }

    /* ============================================= terlaris & rekomendasi === */

    /**
     * Terlaris. Kalau pengunjung sudah memilih lokasi, toko di provinsinya
     * didahulukan - "terlaris di sekitarmu" lebih berguna daripada terlaris
     * se-Indonesia yang belum tentu bisa dikirim.
     */
    public function terlaris($lokasi = NULL, $limit = 8)
    {
        $this->dasar_katalog();
        $this->db->where('p.terjual_count >', 0);
        if ($lokasi && ! empty($lokasi['province_id'])) {
            $this->db->order_by('(s.province_id = ' . (int) $lokasi['province_id'] . ')', 'DESC', FALSE);
        }
        return $this->db->order_by('p.terjual_count', 'DESC')
            ->order_by('p.rating_avg', 'DESC')
            ->limit((int) $limit)
            ->get()->result_array();
    }

    /**
     * Rekomendasi sederhana tanpa mesin pembelajaran: kategori yang pernah
     * dibeli atau difavoritkan, dikurangi produk yang sudah dibeli, diurut
     * dari yang paling laku dan paling dekat. Mudah dijelaskan ke pembeli
     * ("karena kamu suka Makanan & Minuman") dan murah dijalankan.
     *
     * @return array ['alasan' => string|NULL, 'produk' => array]
     */
    public function rekomendasi($user_id, $lokasi = NULL, $limit = 8)
    {
        $kategori = $this->db->query(
            'SELECT category_id, SUM(bobot) AS skor FROM (
                SELECT p.category_id, 2 AS bobot FROM order_items oi
                  JOIN orders o ON o.id = oi.order_id
                  JOIN products p ON p.id = oi.product_id
                 WHERE o.user_id = ? AND o.payment_status = \'paid\'
                UNION ALL
                SELECT p.category_id, 1 AS bobot FROM wishlists w
                  JOIN products p ON p.id = w.product_id
                 WHERE w.user_id = ?
             ) x WHERE category_id IS NOT NULL
             GROUP BY category_id ORDER BY skor DESC LIMIT 3',
            array((int) $user_id, (int) $user_id))->result_array();

        if ( ! $kategori) {
            return array('alasan' => NULL, 'produk' => array());
        }
        $ids = array_map('intval', array_column($kategori, 'category_id'));

        $this->dasar_katalog();
        $this->db->where_in('p.category_id', $ids)
            ->where('p.id NOT IN (SELECT oi.product_id FROM order_items oi JOIN orders o ON o.id = oi.order_id
                                   WHERE o.user_id = ' . (int) $user_id . ' AND oi.product_id IS NOT NULL)', NULL, FALSE);

        if ($lokasi && ! empty($lokasi['province_id'])) {
            $this->db->order_by('(s.province_id = ' . (int) $lokasi['province_id'] . ')', 'DESC', FALSE);
        }
        $produk = $this->db->order_by('p.terjual_count', 'DESC')
            ->order_by('p.rating_avg', 'DESC')
            ->order_by('p.id', 'DESC')
            ->limit((int) $limit)
            ->get()->result_array();

        $nama = $this->db->select('name')->where('id', $ids[0])->get('categories')->row_array();
        return array('alasan' => $nama ? $nama['name'] : NULL, 'produk' => $produk);
    }

    /* ========================================================= bantuan === */

    /** Kolom yang dibutuhkan kartu produk di beranda maupun katalog. */
    protected function kolom_kartu()
    {
        return 'p.id, p.name, p.slug, p.image, p.price, p.rating_avg, p.rating_count, p.terjual_count,
                p.stock, p.store_id,
                cat.name AS category_name, cat.slug AS category_slug,
                s.name AS store_name, s.slug AS store_slug, d.name AS store_district,
                (SELECT COUNT(*) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1) AS variant_count';
    }

    /** FROM + syarat katalog. Dipanggil sebelum menambah syarat lain. */
    protected function dasar_katalog()
    {
        $this->db->select($this->kolom_kartu(), FALSE)
            ->from('products p')
            ->join('stores s', 's.id = p.store_id')
            ->join('categories cat', 'cat.id = p.category_id', 'left')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->where('p.is_active', 1)
            ->where('s.is_active', 1)
            ->group_start()->where('p.stock IS NULL', NULL, FALSE)->or_where('p.stock >', 0)->group_end();
    }
}
