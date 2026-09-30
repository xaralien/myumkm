<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('aset')) {
    /**
     * Alamat berkas aset beserta penanda versi.
     * Simpan di: application/helpers/aset_helper.php
     *
     *     aset('assets/css/tema-nusantara.css')
     *     -> http://situs/assets/css/tema-nusantara.css?v=1790736000
     *
     * Angkanya adalah waktu berkas terakhir diubah. Begitu berkasnya
     * disunting, alamatnya ikut berubah dan browser terpaksa mengambil
     * yang baru.
     *
     * Tanpa ini, browser menyimpan CSS lama berhari-hari: perubahan sudah
     * terpasang di server, tapi yang terlihat di layar masih yang lama -
     * dan itu sangat sulit dikenali karena tidak ada pesan galat apa pun.
     */
    function aset($path)
    {
        $penuh = FCPATH . ltrim($path, '/');
        $waktu = is_file($penuh) ? filemtime($penuh) : NULL;

        return base_url($path) . ($waktu ? '?v=' . $waktu : '');
    }
}
