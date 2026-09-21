<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cart - simpan di application/controllers/Cart.php
 *
 * Rute yang dipakai:
 *   GET  cart                 halaman keranjang
 *   POST cart/options/{id}    isi panel "tambah" (varian + tambahan)  [AJAX]
 *   POST cart/add             masukkan ke keranjang                    [AJAX]
 *   POST cart/update          ubah jumlah                              [AJAX]
 *   POST cart/remove          hapus baris                              [AJAX]
 */
class Cart extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('cart_lib', 'session'));
        $this->load->model(array('product_model'));
        $this->load->helper(array('url', 'money'));
    }

    /** Balasan JSON, selalu menyertakan token CSRF baru agar AJAX berikutnya lolos. */
    protected function json($data, $code = 200)
    {
        $data['csrf_name'] = $this->security->get_csrf_token_name();
        $data['csrf_hash'] = $this->security->get_csrf_hash();
        $data['cart_count'] = $this->cart_lib->count();

        return $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    /* ------------------------------------------------------------------- */

    public function index()
    {
        $data = array(
            'items'    => $this->cart_lib->items(),
            'subtotal' => $this->cart_lib->subtotal(),
        );
        $data['pages'] = 'v_cart';
        $this->load->view('index', $data);
    }

    /**
     * Data untuk panel yang muncul saat tombol "+" diklik.
     * Varian dan tambahan diambil dari database, bukan ditulis di JavaScript,
     * supaya harga di panel selalu sama dengan harga yang dihitung server.
     */
    public function options($product_id)
    {
        $product = $this->product_model->get_active($product_id);
        if (! $product) {
            return $this->json(array('ok' => FALSE, 'message' => 'Produk tidak ditemukan.'), 404);
        }

        /* Bisa kirim hari ini? Diturunkan dari jam tutup dikurangi jeda
           persiapan - tidak ada lagi kolom cutoff terpisah. */
        $tutup    = substr((string) $product['store_close'], 0, 5);
        $siap     = date('H:i', time() + ((int) $product['store_jeda'] * 60));
        $same_day = $siap <= $tutup;

        return $this->json(array(
            'ok'      => TRUE,
            'product' => array(
                'id'    => (int) $product['id'],
                'name'  => $product['name'],
                'price' => (int) $product['price'],
                'image' => base_url('upload/produk/' . $product['image']),
            ),
            'variants'   => $this->product_model->variants($product_id),
            'addons'     => $this->product_model->addons_for_product($product_id),
            'same_day'   => $same_day,
            'jam_siap'   => $siap,
            'store_name' => $product['store_name'],
            'jam_buka'   => substr((string) $product['store_open'], 0, 5),
            'jam_tutup'  => substr((string) $product['store_close'], 0, 5),
        ));
    }

    public function add()
    {
        $addons = $this->input->post('addons');
        $addons = is_array($addons) ? $addons : array();

        $res = $this->cart_lib->add(
            $this->input->post('product_id'),
            $this->input->post('variant_id'),
            $addons,
            $this->input->post('qty')
        );

        return $this->json(array(
            'ok'         => $res['ok'],
            'message'    => $res['message'],
            'code'       => isset($res['code'])       ? $res['code']       : NULL,
            'store_lama' => isset($res['store_lama']) ? $res['store_lama'] : NULL,
            'store_baru' => isset($res['store_baru']) ? $res['store_baru'] : NULL,
        ), $res['ok'] ? 200 : 422);
    }

    public function update()
    {
        $ok = $this->cart_lib->update_qty(
            $this->input->post('key'),
            $this->input->post('qty')
        );
        return $this->json(array(
            'ok'       => $ok,
            'subtotal' => $this->cart_lib->subtotal(),
            'items'    => array_values($this->cart_lib->items()),
        ), $ok ? 200 : 404);
    }

    /** Dipakai tombol "kosongkan lalu tambahkan" saat ganti toko. */
    public function clear()
    {
        $this->cart_lib->clear();
        return $this->json(array('ok' => TRUE));
    }

    public function remove()
    {
        $this->cart_lib->remove($this->input->post('key'));
        return $this->json(array(
            'ok'       => TRUE,
            'subtotal' => $this->cart_lib->subtotal(),
            'items'    => array_values($this->cart_lib->items()),
        ));
    }
}
