<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * admin/Analitik - pertumbuhan marketplace.
 *
 *   GET admin/analitik?periode=30
 *   GET admin/analitik/ekspor?periode=30    unduh Excel
 */
class Analitik extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('analitik_model');
        $this->load->library('periode_lib');
    }

    public function index()
    {
        $p = $this->periode_lib->baca($this->input->get('periode') ?: '30');
        $this->render('admin/v_analitik', array(
            'periode'  => $p,
            'pilihan'  => $this->periode_lib->pilihan(),
            'r'        => $this->analitik_model->ringkasan($p['dari'], $p['sampai']),
            'harian'   => $this->analitik_model->harian($p['dari'], $p['sampai']),
            'kategori' => $this->analitik_model->per_kategori($p['dari'], $p['sampai']),
            'wilayah'  => $this->analitik_model->per_wilayah($p['dari'], $p['sampai']),
            'toko'     => $this->analitik_model->toko_teratas($p['dari'], $p['sampai']),
        ));
    }

    public function ekspor()
    {
        $p = $this->periode_lib->baca($this->input->get('periode') ?: '30');
        $r = $this->analitik_model->ringkasan($p['dari'], $p['sampai']);
        $lembar = array(
            'Ringkasan' => array(
                array('Analitik ' . $this->config->item('nama_brand'), tgl_id($p['dari']) . ' - ' . tgl_id($p['sampai'])),
                array('GMV (Rp)', (int) $r['gmv']), array('Nilai barang (Rp)', (int) $r['barang']), array('Pesanan', (int) $r['pesanan']),
                array('Rata-rata pesanan (Rp)', (int) $r['aov']), array('Pembeli', (int) $r['pembeli']),
                array('Toko yang menjual', (int) $r['toko_jual']), array('Toko aktif', (int) $r['toko_aktif']),
                array('Toko baru', (int) $r['toko_baru']), array('Akun baru', (int) $r['akun_baru']),
                array('Komisi (Rp)', (int) $r['komisi']), array('Biaya voucher & subsidi (Rp)', (int) $r['biaya_promo']),
                array('Pendapatan bersih platform (Rp)', (int) $r['bersih_platform']),
            ),
            'Harian'   => array_merge(array(array('Tanggal', 'Pesanan', 'GMV (Rp)')),
                array_map(function ($h) { return array($h['tgl'], $h['n'], $h['gmv']); }, $this->analitik_model->harian($p['dari'], $p['sampai']))),
            'Kategori' => array_merge(array(array('Kategori', 'Terjual (pcs)', 'Nilai (Rp)')),
                array_map(function ($k) { return array($k['nama'], (int) $k['qty'], (int) $k['nilai']); }, $this->analitik_model->per_kategori($p['dari'], $p['sampai']))),
            'Wilayah'  => array_merge(array(array('Kota/Kabupaten', 'Pesanan', 'Toko', 'Nilai (Rp)')),
                array_map(function ($w) { return array((string) $w['nama'], (int) $w['n'], (int) $w['toko'], (int) $w['nilai']); }, $this->analitik_model->per_wilayah($p['dari'], $p['sampai']))),
            'Sebaran UMKM' => array_merge(array(array('Kota', 'Kecamatan', 'Toko', 'Disetujui', 'Menjual 30 hari', 'Omzet 30 hari (Rp)')),
                array_map(function ($s) { return array((string) $s['kota'], (string) $s['kecamatan'], (int) $s['toko'], (int) $s['aktif'], (int) $s['menjual'], (int) $s['omzet']); }, $this->analitik_model->sebaran())),
        );
        $this->load->library('excel_lib');
        $this->excel_lib->unduh('analitik-' . $p['dari'] . '.xlsx', $lembar);
    }
}
