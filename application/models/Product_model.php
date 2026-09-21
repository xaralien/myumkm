<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Product_model - simpan di application/models/Product_model.php
 */
class Product_model extends CI_Model
{
    const BUMI_KM = 6371;

    public function get_active($id)
    {
        return $this
            ->db
            /* Pengaturan jam ikut dibawa supaya pemanggil tidak perlu
               query kedua. Sejak 07_store_settings.sql, jam kerja milik
               TIAP TOKO - bukan lagi satu nilai global di config. */
            ->select('p.*,
                s.id AS store_id, s.name AS store_name,
                s.open AS store_open, s.close AS store_close,
                s.jeda_persiapan_menit AS store_jeda', FALSE)
            ->from('products p')
            ->join('stores s', 's.id = p.store_id')
            ->where('p.id', (int) $id)
            ->where('p.is_active', 1)
            ->where('s.is_active', 1)
            ->get()
            ->row_array();
    }

    /**
     * @return array dipetakan id => baris, supaya mudah dilihat pakai isset()
     */
    public function get_many(array $ids)
    {
        $ids = array_filter(array_map('intval', $ids));
        if (!$ids) {
            return array();
        }
        $rows = $this
            ->db
            /* Pengaturan jam ikut dibawa supaya pemanggil tidak perlu
               query kedua. Sejak 07_store_settings.sql, jam kerja milik
               TIAP TOKO - bukan lagi satu nilai global di config. */
            ->select('p.*,
                s.id AS store_id, s.name AS store_name,
                s.open AS store_open, s.close AS store_close,
                s.jeda_persiapan_menit AS store_jeda', FALSE)
            ->from('products p')
            ->join('stores s', 's.id = p.store_id')
            ->where_in('p.id', $ids)
            ->where('p.is_active', 1)
            ->where('s.is_active', 1)
            ->get()
            ->result_array();

        $out = array();
        foreach ($rows as $r) {
            $out[(int) $r['id']] = $r;
        }
        return $out;
    }

    public function listing($limit = 24, $offset = 0)
    {
        return $this
            ->db
            ->where('is_active', 1)
            ->order_by('id', 'ASC')
            ->limit((int) $limit, (int) $offset)
            ->get('products')
            ->result_array();
    }

    public function variants($product_id)
    {
        return $this
            ->db
            ->where(array('product_id' => (int) $product_id, 'is_active' => 1))
            ->order_by('sort_order', 'ASC')
            ->get('product_variants')
            ->result_array();
    }

    public function variants_by_ids(array $ids)
    {
        $ids = array_filter(array_map('intval', $ids));
        if (!$ids) {
            return array();
        }
        $rows = $this->db->where_in('id', $ids)->get('product_variants')->result_array();
        $out = array();
        foreach ($rows as $r) {
            $out[(int) $r['id']] = $r;
        }
        return $out;
    }

    /**
     * Cegah orang mengirim id varian milik produk lain lewat DevTools.
     */
    public function variant_belongs_to($variant_id, $product_id)
    {
        return (bool) $this->db->where(array(
            'id' => (int) $variant_id,
            'product_id' => (int) $product_id,
            'is_active' => 1,
        ))->count_all_results('product_variants');
    }

    /* =====================================================================
     |  PENCARIAN, FILTER, URUTAN
     |
     |  build_filter() dipakai bersama oleh count_filtered() dan search()
     |  supaya jumlah hasil dan isi halaman TIDAK PERNAH memakai syarat
     |  yang berbeda. Kalau keduanya ditulis terpisah, paginasi gampang
     |  salah: halaman terakhir kosong padahal totalnya bilang ada isi.
     | ================================================================== */

    /**
     * Kolom yang boleh dipakai mengurutkan. Input user TIDAK PERNAH
     *  masuk langsung ke ORDER BY - itu celah SQL injection, dan
     *  query builder tidak melindungi bagian ini.
     */
    protected $sort_map = array(
        'baru' => array('id', 'DESC'),
        'murah' => array('price', 'ASC'),
        'mahal' => array('price', 'DESC'),
        'nama' => array('name', 'ASC'),
    );

    public function sort_options()
    {
        return array(
            'baru' => 'Terbaru',
            'murah' => 'Harga terendah',
            'mahal' => 'Harga tertinggi',
            'nama' => 'Nama A-Z',
        );
    }

    /**
     * Syarat dasar: produk aktif, milik toko yang aktif, dan - kalau
     * pengunjung sudah memilih lokasi - toko berada di provinsi yang sama.
     *
     * Pembatasan sampai provinsi saja, BUKAN kecamatan. Kalau dipatok di
     * kecamatan, katalog akan sering kosong: satu kecamatan mungkin hanya
     * punya satu toko atau tidak sama sekali. Yang menyempit adalah
     * URUTANNYA, bukan syaratnya - lihat build_sort().
     */
    protected function build_filter(array $f)
    {
        $this
            ->db
            ->from('products p')
            ->join('stores s', 's.id = p.store_id')
            ->where('p.is_active', 1)
            ->where('s.is_active', 1);

        $dekat = $this->mode_dekat($f);

        if ($dekat) {
            /* Penyaring kotak - bisa memakai indeks ix_stores_koordinat.
               Jarak persisnya dihitung belakangan lewat HAVING. */
            $this->bbox_filter($f['dekat_lat'], $f['dekat_lng'], $f['radius']);
        } elseif (!empty($f['province_id'])) {
            /* PENTING: filter provinsi hanya berlaku kalau mode radius
               TIDAK aktif.

               Kalau keduanya dipakai bersamaan, radius jadi terpotong garis
               administrasi. Di Jabodetabek akibatnya parah: radius 10 km
               dari Jakarta Selatan mencakup Depok, Ciputat, dan Bekasi -
               ketiganya beda provinsi dan akan hilang semua, padahal
               jaraknya cuma 9 km.

               Radius sudah membatasi jauh lebih ketat daripada provinsi,
               jadi tidak perlu ditumpuk. */
            $this->db->where('s.province_id', (int) $f['province_id']);
        }

        if (!empty($f['strict']) && !empty($f['district_id']) && !$dekat) {
            $this->db->where('s.district_id', (int) $f['district_id']);
        }

        if (!empty($f['store_id'])) {
            $this->db->where('p.store_id', (int) $f['store_id']);
        }

        if (!empty($f['q'])) {
            // group_start() penting: tanpa ini OR bocor keluar dan
            // mengabaikan syarat wilayah maupun kategori.
            $this
                ->db
                ->group_start()
                ->like('p.name', $f['q'])
                ->or_like('p.description', $f['q'])
                ->or_like('s.name', $f['q'])
                ->group_end();
        }

        if (!empty($f['category'])) {
            $this
                ->db
                ->join('categories c', 'c.id = p.category_id')
                ->where('c.slug', $f['category']);
        }

        if (isset($f['min']) && $f['min'] !== '' && $f['min'] !== NULL) {
            $this->db->where('p.price >=', (int) $f['min']);
        }
        if (isset($f['max']) && $f['max'] !== '' && $f['max'] !== NULL) {
            $this->db->where('p.price <=', (int) $f['max']);
        }
    }

    /**
     * Kedekatan wilayah: 1 = kecamatan sama, 2 = kabupaten/kota sama,
     * 3 = provinsi sama. Dipakai sebagai kunci urut PERTAMA, sebelum
     * urutan pilihan pengguna.
     *
     * Nilai id dipaksa jadi integer, tidak lewat bind parameter, karena
     * bagian ORDER BY / CASE tidak dilindungi query builder.
     */
    protected function proximity_sql(array $f)
    {
        if (empty($f['district_id'])) {
            return NULL;
        }
        $d = (int) $f['district_id'];
        $r = (int) $f['regency_id'];
        $p = (int) $f['province_id'];

        return "CASE
                  WHEN s.district_id = {$d} THEN 1
                  WHEN s.regency_id  = {$r} THEN 2
                  WHEN s.province_id = {$p} THEN 3
                  ELSE 4
                END";
    }

    public function count_filtered(array $f)
    {
        if (!$this->mode_dekat($f)) {
            $this->build_filter($f);
            return (int) $this->db->count_all_results();
        }

        $this->build_filter($f);

        $jarak = $this->jarak_sql($f['dekat_lat'], $f['dekat_lng']);

        $rows = $this
            ->db
            ->select('p.id, ' . $jarak . ' AS jarak_km, s.radius_km AS store_radius_km', FALSE)
            ->having('jarak_km <= ' . (float) $f['radius'], NULL, FALSE)
            ->having('(store_radius_km IS NULL OR jarak_km <= store_radius_km)', NULL, FALSE)
            ->get()
            ->result_array();

        return count($rows);
    }

    public function search(array $f, $limit, $offset)
    {
        $this->build_filter($f);

        $dekat = $this->mode_dekat($f);

        $select = 'p.*,
            cat.name AS category_name, cat.slug AS category_slug,
            s.name AS store_name, s.slug AS store_slug,
            s.district_id AS store_district_id,
            s.regency_id  AS store_regency_id,
            d.name AS store_district, rg.name AS store_regency,
            (SELECT COUNT(*) FROM product_variants v
             WHERE v.product_id = p.id AND v.is_active = 1) AS variant_count';

        if ($dekat) {
            $jarak = $this->jarak_sql($f['dekat_lat'], $f['dekat_lng']);

            /* Kolom hasil hitungan DAN s.radius_km sama-sama diberi alias.
               HAVING tidak bisa menyebut kolom tabel apa pun - tanpa
               GROUP BY, MySQL menganggap seluruh hasil satu grup, dan yang
               boleh disebut hanya alias dari SELECT. Ini penyebab
               "Unknown column 's.latitude' in 'having clause'". */
            $select .= ', ' . $jarak . ' AS jarak_km';
            $select .= ', s.radius_km AS store_radius_km';
        }

        $prox = $this->proximity_sql($f);
        if ($prox !== NULL && !$dekat) {
            $select .= ', (' . $prox . ') AS proximity';
        }

        $this
            ->db
            ->select($select, FALSE)
            ->join('categories cat', 'cat.id = p.category_id', 'left')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->join('regencies rg', 'rg.id = s.regency_id', 'left');

        if ($dekat) {
            // Alias, bukan pengulangan rumus - itu yang membuatnya sah.
            $this
                ->db
                ->having('jarak_km <= ' . (float) $f['radius'], NULL, FALSE)
                ->having('(store_radius_km IS NULL OR jarak_km <= store_radius_km)', NULL, FALSE);

            /* Jarak jadi kunci urut PERTAMA. Sebelumnya urutannya
               proximity, lalu p.id DESC, baru jarak_km - dan karena p.id
               unik, jarak_km tidak pernah menentukan apa pun. Toko
               terdekat tidak muncul lebih dulu. */
            $this->db->order_by('jarak_km', 'ASC');
        } elseif ($prox !== NULL) {
            $this->db->order_by($prox . ' ASC', '', FALSE);
        }

        $key = isset($f['sort']) ? $f['sort'] : 'baru';
        $sort = isset($this->sort_map[$key]) ? $this->sort_map[$key] : $this->sort_map['baru'];
        $this->db->order_by('p.' . $sort[0], $sort[1]);

        return $this->db->limit((int) $limit, (int) $offset)->get()->result_array();
    }

    /**
     * Jumlah produk per tingkat kedekatan. Dipakai untuk kalimat
     * "3 di kecamatanmu, 18 lagi di Kota Batam" - tanpa ini pengunjung
     * bingung kenapa muncul toko dari kecamatan lain.
     */
    public function count_by_proximity(array $f)
    {
        if ($this->mode_dekat($f)) {
            return array(1 => 0, 2 => 0, 3 => 0);
        }

        $prox = $this->proximity_sql($f);
        if ($prox === NULL) {
            return array(1 => 0, 2 => 0, 3 => 0);
        }

        $this->build_filter($f);
        $rows = $this
            ->db
            ->select("({$prox}) AS lvl, COUNT(*) AS jml", FALSE)
            ->group_by('lvl', FALSE)
            ->get()
            ->result_array();

        $out = array(1 => 0, 2 => 0, 3 => 0);
        foreach ($rows as $r) {
            $out[(int) $r['lvl']] = (int) $r['jml'];
        }
        return $out;
    }

    /** Daftar kategori beserta jumlah produknya, untuk tombol filter. */

    /**
     * Kategori beserta jumlah produk yang benar-benar tersedia di wilayah
     * pengunjung. Kategori berjumlah 0 tetap ditampilkan tapi diberi tanda,
     * supaya orang tahu kategori itu ada - hanya belum ada tokonya di sini.
     */
    public function categories($province_id = NULL)
    {
        /* Syarat wilayah diletakkan di klausa ON, bukan WHERE. Kalau di
           WHERE, LEFT JOIN berubah sifat menjadi INNER JOIN dan kategori
           yang belum punya produk ikut hilang dari daftar filter.

           Yang dihitung COUNT(s.id), bukan COUNT(p.id): baris produk tetap
           ada walau join ke stores gagal (toko nonaktif / beda provinsi),
           jadi menghitung p.id akan melebihkan jumlahnya. */
        $syarat_toko = 's.id = p.store_id AND s.is_active = 1';
        if (!empty($province_id)) {
            $syarat_toko .= ' AND s.province_id = ' . (int) $province_id;
        }

        return $this
            ->db
            ->select('c.id, c.name, c.slug, COUNT(s.id) AS jml', FALSE)
            ->from('categories c')
            ->join('products p', 'p.category_id = c.id AND p.is_active = 1', 'left', FALSE)
            ->join('stores s', $syarat_toko, 'left', FALSE)
            ->where('c.is_active', 1)
            ->group_by('c.id')
            ->order_by('c.sort_order', 'ASC')
            ->get()
            ->result_array();
    }

    public function categories_simple()
    {
        return $this
            ->db
            ->where('is_active', 1)
            ->order_by('sort_order', 'ASC')
            ->get('categories')
            ->result_array();
    }

    public function category_exists($id)
    {
        return (bool) $this
            ->db
            ->where(array('id' => (int) $id, 'is_active' => 1))
            ->count_all_results('categories');
    }

    /**
     * Harga termurah & termahal, untuk placeholder kolom rentang harga.
     */
    public function price_range($province_id = NULL)
    {
        $this
            ->db
            ->select('MIN(p.price) AS min_p, MAX(p.price) AS max_p', FALSE)
            ->from('products p')
            ->join('stores s', 's.id = p.store_id')
            ->where('p.is_active', 1)
            ->where('s.is_active', 1);

        if (!empty($province_id)) {
            $this->db->where('s.province_id', (int) $province_id);
        }
        $r = $this->db->get()->row_array();
        return array(
            'min' => (int) ($r['min_p'] ?: 0),
            'max' => (int) ($r['max_p'] ?: 0),
        );
    }

    /* -------------------------------------------------- halaman detail ---
     |  Slug produk hanya unik DI DALAM satu toko (uq_products_store_slug),
     |  jadi pencarian harus memakai pasangan slug toko + slug produk.
     |  URL /produk/{slug} saja ambigu begitu dua toko punya nama produk
     |  yang sama - dan itu pasti terjadi di marketplace bunga.
     | ------------------------------------------------------------------ */

    public function detail($store_slug, $product_slug)
    {
        return $this
            ->db
            ->select('p.*,
                cat.name AS category_name, cat.slug AS category_slug,
                s.id AS store_id, s.name AS store_name, s.slug AS store_slug,
                s.phone AS store_phone, s.address AS store_address,
                s.avatar AS store_avatar,
                s.open AS store_open, s.close AS store_close,
                s.jeda_persiapan_menit AS store_jeda,
                s.description AS store_description,
                s.province_id AS store_province_id,
                s.regency_id  AS store_regency_id,
                s.district_id AS store_district_id,
                d.name AS store_district, rg.name AS store_regency,
                pr.name AS store_province', FALSE)
            ->from('products p')
            ->join('stores s', 's.id = p.store_id')
            ->join('categories cat', 'cat.id = p.category_id', 'left')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->join('regencies rg', 'rg.id = s.regency_id', 'left')
            ->join('provinces pr', 'pr.id = s.province_id', 'left')
            ->where('s.slug', $store_slug)
            ->where('p.slug', $product_slug)
            ->where('p.is_active', 1)
            ->where('s.is_active', 1)
            ->get()
            ->row_array();
    }

    /**
     * Cari produk hanya dari slug produknya, tanpa slug toko.
     *
     * Dipakai menolong URL lama satu segmen. Hasilnya bisa LEBIH DARI SATU -
     * dua toko boleh punya "buket-mawar-merah" masing-masing - jadi
     * pemanggil harus memutuskan sendiri apa yang dilakukan kalau ganda.
     *
     * @return array daftar baris berisi slug toko & slug produk
     */
    public function cari_slug($product_slug)
    {
        return $this
            ->db
            ->select('p.id, p.slug, p.name, s.slug AS store_slug, s.name AS store_name', FALSE)
            ->from('products p')
            ->join('stores s', 's.id = p.store_id')
            ->where('p.slug', $product_slug)
            ->where('p.is_active', 1)
            ->where('s.is_active', 1)
            ->get()
            ->result_array();
    }

    /**
     * Produk lain dari toko yang sama, untuk bagian bawah halaman detail.
     */
    public function lain_dari_toko($store_id, $kecuali_id, $limit = 4)
    {
        return $this
            ->db
            ->select('p.*, (
                SELECT COUNT(*) FROM product_variants v
                WHERE v.product_id = p.id AND v.is_active = 1
            ) AS variant_count', FALSE)
            ->from('products p')
            ->where('p.store_id', (int) $store_id)
            ->where('p.id !=', (int) $kecuali_id)
            ->where('p.is_active', 1)
            ->order_by('p.id', 'DESC')
            ->limit((int) $limit)
            ->get()
            ->result_array();
    }

    /* ---------------------------------------------------------- addons ---
     | Tabel product_addons - milik tiap produk, bukan global. "Vas kaca"
     | untuk buket berbeda dari "pita nama" untuk papan bunga, dan tiap toko
     | punya tambahan serta harganya masing-masing.
     |
     | Kolomnya price_delta, sama seperti product_variants: nilai yang
     | DITAMBAHKAN ke harga dasar.
     | ------------------------------------------------------------------ */

    public function addons_for_product($product_id)
    {
        return $this
            ->db
            ->where(array('product_id' => (int) $product_id, 'is_active' => 1))
            ->order_by('sort_order', 'ASC')
            ->get('product_addons')
            ->result_array();
    }

    /**
     * @return array dipetakan id => baris
     */
    public function addons_by_ids(array $ids)
    {
        $ids = array_filter(array_map('intval', $ids));
        if (!$ids) {
            return array();
        }
        $rows = $this->db->where_in('id', $ids)->get('product_addons')->result_array();
        $out = array();
        foreach ($rows as $r) {
            $out[(int) $r['id']] = $r;
        }
        return $out;
    }

    /**
     * Cegah orang mengirim id addon milik produk lain lewat DevTools.
     */
    public function addon_belongs_to($addon_id, $product_id)
    {
        return (bool) $this->db->where(array(
            'id' => (int) $addon_id,
            'product_id' => (int) $product_id,
            'is_active' => 1,
        ))->count_all_results('product_addons');
    }

    protected function bbox_filter($lat, $lng, $radius_km)
    {
        $lat = (float) $lat;
        $lng = (float) $lng;
        $radius = (float) $radius_km;

        $dlat = $radius / 111.045;
        $dlng = $radius / max(0.000001, 111.045 * cos(deg2rad($lat)));

        $this
            ->db
            ->where('s.latitude IS NOT NULL', NULL, FALSE)
            ->where('s.latitude >=', $lat - $dlat)
            ->where('s.latitude <=', $lat + $dlat)
            ->where('s.longitude >=', $lng - $dlng)
            ->where('s.longitude <=', $lng + $dlng);
    }

    /**
     * Potongan SQL penghitung jarak (km).
     *
     * LEAST(1, ...) itu WAJIB, bukan kehati-hatian berlebihan: pembulatan
     * floating point bisa menghasilkan nilai 1.0000000002, dan ACOS di
     * luar rentang -1..1 mengembalikan NULL. Akibatnya toko yang jaraknya
     * nyaris nol - yaitu yang PALING dekat - justru hilang dari hasil.
     */
    protected function jarak_sql($lat, $lng)
    {
        $lat = (float) $lat;
        $lng = (float) $lng;

        /* LEAST(1, ...) WAJIB: pembulatan floating point bisa menghasilkan
           1.0000000002, dan ACOS di luar -1..1 mengembalikan NULL. Yang
           hilang justru toko berjarak nyaris nol - yang PALING dekat. */
        return '(' . self::BUMI_KM . ' * ACOS(LEAST(1, '
            . 'COS(RADIANS(' . $lat . ')) * COS(RADIANS(s.latitude)) * '
            . 'COS(RADIANS(s.longitude) - RADIANS(' . $lng . ')) + '
            . 'SIN(RADIANS(' . $lat . ')) * SIN(RADIANS(s.latitude))'
            . ')))';
    }

    protected function mode_dekat(array $f)
    {
        return !empty($f['dekat_lat']) && !empty($f['dekat_lng']);
    }

    /**
     * Toko di sekitar sebuah titik, diurutkan dari yang terdekat.
     *
     * @return array berisi kolom jarak_km
     */
    public function toko_sekitar($lat, $lng, $radius_km = 10, $limit = 20)
    {
        $jarak = $this->jarak_sql($lat, $lng);

        $this
            ->db
            ->select('s.id, s.name, s.slug, s.avatar, s.latitude, s.longitude,
                           d.name AS district_name, r.name AS regency_name,
                           ' . $jarak . ' AS jarak_km,
                           s.radius_km AS store_radius_km,
                           (SELECT COUNT(*) FROM products p
                             WHERE p.store_id = s.id AND p.is_active = 1) AS jml_produk', FALSE)
            ->from('stores s')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->join('regencies r', 'r.id = s.regency_id', 'left')
            ->where('s.is_active', 1);

        $this->bbox_filter($lat, $lng, $radius_km);

        return $this
            ->db
            ->having('jarak_km <= ' . (float) $radius_km, NULL, FALSE)
            ->having('(store_radius_km IS NULL OR jarak_km <= store_radius_km)', NULL, FALSE)
            ->order_by('jarak_km', 'ASC')
            ->limit((int) $limit)
            ->get()
            ->result_array();
    }
}
