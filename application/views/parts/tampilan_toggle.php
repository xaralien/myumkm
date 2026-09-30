<?php
/* =============================================================================
   Sakelar tampilan - dipakai di menu akun (desktop & HP).

   Tiga keadaan, bukan dua: kiri desktop, kanan HP, dan "Ikuti perangkat"
   untuk melepas kuncian. Tanpa pilihan ketiga, sekali menyentuh sakelar
   berarti tampilan berhenti menyesuaikan diri selamanya - dan itu yang
   membuatnya terasa macet.
   ========================================================================== */
$CI =& get_instance();
$CI->load->library('tampilan_lib');

$mobile   = $CI->tampilan_lib->is_mobile();
$otomatis = $CI->tampilan_lib->otomatis();
?>
<div class="tampilan-sakelar">
  <span class="tampilan-label">Tampilan</span>

  <div class="tampilan-geser" role="group" aria-label="Pilih tampilan">
    <a href="<?= html_escape($CI->tampilan_lib->url_mode('desktop')) ?>"
       class="<?= (! $otomatis && ! $mobile) ? 'is-aktif' : '' ?>"
       <?= (! $otomatis && ! $mobile) ? 'aria-current="true"' : '' ?>>Desktop</a>

    <a href="<?= html_escape($CI->tampilan_lib->url_mode('mobile')) ?>"
       class="<?= (! $otomatis && $mobile) ? 'is-aktif' : '' ?>"
       <?= (! $otomatis && $mobile) ? 'aria-current="true"' : '' ?>>HP</a>
  </div>

  <?php if ($otomatis): ?>
    <em class="tampilan-ket">Mengikuti ukuran layar<?= $mobile ? ' &middot; HP' : ' &middot; desktop' ?></em>
  <?php else: ?>
    <a href="<?= html_escape($CI->tampilan_lib->url_mode('auto')) ?>" class="tampilan-auto">
      Ikuti ukuran layar
    </a>
  <?php endif; ?>
</div>
