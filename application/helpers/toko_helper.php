<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * toko_helper - mode toko anonim.
 *
 * Saat Pengaturan > Umum > "Sembunyikan nama toko dari pembeli" menyala,
 * semua tampilan untuk PEMBELI mengganti nama toko dengan asal wilayahnya
 * ("Penjual di Tebet"), logo toko tidak ditampilkan, dan kontak WhatsApp
 * toko dihilangkan. Penjual & admin tetap melihat nama aslinya.
 *
 * Dimuat otomatis (config/autoload.php).
 */

if ( ! function_exists('toko_anonim')) {
    function toko_anonim()
    {
        static $nilai = NULL;
        if ($nilai === NULL) {
            $CI = &get_instance();
            $CI->load->library('pengaturan_lib');
            $nilai = $CI->pengaturan_lib->aktif('toko_anonim');
        }
        return $nilai;
    }
}

if ( ! function_exists('nama_toko')) {
    /**
     * Nama toko untuk pembeli.
     * @param string      $nama      nama asli
     * @param string|NULL $kecamatan asal toko, dipakai saat anonim
     */
    function nama_toko($nama, $kecamatan = NULL)
    {
        if ( ! toko_anonim()) {
            return (string) $nama;
        }
        $kecamatan = trim((string) $kecamatan);
        return $kecamatan !== '' ? 'Penjual di ' . $kecamatan : 'Penjual UMKM';
    }
}
