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

<?php
  /* Preferensi tambahan: ukuran huruf, hemat kuota, bahasa (cookie). */
  $balik_pref = rawurlencode(uri_string());
  $baris_pref = array(
    'huruf'  => t('Ukuran huruf'),
    'hemat'  => t('Hemat kuota'),
    'bahasa' => t('Bahasa'),
  );
?>
<?php foreach ($baris_pref as $k => $label): ?>
  <div class="tampilan-sakelar pref-baris">
    <span class="tampilan-label"><?= html_escape($label) ?></span>
    <div class="tampilan-geser" role="group" aria-label="<?= html_escape($label) ?>">
      <?php foreach (preferensi_daftar()[$k] as $v => $nama): ?>
        <a href="<?= site_url('preferensi/atur?k=' . $k . '&v=' . $v . '&balik=' . $balik_pref) ?>"
           class="<?= preferensi($k) === (string) $v ? 'is-aktif' : '' ?>"
           <?= preferensi($k) === (string) $v ? 'aria-current="true"' : '' ?>><?= html_escape($nama) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endforeach; ?>
