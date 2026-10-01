<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('waktu_lalu')) {
    /**
     * Jarak waktu dalam bahasa sehari-hari: "3 hari lalu", "Lebih dari 1 tahun lalu".
     * Simpan di: application/helpers/waktu_helper.php
     *
     * Dipakai di ulasan. Tanggal persis ("25/09/2026") tidak berarti banyak
     * untuk pembaca ulasan - yang ingin mereka tahu adalah ulasannya masih
     * relevan atau sudah lama.
     */
    function waktu_lalu($waktu)
    {
        $t = is_numeric($waktu) ? (int) $waktu : strtotime((string) $waktu);
        if ( ! $t) {
            return '';
        }

        $d = time() - $t;

        if ($d < 60)     { return 'Baru saja'; }
        if ($d < 3600)   { return floor($d / 60) . ' menit lalu'; }
        if ($d < 86400)  { return floor($d / 3600) . ' jam lalu'; }
        if ($d < 604800) { return floor($d / 86400) . ' hari lalu'; }

        if ($d < 2592000) {
            return floor($d / 604800) . ' minggu lalu';
        }

        if ($d < 31536000) {
            return floor($d / 2592000) . ' bulan lalu';
        }

        $tahun = floor($d / 31536000);

        /* "Lebih dari": umur ulasan yang sudah bertahun-tahun tidak perlu
           tepat, dan kata itu jujur soal ketidaktepatannya. */
        return 'Lebih dari ' . $tahun . ' tahun lalu';
    }
}
