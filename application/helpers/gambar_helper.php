<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('rapikan_gambar')) {
    /**
     * Seragamkan ukuran gambar yang baru diunggah.
     * Simpan di: application/helpers/gambar_helper.php
     *
     * Gambar diperkecil supaya MUAT di dalam kotak persegi, lalu ditaruh di
     * tengah kanvas persegi berwarna. Jadi:
     *
     *   - semua gambar berukuran sama persis, katalog jadi rata
     *   - tidak ada bagian gambar yang terpotong - buket tinggi dan papan
     *     bunga yang lebar sama-sama utuh
     *
     * Ini dikerjakan SAAT UNGGAH, bukan lewat CSS. CSS hanya menyembunyikan
     * bagian gambar di layar; berkasnya sendiri tetap beragam ukuran, dan
     * yang besar tetap diunduh utuh oleh pembeli.
     *
     * @param  string $path  berkas yang baru disimpan (ditimpa di tempat)
     * @param  int    $sisi  panjang sisi kanvas
     * @param  array  $latar warna latar RGB
     * @return bool   FALSE kalau gagal - berkas aslinya dibiarkan apa adanya
     */
    function rapikan_gambar($path, $sisi = 900, array $latar = array(255, 255, 255))
    {
        if ( ! extension_loaded('gd') || ! is_file($path)) {
            /* Tanpa GD, berkas asli tetap dipakai. Halaman tetap tampil -
               hanya ukurannya saja yang tidak seragam. Lebih baik begitu
               daripada unggahan gagal total. */
            return FALSE;
        }

        $info = @getimagesize($path);
        if ($info === FALSE) {
            return FALSE;
        }

        list($lebar, $tinggi) = $info;
        if ($lebar < 1 || $tinggi < 1) {
            return FALSE;
        }

        switch ($info[2]) {
            case IMAGETYPE_JPEG: $sumber = @imagecreatefromjpeg($path); break;
            case IMAGETYPE_PNG:  $sumber = @imagecreatefrompng($path);  break;
            case IMAGETYPE_WEBP:
                $sumber = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : FALSE;
                break;
            default: return FALSE;
        }

        if ( ! $sumber) {
            return FALSE;
        }

        /* Gambar kecil TIDAK dibesarkan - membesarkan hanya membuat berkasnya
           berat dan gambarnya kabur. Yang kecil tetap ditaruh di tengah
           kanvas dengan ukuran aslinya. */
        $skala = min(1, $sisi / max($lebar, $tinggi));

        $lebar_baru  = max(1, (int) round($lebar * $skala));
        $tinggi_baru = max(1, (int) round($tinggi * $skala));

        $kanvas = imagecreatetruecolor($sisi, $sisi);

        $warna = imagecolorallocate($kanvas, $latar[0], $latar[1], $latar[2]);
        imagefilledrectangle($kanvas, 0, 0, $sisi, $sisi, $warna);

        // Ditengahkan pada kedua sumbu.
        $x = (int) (($sisi - $lebar_baru) / 2);
        $y = (int) (($sisi - $tinggi_baru) / 2);

        imagecopyresampled($kanvas, $sumber, $x, $y, 0, 0,
                           $lebar_baru, $tinggi_baru, $lebar, $tinggi);

        /* Disimpan dalam format yang SAMA dengan ekstensi berkasnya. Kalau
           isinya JPEG tapi namanya .png, sebagian browser dan alat
           pengolah gambar menolaknya - dan galatnya muncul jauh di
           kemudian hari, saat berkas itu dibuka di tempat lain. */
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'png') {
            $ok = @imagepng($kanvas, $path, 6);
        } elseif ($ext === 'webp' && function_exists('imagewebp')) {
            $ok = @imagewebp($kanvas, $path, 85);
        } else {
            $ok = @imagejpeg($kanvas, $path, 85);
        }

        imagedestroy($kanvas);
        imagedestroy($sumber);

        return (bool) $ok;
    }
}
