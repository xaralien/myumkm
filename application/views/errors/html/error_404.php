<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/* Halaman tidak ditemukan. Simpan di: application/views/errors/html/error_404.php */

require_once __DIR__ . '/galat_kerangka.php';

galat_tampilkan(array(
    'kode'  => '404',
    'judul' => 'Halaman ini tidak ketemu',
    /* Nada sengaja ringan dan tidak menyalahkan: sebagian besar 404 terjadi
       karena tautan lama atau produk yang sudah dihapus penjual - bukan
       karena pengunjung salah. */
    'pesan' => 'Mungkin tautannya sudah berubah, atau produknya sudah tidak '
             . 'dijual lagi. Tidak apa-apa, masih banyak yang lain.',
    'saran' => array(
        array('Toko terdekat', 'shop'),
        array('Lacak pesanan', 'lacak'),
        array('Masuk akun',   'auth/login'),
    ),
));
