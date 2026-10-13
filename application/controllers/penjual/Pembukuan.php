<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * penjual/Pembukuan - catat uang masuk & keluar, lihat laba.
 * Alamat: seller/pembukuan
 *
 *   GET  seller/pembukuan?periode=bulan_ini
 *   POST seller/pembukuan/simpan
 *   POST seller/pembukuan/hapus/{id}
 *   GET  seller/pembukuan/ekspor?periode=...   unduh Excel
 *
 * Mayoritas UMKM tidak mencatat keuangan, sehingga tidak tahu untung
 * sebenarnya dan sulit mengajukan pinjaman. Halaman ini sengaja dibuat
 * sesederhana buku kas: dua tombol besar, satu daftar, satu angka laba.
 * Penjualan di marketplace tercatat sendiri.
 */
class Pembukuan extends Seller_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('keuangan_model');
        $this->load->library('periode_lib');
    }

    public function index()
    {
        $p = $this->periode_lib->baca($this->input->get('periode'));
        $this->render('penjual/v_pembukuan', array(
            'periode'   => $p,
            'pilihan'   => $this->periode_lib->pilihan(),
            'ringkasan' => $this->keuangan_model->ringkasan($this->store['id'], $p['dari'], $p['sampai']),
            'arus'      => $this->keuangan_model->arus($this->store['id'], $p['dari'], $p['sampai']),
            'kategori'  => Keuangan_model::$kategori,
            'jenis'     => $this->input->get('catat') === 'keluar' ? 'keluar' : 'masuk',
        ));
    }

    public function simpan()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $h = $this->keuangan_model->simpan($this->store['id'], (array) $this->input->post(NULL, TRUE));
        $this->session->set_flashdata($h['ok'] ? 'sukses' : 'error', $h['pesan']);
        return redirect('seller/pembukuan?periode=' . rawurlencode((string) $this->input->post('periode')));
    }

    public function hapus($id = NULL)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }
        $ok = $this->keuangan_model->hapus($this->store['id'], $id);
        $this->session->set_flashdata($ok ? 'sukses' : 'error', $ok ? 'Catatan dihapus.' : 'Catatan tidak ditemukan.');
        return redirect('seller/pembukuan?periode=' . rawurlencode((string) $this->input->post('periode')));
    }

    public function ekspor()
    {
        $p = $this->periode_lib->baca($this->input->get('periode'));
        $r = $this->keuangan_model->ringkasan($this->store['id'], $p['dari'], $p['sampai']);
        $baris = array(array('Tanggal', 'Jenis', 'Kategori', 'Keterangan', 'Masuk (Rp)', 'Keluar (Rp)'));
        foreach (array_reverse($this->keuangan_model->arus($this->store['id'], $p['dari'], $p['sampai'])) as $a) {
            $baris[] = array($a['tanggal'], $a['jenis'] === 'masuk' ? 'Masuk' : 'Keluar', $a['kategori'], (string) $a['keterangan'],
                             $a['jenis'] === 'masuk' ? (int) $a['jumlah'] : 0, $a['jenis'] === 'keluar' ? (int) $a['jumlah'] : 0);
        }
        $ringkas = array(
            array('Pembukuan ' . $this->store['name'], ''),
            array('Periode', tgl_id($p['dari']) . ' - ' . tgl_id($p['sampai'])),
            array('Penjualan marketplace (bersih)', $r['marketplace']),
            array('Pemasukan lain', $r['catatan']),
            array('Total pendapatan', $r['pendapatan']),
            array('Total pengeluaran', $r['pengeluaran']),
            array('Laba', $r['laba']),
            array('Modal tambahan (bukan pendapatan)', $r['modal']),
        );
        $this->load->library('excel_lib');
        $this->excel_lib->unduh('pembukuan-' . $this->store['slug'] . '-' . $p['dari'] . '.xlsx',
            array('Ringkasan' => $ringkas, 'Transaksi' => $baris));
    }
}
