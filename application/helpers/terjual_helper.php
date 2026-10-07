<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('terjual_teks')) {
    /**
     * Angka terjual dalam bentuk yang dibaca pembeli.
     * Simpan di: application/helpers/terjual_helper.php
     *
     * DIBULATKAN KE BAWAH, bukan angka persis. Dua alasan:
     *
     *   - Toko baru dengan "3 terjual" terlihat sepi padahal wajar. "10+"
     *     tidak muncul sampai angkanya benar-benar sampai sana, jadi tidak
     *     membohongi, tapi juga tidak memalukan.
     *
     *   - Angka persis membuat pembeli membandingkan dua produk lewat
     *     selisih yang tidak berarti apa-apa (82 vs 79).
     *
     * @return string kosong kalau belum ada yang terjual
     */
    function terjual_teks($n)
    {
        $n = (int) $n;

        if ($n >= 1000) {
            return floor($n / 1000) . ' rb+ terjual';
        }
        if ($n >= 50) {
            return (floor($n / 50) * 50) . '+ terjual';
        }
        if ($n >= 10) {
            return (floor($n / 10) * 10) . '+ terjual';
        }

        // Di bawah 10 ditulis apa adanya - "0+ terjual" tidak masuk akal.
        return $n > 0 ? $n . ' terjual' : '';
    }
}
