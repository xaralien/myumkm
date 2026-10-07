<?php
/* =============================================================================
   Baris rating + terjual di bawah nama toko.

   Diperlukan: $rating, $jumlah_ulasan, $terjual.

   Angka terjual DIBULATKAN ke bawah ("250+ terjual"), bukan angka persis.
   Dua alasan: toko baru dengan "3 terjual" terlihat sepi padahal wajar,
   dan angka persis membuat pembeli membandingkan toko lewat selisih kecil
   yang tidak berarti apa-apa.
   ========================================================================== */
/* Pembulatan dipindah ke helper terjual_teks() supaya rumusnya tidak
   ditulis ulang di kartu katalog, halaman produk, dan halaman toko -
   tiga tempat yang pasti berbeda sendiri seiring waktu. */
$teks_terjual = terjual_teks($terjual);
?>
<?php if ((int) $jumlah_ulasan > 0 || $teks_terjual): ?>
  <span class="tk-nilai">
    <?php if ((int) $jumlah_ulasan > 0): ?>
      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
      <b><?= number_format((float) $rating, 1, ',', '.') ?></b>
      <span class="tk-nilai-ulasan">(<?= (int) $jumlah_ulasan ?>)</span>
    <?php endif; ?>

    <?php if ((int) $jumlah_ulasan > 0 && $teks_terjual): ?>
      <i aria-hidden="true">&middot;</i>
    <?php endif; ?>

    <?php if ($teks_terjual): ?>
      <span class="tk-nilai-terjual"><?= $teks_terjual ?></span>
    <?php endif; ?>
  </span>
<?php endif; ?>
