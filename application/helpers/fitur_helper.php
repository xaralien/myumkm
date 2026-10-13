<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* Bantuan kecil untuk fitur tambahan. Dimuat otomatis (config/autoload.php). */

if ( ! function_exists('alasan_laporan')) {
    /** Pilihan alasan di formulir "Laporkan" - satu daftar untuk view & controller. */
    function alasan_laporan()
    {
        return array(
            'Barang terlarang / ilegal', 'Penipuan', 'Foto atau deskripsi tidak sesuai',
            'Klaim halal/izin palsu', 'Konten kasar / tidak pantas', 'Spam', 'Lainnya',
        );
    }
}
