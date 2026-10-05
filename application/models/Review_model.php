<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Review_model - ulasan & rating produk.
 * Simpan di: application/models/Review_model.php
 */
class Review_model extends CI_Model {

    /* =====================================================================
       SIAPA YANG BOLEH MENULIS
       ===================================================================== */

    /**
     * Baris pesanan yang boleh diulas oleh pemilik pesanan ini.
     *
     * Syaratnya: pesanan sudah SELESAI (barang diterima) dan baris itu belum
     * pernah diulas. Memperbolehkan ulasan sejak pesanan dibayar membuat
     * orang menilai barang yang belum dilihatnya.
     *
     * @param  int $order_id
     * @return array baris order_items yang masih bisa diulas
     */
    public function bisa_diulas($order_id)
    {
        return $this->db
            ->select('oi.id, oi.product_id, oi.product_name, oi.variant_name, p.image, p.slug,
                      s.slug AS store_slug', FALSE)
            ->from('order_items oi')
            ->join('orders o', 'o.id = oi.order_id')
            ->join('products p', 'p.id = oi.product_id', 'left')
            ->join('stores s', 's.id = o.store_id', 'left')
            ->where('oi.order_id', (int) $order_id)
            ->where('o.order_status', 'delivered')
            // NOT EXISTS, bukan LEFT JOIN + IS NULL: lebih jelas maksudnya
            // dan tidak menggandakan baris kalau datanya aneh.
            ->where('NOT EXISTS (SELECT 1 FROM reviews r WHERE r.order_item_id = oi.id)', NULL, FALSE)
            ->get()->result_array();
    }

    /** Ulasan yang sudah ditulis untuk satu pesanan. */
    public function milik_pesanan($order_id)
    {
        return $this->db->where('order_id', (int) $order_id)
                        ->order_by('id', 'ASC')
                        ->get('reviews')->result_array();
    }

    /* =====================================================================
       MENYIMPAN
       ===================================================================== */

    /**
     * Simpan ulasan satu baris pesanan.
     *
     * @return array ['ok' => bool, 'pesan' => string, 'id' => int]
     */
    public function simpan($order, $item_id, $rating, $isi, $nama, $user_id = NULL)
    {
        $rating = (int) $rating;
        if ($rating < 1 || $rating > 5) {
            return array('ok' => FALSE, 'pesan' => 'Pilih bintang 1 sampai 5.');
        }

        // Dicocokkan ke pesanannya - tanpa ini, mengganti angka di formulir
        // cukup untuk mengulas baris pesanan orang lain.
        $item = $this->db->where('id', (int) $item_id)
                         ->where('order_id', (int) $order['id'])
                         ->get('order_items')->row_array();

        if ( ! $item) {
            return array('ok' => FALSE, 'pesan' => 'Produk tidak ada di pesanan ini.');
        }

        if ($order['order_status'] !== 'delivered') {
            return array('ok' => FALSE, 'pesan' => 'Ulasan bisa ditulis setelah pesanan selesai.');
        }

        $data = array(
            'order_id'      => (int) $order['id'],
            'order_item_id' => (int) $item['id'],
            'product_id'    => (int) $item['product_id'],
            'store_id'      => (int) $order['store_id'],
            'user_id'       => $user_id ? (int) $user_id : NULL,
            'nama'          => mb_substr(trim($nama) ?: 'Pembeli', 0, 100),
            'rating'        => $rating,
            'isi'           => mb_substr(trim((string) $isi), 0, 1500) ?: NULL,
            'variant_name'  => $item['variant_name'],
        );

        /* Indeks unik uq_ulasan_item yang menjaga agar tidak ada ulasan
           ganda. Insert kedua akan ditolak database - itu disengaja, dan di
           sini ditangkap supaya yang muncul pesan biasa, bukan galat SQL. */
        $debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;
        $ok = $this->db->insert('reviews', $data);
        $this->db->db_debug = $debug;

        if ( ! $ok) {
            return array('ok' => FALSE, 'pesan' => 'Produk ini sudah kamu ulas.');
        }

        $id = (int) $this->db->insert_id();

        $this->hitung_ulang($data['product_id'], $data['store_id']);

        return array('ok' => TRUE, 'pesan' => 'Terima kasih atas ulasanmu.', 'id' => $id);
    }

    public function tambah_media($review_id, $berkas, $tipe = 'foto', $urutan = 0)
    {
        return $this->db->insert('review_media', array(
            'review_id' => (int) $review_id,
            'tipe'      => ($tipe === 'video') ? 'video' : 'foto',
            'berkas'    => $berkas,
            'urutan'    => (int) $urutan,
        ));
    }

    /** Balasan penjual. */
    public function balas($review_id, $store_id, $teks)
    {
        $teks = trim((string) $teks);
        if ($teks === '') {
            return FALSE;
        }

        $this->db->where('id', (int) $review_id)
                 ->where('store_id', (int) $store_id)   // hanya tokonya sendiri
                 ->update('reviews', array(
                     'balasan'    => mb_substr($teks, 0, 1000),
                     'balasan_at' => date('Y-m-d H:i:s'),
                 ));

        return $this->db->affected_rows() > 0;
    }

    /**
     * Hitung ulang rata-rata produk dan tokonya.
     *
     * Dipanggil tiap ada ulasan baru. Angkanya disimpan supaya katalog tidak
     * perlu menjumlahkan seluruh ulasan setiap kali dibuka.
     */
    public function hitung_ulang($product_id, $store_id)
    {
        $p = $this->db->select('COUNT(*) AS n, AVG(rating) AS r', FALSE)
                      ->where('product_id', (int) $product_id)
                      ->get('reviews')->row_array();

        $this->db->where('id', (int) $product_id)->update('products', array(
            'rating_count' => (int) $p['n'],
            'rating_avg'   => $p['n'] ? round($p['r'], 1) : 0,
        ));

        $s = $this->db->select('COUNT(*) AS n, AVG(rating) AS r', FALSE)
                      ->where('store_id', (int) $store_id)
                      ->get('reviews')->row_array();

        $this->db->where('id', (int) $store_id)->update('stores', array(
            'rating_count' => (int) $s['n'],
            'rating_avg'   => $s['n'] ? round($s['r'], 1) : 0,
        ));
    }

    /* =====================================================================
       MENAMPILKAN
       ===================================================================== */

    /**
     * Ringkasan: rata-rata, jumlah, dan sebaran bintang 1..5.
     *
     * @param string $jenis 'produk' atau 'toko'
     */
    public function ringkasan($jenis, $id)
    {
        $kolom = ($jenis === 'toko') ? 'store_id' : 'product_id';

        $baris = $this->db
            ->select('rating, COUNT(*) AS n', FALSE)
            ->where($kolom, (int) $id)
            ->group_by('rating')
            ->get('reviews')->result_array();

        $sebaran = array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0);
        $total = $jumlah = 0;

        /* Rating dan ulasan dihitung terpisah: sebagian pembeli hanya
           memberi bintang tanpa menulis apa-apa. Menyebut keduanya dengan
           satu angka membuat daftar di bawahnya terlihat lebih pendek
           daripada yang dijanjikan. */
        $kolom_i = ($jenis === 'toko') ? 'store_id' : 'product_id';
        $berisi = (int) $this->db->where($kolom_i, (int) $id)
                                 ->where('isi IS NOT NULL', NULL, FALSE)
                                 ->where("TRIM(isi) !=", '')
                                 ->count_all_results('reviews');

        foreach ($baris as $b) {
            $sebaran[(int) $b['rating']] = (int) $b['n'];
            $total  += (int) $b['n'];
            $jumlah += (int) $b['rating'] * (int) $b['n'];
        }

        /* "96% pembeli merasa puas" dihitung dari bintang 4 ke atas. Angka
           ini lebih mudah dicerna daripada rata-rata desimal, dan itulah
           yang biasanya dicari pembeli sebelum memutuskan. */
        $puas = $total ? round((($sebaran[5] + $sebaran[4]) / $total) * 100) : 0;

        return array(
            'rata'    => $total ? round($jumlah / $total, 1) : 0,
            'total'   => $total,
            'berisi'  => $berisi,
            'sebaran' => $sebaran,
            'puas'    => $puas,
            'media'   => $this->jumlah_media($jenis, $id),
        );
    }

    protected function jumlah_media($jenis, $id)
    {
        $kolom = ($jenis === 'toko') ? 'r.store_id' : 'r.product_id';

        return (int) $this->db->from('review_media m')
                              ->join('reviews r', 'r.id = m.review_id')
                              ->where($kolom, (int) $id)
                              ->count_all_results();
    }

    /**
     * Daftar ulasan.
     *
     * @param array $opsi rating, media (bool), limit, offset
     */
    public function daftar($jenis, $id, array $opsi = array())
    {
        $kolom = ($jenis === 'toko') ? 'r.store_id' : 'r.product_id';

        $this->db->select('r.*, p.name AS produk_nama, p.slug AS produk_slug, s.slug AS toko_slug', FALSE)
                 ->from('reviews r')
                 ->join('products p', 'p.id = r.product_id', 'left')
                 ->join('stores s', 's.id = r.store_id', 'left')
                 ->where($kolom, (int) $id);

        if ( ! empty($opsi['rating'])) {
            $this->db->where('r.rating', (int) $opsi['rating']);
        }

        if ( ! empty($opsi['media'])) {
            $this->db->where('EXISTS (SELECT 1 FROM review_media m WHERE m.review_id = r.id)', NULL, FALSE);
        }

        $baris = $this->db
            ->order_by('r.created_at', 'DESC')
            ->limit((int) ($opsi['limit'] ?? 10), (int) ($opsi['offset'] ?? 0))
            ->get()->result_array();

        return $this->lampirkan_media($baris);
    }

    public function hitung($jenis, $id, array $opsi = array())
    {
        $kolom = ($jenis === 'toko') ? 'r.store_id' : 'r.product_id';

        $this->db->from('reviews r')->where($kolom, (int) $id);

        if ( ! empty($opsi['rating'])) {
            $this->db->where('r.rating', (int) $opsi['rating']);
        }
        if ( ! empty($opsi['media'])) {
            $this->db->where('EXISTS (SELECT 1 FROM review_media m WHERE m.review_id = r.id)', NULL, FALSE);
        }

        return (int) $this->db->count_all_results();
    }

    /**
     * Foto & video dari semua ulasan - untuk deretan di atas daftar ulasan.
     */
    public function media($jenis, $id, $limit = 6)
    {
        $kolom = ($jenis === 'toko') ? 'r.store_id' : 'r.product_id';

        return $this->db
            ->select('m.*, r.id AS review_id', FALSE)
            ->from('review_media m')
            ->join('reviews r', 'r.id = m.review_id')
            ->where($kolom, (int) $id)
            ->order_by('m.id', 'DESC')
            ->limit((int) $limit)
            ->get()->result_array();
    }

    /**
     * Ambil media untuk sekumpulan ulasan sekaligus.
     *
     * Satu query untuk semua, bukan satu query per ulasan: 10 ulasan di
     * halaman berarti 10 query tambahan, dan itu bertambah terus seiring
     * daftarnya memanjang.
     */
    protected function lampirkan_media(array $baris)
    {
        if ( ! $baris) {
            return $baris;
        }

        $ids = array_column($baris, 'id');

        $media = $this->db->where_in('review_id', $ids)
                          ->order_by('urutan', 'ASC')
                          ->get('review_media')->result_array();

        $peta = array();
        foreach ($media as $m) {
            $peta[$m['review_id']][] = $m;
        }

        foreach ($baris as &$b) {
            $b['media'] = $peta[$b['id']] ?? array();
        }

        return $baris;
    }

    /* ---------------------------------------------- untuk panel penjual --- */

    protected function filter_penjual($store_id, $saring)
    {
        $this->db->from('reviews r')
                 ->join('products p', 'p.id = r.product_id', 'left')
                 ->where('r.store_id', (int) $store_id);

        if ($saring === 'belum') {
            $this->db->where('r.balasan IS NULL', NULL, FALSE);
        } elseif ($saring === 'rendah') {
            // 1-3 bintang: yang paling perlu ditanggapi penjual.
            $this->db->where('r.rating <=', 3);
        }
        return $this;
    }

    public function daftar_toko_penjual($store_id, $saring = NULL, array $opsi = array())
    {
        $baris = $this->filter_penjual($store_id, $saring)->db
            ->select('r.*, p.name AS produk_nama, p.slug AS produk_slug', FALSE)
            ->order_by('r.created_at', 'DESC')
            ->limit((int) ($opsi['limit'] ?? 10), (int) ($opsi['offset'] ?? 0))
            ->get()->result_array();

        return $this->lampirkan_media($baris);
    }

    public function hitung_toko_penjual($store_id, $saring = NULL)
    {
        return (int) $this->filter_penjual($store_id, $saring)->db->count_all_results();
    }

    public function belum_dibalas($store_id)
    {
        return (int) $this->db->where('store_id', (int) $store_id)
                              ->where('balasan IS NULL', NULL, FALSE)
                              ->count_all_results('reviews');
    }
}
