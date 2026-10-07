<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Galat - halaman 404 di dalam kerangka situs.
 * Simpan di: application/controllers/Galat.php
 *
 * Dipasang lewat $route['404_override'] = 'galat';
 *
 * Bedanya dengan views/errors/html/error_404.php:
 *
 *   - Berkas di errors/ itu HALAMAN TELANJANG. Ia dipakai saat CodeIgniter
 *     tidak bisa diandalkan - database mati, kelas gagal dimuat - jadi
 *     sengaja tidak memanggil apa pun dari framework.
 *
 *   - Controller ini dipakai saat aplikasinya SEHAT dan cuma alamatnya yang
 *     salah. Di situ CodeIgniter jalan normal, jadi halamannya bisa tampil
 *     di dalam kerangka situs: navbar, keranjang, tab bar di HP. Pengunjung
 *     tidak merasa terlempar keluar, dan jalan keluarnya sudah ada di layar.
 *
 * Keduanya tetap diperlukan. Yang satu untuk alamat salah, yang satu untuk
 * aplikasi yang sedang rusak.
 */
class Galat extends CI_Controller {

    public function index()
    {
        /* Status 404 WAJIB dikirim, bukan 200. Halaman "tidak ditemukan"
           yang menjawab 200 akan diindeks Google sebagai halaman biasa, dan
           alamat yang sudah mati tetap muncul di hasil pencarian. */
        $this->output->set_status_header(404);

        $this->load->helper('url');

        $data = array(
            'kode'  => '404',
            'judul' => 'Halaman ini tidak ketemu',
            /* Nada sengaja tidak menyalahkan: sebagian besar 404 terjadi
               karena tautan lama atau produk yang sudah dihapus penjual. */
            'pesan' => 'Mungkin tautannya sudah berubah, atau produknya sudah '
                     . 'tidak dijual lagi. Tidak apa-apa, masih banyak yang lain.',
            'pages' => 'v_galat',
        );

        $this->load->view('index', $data);
    }

    /**
     * Halaman galat umum di dalam kerangka situs.
     * Dipakai controller lain lewat redirect, misalnya saat sesuatu gagal
     * tapi aplikasinya sendiri masih sehat.
     *
     * GET galat/umum
     */
    public function umum()
    {
        $this->output->set_status_header(500);
        $this->load->helper('url');

        $this->load->view('index', array(
            'kode'  => '500',
            'judul' => 'Ada yang tidak beres',
            'pesan' => 'Halaman ini gagal dimuat. Coba lagi sebentar lagi, '
                     . 'atau kembali ke beranda.',
            'pages' => 'v_galat',
        ));
    }
}
