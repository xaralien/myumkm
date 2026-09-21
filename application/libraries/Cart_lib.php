<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cart_lib - keranjang belanja untuk pembeli tanpa akun.
 *
 * Simpan di: application/libraries/Cart_lib.php
 * Pakai    : $this->load->library('cart_lib');
 *
 * ATURAN UTAMA: harga TIDAK PERNAH diambil dari input pembeli.
 * Yang dikirim browser hanya id produk, id varian, id tambahan, dan jumlah.
 * Semua nominal dibaca ulang dari database setiap kali keranjang dihitung.
 * Tanpa ini, siapa pun bisa mengubah harga lewat DevTools.
 *
 * Kenapa session, bukan tabel database: pembeli guest tidak punya identitas
 * yang bisa dipakai sebagai kunci. Session sudah cukup dan jauh lebih ringan.
 */
class Cart_lib
{

    const KEY      = 'cart_items';
    const MAX_QTY  = 20;
    const MAX_LINE = 30;

    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('session');
        $this->CI->load->model('product_model');
    }

    /* -------------------------------------------------- isi keranjang --- */

    /** Isi mentah dari session: hanya id dan jumlah, tanpa harga. */
    protected function raw()
    {
        $raw = $this->CI->session->userdata(self::KEY);
        return is_array($raw) ? $raw : array();
    }

    protected function save(array $raw)
    {
        $this->CI->session->set_userdata(self::KEY, $raw);
    }

    /**
     * Kunci baris. Produk yang sama dengan ukuran atau tambahan berbeda
     * harus jadi baris terpisah, bukan menambah jumlah baris yang sudah ada.
     */
    protected function line_key($product_id, $variant_id, array $addon_ids)
    {
        sort($addon_ids);
        return md5($product_id . '|' . (int) $variant_id . '|' . implode(',', $addon_ids));
    }

    /* ------------------------------------------------------- perubahan --- */

    /**
     * @return array ['ok' => bool, 'message' => string]
     */
    public function add($product_id, $variant_id = 0, array $addon_ids = array(), $qty = 1)
    {
        $product_id = (int) $product_id;
        $variant_id = (int) $variant_id;
        $qty        = max(1, min(self::MAX_QTY, (int) $qty));
        $addon_ids  = array_values(array_unique(array_map('intval', $addon_ids)));

        $product = $this->CI->product_model->get_active($product_id);
        if (! $product) {
            return array('ok' => FALSE, 'message' => 'Produk tidak ditemukan atau sudah tidak dijual.');
        }

        // Varian harus benar-benar milik produk ini - jangan percaya id dari browser.
        if ($variant_id && ! $this->CI->product_model->variant_belongs_to($variant_id, $product_id)) {
            return array('ok' => FALSE, 'message' => 'Pilihan ukuran tidak valid.');
        }

        // Begitu pula addons. Tanpa ini, id addon murah dari produk lain
        // bisa disisipkan untuk mendapat tambahan mahal dengan harga salah.
        foreach ($addon_ids as $aid) {
            if (! $this->CI->product_model->addon_belongs_to($aid, $product_id)) {
                return array('ok' => FALSE, 'message' => 'Pilihan tambahan tidak valid.');
            }
        }

        /* ---- SATU TOKO PER KERANJANG ---------------------------------
         | Marketplace: tiap toko punya ongkir, jadwal, dan penjual sendiri.
         | Mencampur dua toko dalam satu pesanan menuntut pemecahan pesanan,
         | pembagian pembayaran ke tiap penjual, dan refund sebagian - jauh
         | lebih rumit daripada nilainya untuk kasus toko bunga.
         |
         | Yang dikembalikan bukan sekadar penolakan: nama toko lama ikut
         | dikirim supaya antarmuka bisa menawarkan "kosongkan keranjang".
         | -------------------------------------------------------------- */
        $toko_kini = $this->store_id();
        if ($toko_kini !== NULL && (int) $product['store_id'] !== $toko_kini) {
            return array(
                'ok'          => FALSE,
                'code'        => 'beda_toko',
                'message'     => 'Keranjangmu berisi produk dari toko lain.',
                'store_lama'  => $this->store_name(),
                'store_baru'  => $product['store_name'],
            );
        }

        $raw = $this->raw();
        $key = $this->line_key($product_id, $variant_id, $addon_ids);

        if (isset($raw[$key])) {
            $raw[$key]['qty'] = min(self::MAX_QTY, $raw[$key]['qty'] + $qty);
        } else {
            if (count($raw) >= self::MAX_LINE) {
                return array('ok' => FALSE, 'message' => 'Keranjang sudah penuh.');
            }
            $raw[$key] = array(
                'store_id'   => (int) $product['store_id'],
                'product_id' => $product_id,
                'variant_id' => $variant_id,
                'addon_ids'  => $addon_ids,
                'qty'        => $qty,
            );
        }

        $this->save($raw);
        return array('ok' => TRUE, 'message' => 'Ditambahkan ke keranjang.');
    }

    public function update_qty($key, $qty)
    {
        $raw = $this->raw();
        if (! isset($raw[$key])) {
            return FALSE;
        }
        $qty = (int) $qty;
        if ($qty < 1) {
            unset($raw[$key]);
        } else {
            $raw[$key]['qty'] = min(self::MAX_QTY, $qty);
        }
        $this->save($raw);
        return TRUE;
    }

    public function remove($key)
    {
        $raw = $this->raw();
        unset($raw[$key]);
        $this->save($raw);
        return TRUE;
    }

    public function clear()
    {
        $this->CI->session->unset_userdata(self::KEY);
    }

    /* ---------------------------------------------------------- toko --- */

    /** @return int|NULL id toko pemilik isi keranjang, NULL kalau kosong */
    public function store_id()
    {
        foreach ($this->raw() as $line) {
            if (! empty($line['store_id'])) {
                return (int) $line['store_id'];
            }
        }
        return NULL;
    }

    public function store_name()
    {
        $id = $this->store_id();
        if (! $id) {
            return NULL;
        }
        $row = $this->CI->db->select('name')->where('id', $id)->get('stores')->row_array();
        return $row ? $row['name'] : NULL;
    }

    /** Data toko lengkap, dipakai halaman keranjang & checkout. */
    public function store()
    {
        $id = $this->store_id();
        if (! $id) {
            return NULL;
        }
        return $this->CI->db
            ->select('s.*,
                d.name AS district_name,
                r.name AS regency_name,
                p.name AS province_name', FALSE)
            ->from('stores s')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->join('regencies r', 'r.id = s.regency_id', 'left')
            ->join('provinces p', 'p.id = s.province_id', 'left')
            ->where('s.id', $id)
            ->get()->row_array();
    }

    /* -------------------------------------------------- pembacaan data --- */

    /**
     * Keranjang lengkap dengan harga terkini dari database.
     * Baris yang produknya sudah dihapus/nonaktif ikut dibuang di sini.
     */
    public function items()
    {
        $raw = $this->raw();
        if (! $raw) {
            return array();
        }

        $products = $this->CI->product_model->get_many(
            array_column($raw, 'product_id')
        );
        $variants = $this->CI->product_model->variants_by_ids(
            array_filter(array_column($raw, 'variant_id'))
        );
        // Kumpulkan semua id addon di keranjang, ambil sekali jalan.
        $semua_addon = array();
        foreach ($raw as $line) {
            if (! empty($line['addon_ids']) && is_array($line['addon_ids'])) {
                $semua_addon = array_merge($semua_addon, $line['addon_ids']);
            }
        }
        $addons = $this->CI->product_model->addons_by_ids($semua_addon);

        $items   = array();
        $changed = FALSE;

        foreach ($raw as $key => $line) {
            if (! isset($products[$line['product_id']])) {
                unset($raw[$key]);
                $changed = TRUE;
                continue;
            }
            $p          = $products[$line['product_id']];
            $unit       = (int) $p['price'];
            $variant_nm = NULL;

            if ($line['variant_id'] && isset($variants[$line['variant_id']])) {
                $v          = $variants[$line['variant_id']];
                $unit      += (int) $v['price_delta'];
                $variant_nm = $v['name'];
            }

            $picked   = array();
            $addon_ids = isset($line['addon_ids']) && is_array($line['addon_ids'])
                ? $line['addon_ids'] : array();

            foreach ($addon_ids as $aid) {
                if (isset($addons[$aid])) {
                    $picked[] = array(
                        'id'    => (int) $aid,
                        'name'  => $addons[$aid]['name'],
                        // Disimpan sebagai 'price' di addons_json karena itu
                        // SALINAN harga saat dibeli, bukan lagi selisih.
                        'price' => (int) $addons[$aid]['price_delta'],
                    );
                    $unit += (int) $addons[$aid]['price_delta'];
                }
            }

            $items[$key] = array(
                'key'          => $key,
                'store_id'     => (int) $p['store_id'],
                'product_id'   => (int) $p['id'],
                'product_name' => $p['name'],
                'image'        => $p['image'],
                'variant_id'   => (int) $line['variant_id'],
                'variant_name' => $variant_nm,
                'addons'       => $picked,
                'unit_price'   => $unit,
                'qty'          => (int) $line['qty'],
                'line_total'   => $unit * (int) $line['qty'],
            );
        }

        if ($changed) {
            $this->save($raw);
        }
        return $items;
    }

    public function subtotal()
    {
        $sum = 0;
        foreach ($this->items() as $it) {
            $sum += $it['line_total'];
        }
        return $sum;
    }

    /** Jumlah barang, untuk angka kecil di ikon keranjang. */
    public function count()
    {
        $n = 0;
        foreach ($this->raw() as $line) {
            $n += (int) $line['qty'];
        }
        return $n;
    }

    public function is_empty()
    {
        return count($this->raw()) === 0;
    }

    /* ------------------------------------------------------------ ongkir --- */

    /**
     * Ongkir dihitung dari TOKO, bukan dari daftar kota di file config.
     *
     * Tarifnya bertingkat mengikuti struktur wilayah yang sudah dipakai
     * katalog: dalam kecamatan toko, dalam kota, atau luar kota.
     *
     * @param  int      $district_id kecamatan penerima
     * @param  int|NULL $subtotal    NULL = hitung dari isi keranjang
     * @return array ['ok','ongkir','tingkat','pesan']
     *
     *   ok = FALSE berarti toko TIDAK melayani wilayah itu. Dikembalikan
     *   sebagai array, bukan sekadar angka, karena "tidak melayani" bukan
     *   sama dengan "ongkir nol" - dan kalau dipaksa jadi angka, pesanan
     *   ke luar jangkauan akan lolos dengan ongkir 0.
     */
    public function shipping_fee($district_id, $subtotal = NULL)
    {
        $toko = $this->store();
        if (! $toko) {
            return array(
                'ok' => FALSE,
                'ongkir' => 0,
                'tingkat' => NULL,
                'pesan' => 'Keranjang kosong.'
            );
        }

        $this->CI->load->model('region_model');
        $tujuan = $this->CI->region_model->district_full((int) $district_id);

        if (! $tujuan) {
            return array(
                'ok' => FALSE,
                'ongkir' => 0,
                'tingkat' => NULL,
                'pesan' => 'Kecamatan tujuan tidak dikenali.'
            );
        }

        $subtotal = ($subtotal === NULL) ? $this->subtotal() : (int) $subtotal;

        if ((int) $tujuan['district_id'] === (int) $toko['district_id']) {
            $tingkat = 'kecamatan';
            $ongkir  = (int) $toko['ongkir_kecamatan'];
        } elseif ((int) $tujuan['regency_id'] === (int) $toko['regency_id']) {
            $tingkat = 'kota';
            $ongkir  = (int) $toko['ongkir_kota'];
        } elseif ((int) $tujuan['province_id'] === (int) $toko['province_id']) {
            // NULL berarti toko tidak melayani luar kotanya.
            if ($toko['ongkir_provinsi'] === NULL) {
                return array(
                    'ok' => FALSE,
                    'ongkir' => 0,
                    'tingkat' => 'provinsi',
                    'pesan' => $toko['name'] . ' hanya melayani '
                        . $toko['regency_name'] . ' dan sekitarnya.'
                );
            }
            $tingkat = 'provinsi';
            $ongkir  = (int) $toko['ongkir_provinsi'];
        } else {
            return array(
                'ok' => FALSE,
                'ongkir' => 0,
                'tingkat' => 'luar',
                'pesan' => $toko['name'] . ' tidak mengirim ke luar '
                    . $toko['province_name'] . '. Bunga segar tidak tahan '
                    . 'perjalanan antarprovinsi.'
            );
        }

        // Gratis ongkir per toko. 0 berarti fiturnya dimatikan.
        $batas = (int) $toko['gratis_ongkir_min'];
        if ($batas > 0 && $subtotal >= $batas) {
            $ongkir = 0;
        }

        return array('ok' => TRUE, 'ongkir' => $ongkir, 'tingkat' => $tingkat, 'pesan' => '');
    }
}
