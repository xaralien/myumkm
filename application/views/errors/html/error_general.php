<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/* Galat umum. Simpan di: application/views/errors/html/error_general.php */

require_once __DIR__ . '/galat_kerangka.php';

galat_tampilkan(array(
    'kode'  => '',
    'judul' => isset($heading) ? $heading : 'Ada yang tidak beres',
    'pesan' => 'Kami sedang memperbaikinya. Coba muat ulang halaman ini '
             . 'sebentar lagi.',
    'saran' => array(
        array('Lihat katalog', 'shop'),
        array('Lacak pesanan', 'lacak'),
    ),
    /* $message dari CodeIgniter berisi penjelasan yang sudah disiapkan
       aplikasi, bukan jejak galat mentah - jadi aman ditampilkan. */
    'rincian' => (ENVIRONMENT !== 'production' && isset($message)) ? $message : '',
));
