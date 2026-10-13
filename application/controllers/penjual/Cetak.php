<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Cetak - label pengiriman & nota.  Alamat: seller/cetak
 *
 *   GET seller/cetak                       pilih pesanan yang mau dicetak
 *   GET seller/cetak/label?id[]=1&id[]=2   label alamat (A6 / thermal)
 *   GET seller/cetak/nota?id[]=1           nota / invoice
 *
 * Halaman cetak berdiri sendiri (tanpa menu panel) dan langsung membuka
 * dialog cetak. Ukuran bisa dipilih: A6 (printer biasa, 4 label per A4)
 * atau 80 mm / 58 mm (printer thermal kasir yang umum di UMKM).
 */
class Cetak extends Seller_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('order_model');
    }

    public function index()
    {
        $this->render('penjual/v_cetak_pilih', array(
            'orders' => $this->db->where('store_id', (int) $this->store['id'])
                ->where('payment_status', 'paid')
                ->where_in('order_status', array('pending', 'confirmed', 'preparing', 'delivering'))
                ->order_by('created_at', 'ASC')->limit(100)
                ->get('orders')->result_array(),
        ));
    }

    public function label()
    {
        $this->tampil('penjual/v_cetak_label');
    }

    public function nota()
    {
        $this->tampil('penjual/v_cetak_nota');
    }

    protected function tampil($view)
    {
        $ids = array_filter(array_map('intval', (array) $this->input->get('id')));
        if ( ! $ids) {
            $this->session->set_flashdata('error', 'Pilih pesanan yang mau dicetak.');
            return redirect('seller/cetak');
        }

        // Hanya pesanan milik toko ini - id dari alamat bisa diubah siapa saja.
        $orders = $this->db->select('o.*, d.name AS district_name, r.name AS regency_name, p.name AS province_name', FALSE)
            ->from('orders o')
            ->join('districts d', 'd.id = o.recipient_district_id', 'left')
            ->join('regencies r', 'r.id = o.recipient_regency_id', 'left')
            ->join('provinces p', 'p.id = o.recipient_province_id', 'left')
            ->where('o.store_id', (int) $this->store['id'])
            ->where_in('o.id', array_slice($ids, 0, 100))
            ->order_by('o.id', 'ASC')->get()->result_array();
        if ( ! $orders) {
            show_404();
        }
        foreach ($orders as &$o) {
            $o['items'] = $this->order_model->items($o['id']);
        }
        unset($o);

        $toko = $this->db->select('s.*, d.name AS district_name, r.name AS regency_name', FALSE)
            ->from('stores s')
            ->join('districts d', 'd.id = s.district_id', 'left')
            ->join('regencies r', 'r.id = s.regency_id', 'left')
            ->where('s.id', (int) $this->store['id'])->get()->row_array();

        $ukuran = in_array($this->input->get('ukuran'), array('a6', '80', '58'), TRUE) ? $this->input->get('ukuran') : 'a6';

        $this->load->view($view, array(
            'orders' => $orders,
            'toko'   => $toko,
            'ukuran' => $ukuran,
            'ids'    => $ids,
            'brand'  => $this->config->item('nama_brand'),
        ));
    }
}
