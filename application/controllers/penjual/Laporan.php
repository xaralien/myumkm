<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Laporan - laporan penjualan & laporan keuangan siap KUR.
 * Alamat: seller/laporan
 *
 *   GET seller/laporan?periode=30      ringkasan, grafik, produk terlaris
 *   GET seller/laporan/ekspor?periode  unduh Excel
 *   GET seller/laporan/kur?bulan=6     laporan cetak untuk pengajuan pinjaman
 */
class Laporan extends Seller_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('keuangan_model');
        $this->load->library('periode_lib');
    }

    public function index()
    {
        $p = $this->periode_lib->baca($this->input->get('periode') ?: '30');
        $this->render('penjual/v_laporan', array(
            'periode' => $p,
            'pilihan' => $this->periode_lib->pilihan(),
            'jual'    => $this->keuangan_model->penjualan($this->store['id'], $p['dari'], $p['sampai']),
        ));
    }

    public function ekspor()
    {
        $p = $this->periode_lib->baca($this->input->get('periode') ?: '30');
        $j = $this->keuangan_model->penjualan($this->store['id'], $p['dari'], $p['sampai']);

        $harian = array(array('Tanggal', 'Pesanan', 'Omzet (Rp)'));
        foreach ($j['harian'] as $h) {
            $harian[] = array($h['tgl'], $h['pesanan'], $h['omzet']);
        }
        $produk = array(array('Produk', 'Terjual', 'Omzet (Rp)'));
        foreach ($j['produk'] as $x) {
            $produk[] = array($x['product_name'], (int) $x['qty'], (int) $x['omzet']);
        }
        $pesanan = array(array('Nomor', 'Tanggal bayar', 'Pembeli', 'Kota', 'Subtotal', 'Ongkir', 'Voucher toko', 'Komisi', 'Bagian toko', 'Status'));
        foreach ($this->db->where('store_id', (int) $this->store['id'])->where('payment_status', 'paid')
                     ->where('paid_at >=', $p['dari'] . ' 00:00:00')->where('paid_at <=', $p['sampai'] . ' 23:59:59')
                     ->order_by('paid_at', 'ASC')->get('orders')->result_array() as $o) {
            $pesanan[] = array($o['order_number'], $o['paid_at'], $o['customer_name'], $o['recipient_city'],
                (int) $o['subtotal'], (int) $o['shipping_fee'], (int) ($o['diskon_toko'] ?? 0), (int) $o['fee_platform'],
                (int) $o['net_store'], label_status($o['order_status']));
        }

        $this->load->library('excel_lib');
        $this->excel_lib->unduh('penjualan-' . $this->store['slug'] . '-' . $p['dari'] . '.xlsx', array(
            'Ringkasan' => array(
                array('Laporan penjualan ' . $this->store['name'], ''),
                array('Periode', tgl_id($p['dari']) . ' - ' . tgl_id($p['sampai'])),
                array('Jumlah pesanan', (int) $j['total']['pesanan']),
                array('Omzet barang (Rp)', (int) $j['total']['omzet']),
                array('Diterima toko (Rp)', (int) $j['total']['bersih']),
                array('Jumlah pembeli', (int) $j['total']['pembeli']),
                array('Pembeli yang membeli lagi', (int) $j['total']['pembeli_ulang']),
            ),
            'Harian'   => $harian,
            'Produk'   => $produk,
            'Pesanan'  => $pesanan,
        ));
    }

    /**
     * Laporan keuangan untuk pengajuan KUR / pinjaman koperasi. Format
     * sederhana yang biasa diminta petugas bank untuk usaha mikro: profil
     * usaha, laba-rugi per bulan, rata-rata, dan rincian pengeluaran.
     * Dicetak/disimpan sebagai PDF lewat dialog cetak browser.
     */
    public function kur()
    {
        $n = in_array((int) $this->input->get('bulan'), array(3, 6, 12), TRUE) ? (int) $this->input->get('bulan') : 6;
        $bulan = $this->keuangan_model->per_bulan($this->store['id'], $n);

        $awal  = $bulan[0]['bulan'];
        $akhir = date('Y-m-t', strtotime(end($bulan)['bulan']));

        $dok = array();
        if ($this->db->table_exists('store_documents')) {
            $dok = $this->db->where('store_id', (int) $this->store['id'])->get('store_documents')->result_array();
        }

        $toko = $this->db->select('s.*, u.name AS pemilik, u.email, d.name AS district_name, r.name AS regency_name, p.name AS province_name', FALSE)
            ->from('stores s')->join('users u', 'u.id = s.user_id')
            ->join('districts d', 'd.id = s.district_id', 'left')->join('regencies r', 'r.id = s.regency_id', 'left')
            ->join('provinces p', 'p.id = s.province_id', 'left')
            ->where('s.id', (int) $this->store['id'])->get()->row_array();

        $this->load->view('penjual/v_kur', array(
            'toko'    => $toko,
            'bulan'   => $bulan,
            'n'       => $n,
            'total'   => $this->keuangan_model->ringkasan($this->store['id'], $awal, $akhir),
            'jual'    => $this->keuangan_model->penjualan($this->store['id'], $awal, $akhir),
            'dok'     => $dok,
            'brand'   => $this->config->item('nama_brand'),
            'produk_aktif' => $this->db->where('store_id', (int) $this->store['id'])->where('is_active', 1)->count_all_results('products'),
        ));
    }
}
