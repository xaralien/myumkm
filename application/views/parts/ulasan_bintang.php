<?php
/* =============================================================================
   Deretan bintang.
   Diperlukan: $nilai (0-5). Opsional: $ukuran (px), $teks (tampilkan angka).
   ========================================================================== */
$nilai  = (float) $nilai;
$ukuran = isset($ukuran) ? (int) $ukuran : 16;
$penuh  = (int) floor($nilai);

// Bintang separuh dari 0.3 ke atas: di bawah itu terlihat seperti kesalahan
// pembulatan, bukan nilai yang disengaja.
$separuh = ($nilai - $penuh) >= 0.3;
?>
<span class="ul-bintang" role="img" aria-label="<?= $nilai ?> dari 5 bintang">
  <?php for ($i = 1; $i <= 5; $i++): ?>
    <?php
      $isi = ($i <= $penuh) ? 'penuh' : (($i === $penuh + 1 && $separuh) ? 'separuh' : 'kosong');
    ?>
    <svg viewBox="0 0 24 24" width="<?= $ukuran ?>" height="<?= $ukuran ?>" class="is-<?= $isi ?>" aria-hidden="true">
      <?php if ($isi === 'separuh'): ?>
        <defs>
          <linearGradient id="sep<?= $ukuran . $i ?>">
            <stop offset="50%" stop-color="currentColor"></stop>
            <stop offset="50%" stop-color="transparent"></stop>
          </linearGradient>
        </defs>
      <?php endif; ?>
      <path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z"
            fill="<?= $isi === 'separuh' ? 'url(#sep' . $ukuran . $i . ')' : 'currentColor' ?>"></path>
    </svg>
  <?php endfor; ?>
  <?php if (! empty($teks)): ?>
    <b><?= number_format($nilai, 1, ',', '.') ?></b>
  <?php endif; ?>
</span>
