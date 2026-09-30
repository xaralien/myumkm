<?php
/* =============================================================================
   Rapikan gambar yang SUDAH diunggah sebelum penyeragaman dipasang.
   Simpan di: tools/rapikan_gambar_lama.php

   Jalankan sekali lewat terminal, dari folder project:

       php tools/rapikan_gambar_lama.php            (lihat dulu, tidak mengubah)
       php tools/rapikan_gambar_lama.php --jalan    (benar-benar mengubah)

   CADANGKAN folder upload/ dulu. Gambar ditimpa di tempat dan aslinya
   tidak bisa dikembalikan.
   ========================================================================== */

if (PHP_SAPI !== 'cli') {
    exit("Hanya bisa dijalankan lewat terminal.\n");
}

define('BASEPATH', __DIR__);
require __DIR__ . '/../application/helpers/gambar_helper.php';

$jalan = in_array('--jalan', $argv, TRUE);

$folder = array(
    __DIR__ . '/../upload/produk' => 900,
    __DIR__ . '/../upload/avatar' => 400,
);

$total = $diubah = $dilewat = 0;

foreach ($folder as $dir => $sisi) {
    if ( ! is_dir($dir)) {
        continue;
    }

    foreach (glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $berkas) {
        $total++;

        $info = @getimagesize($berkas);
        if ($info === FALSE) {
            continue;
        }

        // Yang sudah persegi dan seukuran target dilewati - memproses ulang
        // gambar JPEG berkali-kali menurunkan mutunya sedikit demi sedikit.
        if ($info[0] === $info[1] && $info[0] === $sisi) {
            $dilewat++;
            continue;
        }

        printf("%-46s %5dx%-5d -> %dx%d\n",
            basename($berkas), $info[0], $info[1], $sisi, $sisi);

        if ($jalan && rapikan_gambar($berkas, $sisi)) {
            $diubah++;
        }
    }
}

echo "\n";
printf("%d berkas diperiksa, %d sudah rapi, %d %s\n",
    $total, $dilewat, $jalan ? $diubah : ($total - $dilewat),
    $jalan ? 'diubah' : 'akan diubah');

if ( ! $jalan) {
    echo "\nIni baru pratinjau. Tambahkan --jalan untuk benar-benar mengubah.\n";
}
