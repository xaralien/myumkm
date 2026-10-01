<?php
/* =============================================================================
   Deretan media (foto & video) ulasan.

   Diperlukan: $media (array baris review_media).
   Opsional  : $maks (tampilkan sekian saja, sisanya jadi "+N"),
               $kelas ('galeri' untuk deretan besar, 'kecil' untuk per-ulasan).

   Video TIDAK diputar di tempat - yang tampil hanya petak dengan tanda
   putar. Memuat berkas video untuk setiap petak di halaman menghabiskan
   kuota pengunjung padahal kebanyakan tidak akan diputar.
   ========================================================================== */
$kelas = isset($kelas) ? $kelas : 'galeri';
$maks  = isset($maks) ? (int) $maks : 0;

$tampil = ($maks > 0) ? array_slice($media, 0, $maks) : $media;
$sisa   = ($maks > 0) ? max(0, count($media) - $maks) : 0;
?>
<div class="ul-media-baris is-<?= $kelas ?>">
  <?php foreach ($tampil as $i => $m): ?>
    <?php
      $src = base_url('upload/ulasan/' . $m['berkas']);
      $akhir = ($sisa > 0 && $i === count($tampil) - 1);
    ?>
    <a class="ul-media-petak <?= $akhir ? 'is-sisa' : '' ?>"
       href="<?= $src ?>" data-penuh data-tipe="<?= $m['tipe'] ?>"
       aria-label="<?= $m['tipe'] === 'video' ? 'Putar video dari pembeli' : 'Lihat foto dari pembeli' ?>">

      <?php if ($m['tipe'] === 'video'): ?>
        <!-- preload="metadata": cukup untuk mengambil satu bingkai sebagai
             sampul, tanpa mengunduh seluruh videonya. -->
        <video src="<?= $src ?>" preload="metadata" muted playsinline></video>
        <span class="ul-media-putar" aria-hidden="true">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M8 5v14l11-7z"></path></svg>
        </span>
      <?php else: ?>
        <img src="<?= $src ?>" alt="" loading="lazy">
      <?php endif; ?>

      <?php if ($akhir): ?>
        <span class="ul-media-lebih" aria-hidden="true">+<?= $sisa ?></span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>
