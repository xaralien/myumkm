<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/* Galat database. Simpan di: application/views/errors/html/error_db.php */

require_once __DIR__ . '/galat_kerangka.php';

galat_tampilkan(array(
    'kode'  => '',
    'judul' => 'Layanan sedang terganggu',
    /* Kata "database" sengaja tidak disebut ke pengunjung: tidak menolong
       mereka, dan memberi tahu penyerang bagian mana yang sedang rapuh. */
    'pesan' => 'Data tidak bisa diambil untuk sementara. Pesananmu yang sudah '
             . 'masuk tetap aman. Coba lagi beberapa saat lagi.',
    'rincian' => (ENVIRONMENT !== 'production' && isset($message)) ? $message : '',
));
