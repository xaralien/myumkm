<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('html_aman')) {
    /**
     * Saring HTML dari editor teks (Summernote) sebelum disimpan/ditampilkan.
     * Simpan di: application/helpers/html_helper.php
     *
     * KENAPA INI WAJIB
     * Begitu deskripsi produk boleh berisi HTML, penjual bisa menuliskan
     * <script> atau onclick di dalamnya - dan itu berjalan di browser
     * SETIAP pembeli yang membuka halaman produknya. Editor di browser
     * tidak bisa dipercaya untuk mencegahnya: siapa pun bisa mengirim
     * langsung ke server tanpa lewat editor.
     *
     * Cara kerjanya: hanya tag dan atribut dalam daftar izin yang lolos.
     * Apa pun di luar itu dibuang - termasuk tag yang belum terpikirkan
     * hari ini. Daftar larangan selalu ketinggalan; daftar izin tidak.
     *
     * @param  string $html
     * @param  int    $maks batas jumlah karakter
     * @return string HTML yang aman ditampilkan apa adanya
     */
    function html_aman($html, $maks = 8000)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        // Tag yang isinya ikut dibuang - bukan cuma tagnya.
        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*/?>#is', '', $html);

        $izin = '<p><br><b><strong><i><em><u><s><ul><ol><li><a><h4><h5><blockquote><span><div>';
        $html = strip_tags($html, $izin);

        if ( ! class_exists('DOMDocument')) {
            /* Tanpa ekstensi DOM, atribut dibuang seluruhnya lewat pola -
               termasuk href, jadi tautan berubah jadi teks biasa. Lebih
               aman kehilangan tautan daripada meloloskan onclick. */
            $html = preg_replace('#<([a-z][a-z0-9]*)\b[^>]*>#i', '<$1>', $html);
            return mb_substr($html, 0, $maks);
        }

        $dom = new DOMDocument('1.0', 'UTF-8');

        $sebelumnya = libxml_use_internal_errors(TRUE);

        /* Ditandai UTF-8 lewat meta, bukan mb_convert_encoding: tanpa itu
           DOMDocument menganggap masukan sebagai ISO-8859-1 dan huruf
           beraksen berubah jadi karakter aneh. */
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="akar">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($sebelumnya);

        $akar = $dom->getElementById('akar');
        if ( ! $akar) {
            return mb_substr(strip_tags($html), 0, $maks);
        }

        $tag_izin = array('p', 'br', 'b', 'strong', 'i', 'em', 'u', 's',
                          'ul', 'ol', 'li', 'a', 'h4', 'h5', 'blockquote');

        $xpath = new DOMXPath($dom);

        // Dibalik: menghapus simpul sambil menelusuri daftar langsung akan
        // melewatkan sebagian simpul, karena daftarnya ikut berubah.
        $semua = iterator_to_array($xpath->query('//*', $akar));

        foreach (array_reverse($semua) as $el) {
            if ($el === $akar) {
                continue;
            }

            $nama = strtolower($el->nodeName);

            if ( ! in_array($nama, $tag_izin, TRUE)) {
                // Tag tak dikenal dibuka bungkusnya - isinya tetap terbaca.
                while ($el->firstChild) {
                    $el->parentNode->insertBefore($el->firstChild, $el);
                }
                $el->parentNode->removeChild($el);
                continue;
            }

            // Semua atribut dibuang, lalu href dikembalikan kalau layak.
            $href = ($nama === 'a') ? trim((string) $el->getAttribute('href')) : '';

            while ($el->attributes->length) {
                $el->removeAttribute($el->attributes->item(0)->nodeName);
            }

            if ($nama === 'a') {
                /* Hanya http, https, dan mailto. Tanpa pemeriksaan ini,
                   href="javascript:..." tetap lolos walau semua tag sudah
                   disaring. */
                if (preg_match('#^(https?://|mailto:)#i', $href)) {
                    $el->setAttribute('href', $href);
                    $el->setAttribute('target', '_blank');
                    // noopener: tab baru tidak bisa mengendalikan tab asal.
                    $el->setAttribute('rel', 'noopener nofollow');
                } else {
                    while ($el->firstChild) {
                        $el->parentNode->insertBefore($el->firstChild, $el);
                    }
                    $el->parentNode->removeChild($el);
                }
            }
        }

        $keluar = '';
        foreach ($akar->childNodes as $anak) {
            $keluar .= $dom->saveHTML($anak);
        }

        return mb_substr(trim($keluar), 0, $maks);
    }
}

if ( ! function_exists('html_teks')) {
    /**
     * Ambil teks polosnya saja - untuk meta description, cuplikan, dan
     * tempat lain yang tidak boleh berisi tag.
     */
    function html_teks($html, $maks = 300)
    {
        /* Spasi disisipkan di batas blok lebih dulu. Tanpa itu,
           "<p>merah</p><li>12 tangkai" menjadi "merah12 tangkai" - dua
           kata yang tidak pernah bersebelahan jadi menempel. */
        $html = preg_replace('#</(p|div|li|ul|ol|h[1-6]|blockquote)>#i', ' ', (string) $html);
        $html = preg_replace('#<br\s*/?>#i', ' ', $html);

        $t = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
        $t = trim(preg_replace('/\s+/u', ' ', $t));

        return mb_substr($t, 0, $maks);
    }
}
