<?php
/* parts/lacak_resi.php - status perjalanan paket.
   Butuh $lacak dari Ekspedisi_lib::lacak() (boleh NULL). */
if (empty($lacak)) {
    return;
}
?>
<div class="lacak-resi">
  <div class="lacak-kepala">
    <div>
      <span class="hint"><?= html_escape($lacak['kurir'] ?: 'Kurir') ?></span>
      <strong class="lacak-nomor"><?= html_escape($lacak['resi']) ?></strong>
    </div>
    <button type="button" class="btn btn-sm btn-black-hover-outline" data-salin="<?= html_escape($lacak['resi']) ?>">Salin resi</button>
  </div>

  <?php if ($lacak['status']): ?>
    <p class="lacak-status mb-2">Status: <strong><?= html_escape($lacak['status']) ?></strong></p>
  <?php endif; ?>

  <?php if ($lacak['riwayat']): ?>
    <ol class="lacak-garis">
      <?php foreach ($lacak['riwayat'] as $i => $r): ?>
        <li class="<?= $i === 0 ? 'is-terbaru' : '' ?>">
          <span class="lacak-waktu"><?= html_escape($r['waktu']) ?></span>
          <span><?= html_escape($r['ket']) ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php elseif ($lacak['tautan']): ?>
    <p class="hint mb-2">Salin nomor resi di atas, lalu tempel di halaman lacak resmi kurir.</p>
  <?php endif; ?>

  <?php if ($lacak['tautan']): ?>
    <a href="<?= html_escape($lacak['tautan']) ?>" target="_blank" rel="noopener" class="lacak-tautan">Buka halaman lacak <?= html_escape($lacak['kurir']) ?> &rarr;</a>
  <?php endif; ?>
</div>
<script>
document.querySelectorAll('[data-salin]').forEach(function (b) {
  if (b.dataset.siap) return; b.dataset.siap = 1;
  b.addEventListener('click', function () {
    var t = b.getAttribute('data-salin'), ok = function () { b.textContent = 'Tersalin ✓'; };
    if (navigator.clipboard) { navigator.clipboard.writeText(t).then(ok); }
    else { var i = document.createElement('input'); i.value = t; document.body.appendChild(i); i.select(); document.execCommand('copy'); i.remove(); ok(); }
  });
});
</script>
