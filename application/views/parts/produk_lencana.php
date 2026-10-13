<?php
/* Lencana produk UMKM. Parameter: $p (baris produk), [$verif] daftar jenis
   dokumen toko yang sudah diverifikasi admin, mis. array('halal','pirt').
   Nomor Halal/P-IRT tanpa verifikasi tetap ditampilkan - sebagai klaim
   penjual, bukan jaminan. */
$verif = isset($verif) ? (array) $verif : array();
$l = array();
if ( ! empty($p['terjual_count']) && (int) $p['terjual_count'] >= 10) {
    $l[] = array('lencana-emas', 'Terlaris');
}
if ( ! empty($p['buatan_tangan'])) {
    $l[] = array('', 'Buatan tangan');
}
if ( ! empty($p['bahan_lokal'])) {
    $l[] = array('', 'Bahan lokal');
}
if ( ! empty($p['halal_no'])) {
    $l[] = array(in_array('halal', $verif, TRUE) ? 'lencana-hijau' : '',
                 'Halal' . (in_array('halal', $verif, TRUE) ? ' ✓' : ''), 'No. ' . $p['halal_no']);
}
if ( ! empty($p['pirt_no'])) {
    $l[] = array(in_array('pirt', $verif, TRUE) ? 'lencana-hijau' : '',
                 'P-IRT' . (in_array('pirt', $verif, TRUE) ? ' ✓' : ''), 'No. ' . $p['pirt_no']);
}
?>
<?php if ($l): ?>
  <div class="lencana-baris produk-lencana">
    <?php foreach ($l as $x): ?>
      <span class="lencana <?= $x[0] ?>"<?= isset($x[2]) ? ' title="' . html_escape($x[2]) . '"' : '' ?>>
        <?= html_escape($x[1]) ?><?php if (isset($x[2])): ?> <small><?= html_escape($x[2]) ?></small><?php endif; ?>
      </span>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
