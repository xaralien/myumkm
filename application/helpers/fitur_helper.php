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

/* =============================================================================
   PREFERENSI TAMPILAN - huruf besar, hemat kuota, bahasa.
   Disimpan di cookie (tanpa akun pun bisa), dibaca lewat fungsi di bawah.
   ========================================================================== */

if ( ! function_exists('preferensi_daftar')) {
    /** Kunci & nilai yang sah. Nilai pertama = bawaan. */
    function preferensi_daftar()
    {
        return array(
            'huruf'  => array('normal' => 'Normal', 'besar' => 'Besar'),
            'hemat'  => array('0' => 'Mati', '1' => 'Nyala'),
            'bahasa' => array('id' => 'Indonesia', 'jv' => 'Jawa', 'su' => 'Sunda'),
        );
    }
}

if ( ! function_exists('preferensi')) {
    function preferensi($kunci)
    {
        $daftar = preferensi_daftar();
        if ( ! isset($daftar[$kunci])) {
            return NULL;
        }
        $v = isset($_COOKIE['pref_' . $kunci]) ? (string) $_COOKIE['pref_' . $kunci] : '';
        return array_key_exists($v, $daftar[$kunci]) ? $v : (string) array_keys($daftar[$kunci])[0];
    }
}

if ( ! function_exists('preferensi_kelas')) {
    /** Kelas untuk <html>: dipakai CSS (huruf besar, hemat kuota). */
    function preferensi_kelas()
    {
        $k = array();
        if (preferensi('huruf') === 'besar') { $k[] = 'huruf-besar'; }
        if (preferensi('hemat') === '1')     { $k[] = 'hemat-kuota'; }
        return implode(' ', $k);
    }
}

if ( ! function_exists('t')) {
    /**
     * Terjemahan ringan ke bahasa daerah untuk menu & tombol utama.
     * Teks yang belum ada di kamus tampil apa adanya (bahasa Indonesia) -
     * jadi halaman tidak pernah kosong karena terjemahan belum lengkap.
     */
    function t($teks)
    {
        static $kamus = array();
        $b = preferensi('bahasa');
        if ($b === 'id') {
            return $teks;
        }
        if ( ! isset($kamus[$b])) {
            $f = APPPATH . 'language/daerah/' . $b . '.php';
            $kamus[$b] = is_file($f) ? (include $f) : array();
        }
        return isset($kamus[$b][$teks]) ? $kamus[$b][$teks] : $teks;
    }
}

if ( ! function_exists('gps_config_js')) {
    /**
     * Konfigurasi tombol lokasi (assets/js/gps.js) sebagai objek JS.
     * gkey = kunci Google Maps dari Admin > Pengaturan > Peta (opsional);
     * kosong berarti Google dilewati.
     */
    function gps_config_js()
    {
        $CI = &get_instance();
        $CI->load->library('pengaturan_lib');
        return json_encode(array(
            'simpan' => site_url('location/titik'),
            'cocok'  => site_url('region/cocok'),
            'gkey'   => (string) $CI->pengaturan_lib->get('google_maps_key'),
        ), JSON_UNESCAPED_SLASHES);
    }
}
