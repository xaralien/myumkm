<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stok_lib - mengurangi dan mengembalikan stok produk.
 * Simpan di: application/libraries/Stok_lib.php
 *
 * KAPAN STOK BERKURANG
 * Saat pembayaran diterima, bukan saat barang masuk keranjang. Keranjang
 * sering ditinggalkan; menahan stok sejak itu membuat barang terlihat habis
 * padahal tidak ada yang benar-benar membelinya.
 *
 * NULL = TIDAK DIBATASI
 * Banyak UMKM membuat barangnya sesuai pesanan. Produk dengan stock NULL
 * dilewati seluruh pemeriksaan di sini.
 */
class Stok_lib {

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Cukupkah stok untuk sejumlah barang?
     *
     * @param  array $butuh  [product_id => qty]
     * @return array daftar produk yang kurang; kosong berarti aman
     */
    public function periksa(array $butuh)
    {
        if ( ! $butuh) {
            return array();
        }

        $baris = $this->CI->db
            ->select('id, name, stock')
            ->where_in('id', array_keys($butuh))
            ->where('stock IS NOT NULL', NULL, FALSE)
            ->get('products')->result_array();

        $kurang = array();

        foreach ($baris as $p) {
            $perlu = (int) $butuh[$p['id']];

            if ((int) $p['stock'] < $perlu) {
                $kurang[] = array(
                    'id'    => (int) $p['id'],
                    'nama'  => $p['name'],
                    'sisa'  => (int) $p['stock'],
                    'minta' => $perlu,
                );
            }
        }

        return $kurang;
    }

    /**
     * Kurangi stok untuk satu pesanan.
     *
     * Dikerjakan dengan satu UPDATE berkondisi per produk:
     *
     *     UPDATE products SET stock = stock - 2 WHERE id = 9 AND stock >= 2
     *
     * Bukan "baca dulu, lalu tulis". Dengan baca-lalu-tulis, dua pembayaran
     * yang masuk bersamaan bisa sama-sama membaca sisa 1 dan sama-sama
     * merasa berhasil - satu pembeli akhirnya tidak kebagian barang.
     * Database yang memutuskan di sini, dan hanya satu yang menang.
     *
     * @return array ['ok' => bool, 'gagal' => [nama produk]]
     */
    public function kurangi($order_id)
    {
        $item = $this->butuh($order_id);
        $gagal = array();

        foreach ($item as $pid => $qty) {
            /* CAST ke SIGNED wajib: kolom stock bertipe UNSIGNED, dan MySQL
               menghitung 'stock - 2' sebagai bilangan tak bertanda. Begitu
               hasilnya bisa negatif, perintahnya gagal dengan "BIGINT
               UNSIGNED value is out of range" - bukan sekadar melewatkan
               barisnya. GREATEST menjaga nilainya tidak pernah di bawah nol. */
            $kurang = 'CAST(stock AS SIGNED) - ' . (int) $qty;

            $this->CI->db
                ->set('stock', 'GREATEST(' . $kurang . ', 0)', FALSE)
                ->set('stock_habis_at', 'CASE WHEN ' . $kurang . ' <= 0 THEN NOW() ELSE NULL END', FALSE)
                ->where('id', (int) $pid)
                ->where('stock IS NOT NULL', NULL, FALSE)
                ->where('stock >=', (int) $qty)
                ->update('products');

            /* Nol baris terpengaruh berarti stoknya SUDAH tidak cukup saat
               perintah ini dijalankan. Pesanannya tetap diteruskan - uangnya
               sudah masuk, dan membatalkannya sepihak jauh lebih merugikan
               pembeli daripada penjual yang kelebihan satu pesanan. Yang
               dilakukan: penjual diberi tahu lewat pesan di chat pesanan. */
            if ($this->CI->db->affected_rows() < 1) {
                $p = $this->CI->db->select('name, stock')->where('id', (int) $pid)
                                  ->get('products')->row_array();

                if ($p && $p['stock'] !== NULL) {
                    $gagal[] = $p['name'];
                }
            }
        }

        return array('ok' => empty($gagal), 'gagal' => $gagal);
    }

    /**
     * Kembalikan stok - dipakai saat pesanan yang SUDAH DIBAYAR dibatalkan.
     *
     * Pesanan yang belum dibayar tidak pernah mengurangi stok, jadi tidak
     * ada yang perlu dikembalikan.
     */
    public function kembalikan($order_id)
    {
        foreach ($this->butuh($order_id) as $pid => $qty) {
            $this->CI->db
                ->set('stock', 'CAST(stock AS SIGNED) + ' . (int) $qty, FALSE)
                ->set('stock_habis_at', NULL)
                ->where('id', (int) $pid)
                ->where('stock IS NOT NULL', NULL, FALSE)
                ->update('products');
        }
    }

    /** [product_id => total qty] untuk satu pesanan. */
    protected function butuh($order_id)
    {
        $baris = $this->CI->db
            ->select('product_id, SUM(qty) AS qty', FALSE)
            ->where('order_id', (int) $order_id)
            ->group_by('product_id')
            ->get('order_items')->result_array();

        $out = array();
        foreach ($baris as $b) {
            if ($b['product_id']) {
                $out[(int) $b['product_id']] = (int) $b['qty'];
            }
        }
        return $out;
    }

    /** Sisa stok satu produk; NULL berarti tidak dibatasi. */
    public function sisa($product_id)
    {
        $p = $this->CI->db->select('stock')->where('id', (int) $product_id)
                          ->get('products')->row_array();

        return ($p && $p['stock'] !== NULL) ? (int) $p['stock'] : NULL;
    }
}
