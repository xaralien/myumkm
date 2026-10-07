<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/* Exception tak tertangani. Simpan di: application/views/errors/html/error_exception.php */

require_once __DIR__ . '/galat_kerangka.php';

$rincian = '';

if (ENVIRONMENT !== 'production') {
    $b = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

    $rincian = '<div>' . $b(get_class($exception)) . ': ' . $b($message) . '</div>'
             . '<div>' . $b($exception->getFile()) . ' baris ' . (int) $exception->getLine() . '</div>';

    if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE) {
        $jejak = '';

        foreach ($exception->getTrace() as $t) {
            // Berkas milik CodeIgniter sendiri dilewati - yang menolong saat
            // menelusuri galat adalah berkas aplikasi, bukan isi framework.
            if (isset($t['file']) && strpos($t['file'], realpath(BASEPATH)) !== 0) {
                $jejak .= '<div>' . $b($t['file']) . ':' . (int) $t['line']
                       . ' &rarr; ' . $b($t['function']) . '()</div>';
            }
        }

        if ($jejak) {
            $rincian .= '<div style="margin-top:8px;opacity:.85">' . $jejak . '</div>';
        }
    }
}

galat_tampilkan(array(
    'kode'  => '',
    'judul' => 'Ada yang tidak beres',
    'pesan' => 'Halaman ini gagal dimuat. Tim kami bisa memeriksanya kalau '
             . 'kamu memberi tahu apa yang sedang kamu lakukan tadi.',
    'saran' => array(
        array('Lihat katalog', 'shop'),
        array('Lacak pesanan', 'lacak'),
    ),
    'rincian' => $rincian,
));
