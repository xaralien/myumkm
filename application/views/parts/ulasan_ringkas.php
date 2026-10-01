<?php
/* =============================================================================
   Ringkasan rating.

   Kiri: nilai besar + persen puas + jumlah.
   Kanan: sebaran bintang 5..1 dalam tiga kolom - lebih padat daripada lima
   baris bertumpuk, dan seluruhnya terbaca tanpa menggulir.

   Diperlukan: $ringkasan. Opsional: $tautan_filter (fungsi: bintang -> url).
   ========================================================================== */
$r = $ringkasan;

$bintang_svg = function ($ukuran) {
    return '<svg viewBox="0 0 24 24" width="' . $ukuran . '" height="' . $ukuran . '" aria-hidden="true">'
         . '<path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>';
};
?>
<div class="ul-ringkas">

  <div class="ul-ringkas-nilai">
    <div class="ul-angka">
      <span class="ul-angka-ikon"><?= $bintang_svg(40) ?></span>
      <strong><?= number_format($r['rata'], 1, ',', '.') ?><span>/5</span></strong>
    </div>

    <?php if ($r['total'] > 0): ?>
      <!-- Persentase puas lebih mudah dicerna daripada rata-rata desimal,
           dan itulah yang biasanya dicari pembeli sebelum memutuskan. -->
      <p class="ul-puas"><?= (int) $r['puas'] ?>% pembeli puas dengan produk ini</p>
      <p class="ul-jumlah">
        <?= (int) $r['total'] ?> rating &bull; <?= (int) ($r['berisi'] ?? $r['total']) ?> ulasan
      </p>
    <?php else: ?>
      <p class="ul-jumlah">Belum ada rating</p>
    <?php endif; ?>
  </div>

  <?php if ($r['total'] > 0): ?>
    <div class="ul-sebaran">
      <?php foreach (array(5, 4, 3, 2, 1) as $b): ?>
        <?php
          $n = (int) $r['sebaran'][$b];
          // Dibagi total, bukan dibagi angka terbesar: panjang batang
          // menggambarkan porsi sebenarnya, bukan perbandingan antarbaris.
          $persen = $r['total'] ? ($n / $r['total']) * 100 : 0;

          $isi = '<span class="ul-sebaran-bintang">' . $bintang_svg(15) . '<b>' . $b . '</b></span>'
               . '<span class="ul-sebaran-bar"><i style="width: ' . round($persen, 1) . '%"></i></span>'
               . '<span class="ul-sebaran-n">' . $n . '</span>';
        ?>
        <?php if (! empty($tautan_filter) && $n > 0): ?>
          <a class="ul-sebaran-baris" href="<?= html_escape($tautan_filter($b)) ?>"><?= $isi ?></a>
        <?php else: ?>
          <div class="ul-sebaran-baris is-mati"><?= $isi ?></div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
