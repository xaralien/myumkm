<?php
/* Utas sengketa. Parameter: $pesan (baris sengketa_pesan), $saya ('pembeli'|'penjual'|'admin'). */
$label = array('pembeli' => 'Pembeli', 'penjual' => 'Penjual', 'admin' => 'Admin');
?>
<div class="sk-utas">
  <?php foreach ($pesan as $m): ?>
    <div class="sk-pesan sk-<?= $m['pengirim'] ?><?= $m['pengirim'] === $saya ? ' is-saya' : '' ?>">
      <span class="sk-dari"><?= $label[$m['pengirim']] ?> &middot; <?= date('d/m H:i', strtotime($m['created_at'])) ?></span>
      <p><?= nl2br(html_escape($m['isi'])) ?></p>
    </div>
  <?php endforeach; ?>
</div>
