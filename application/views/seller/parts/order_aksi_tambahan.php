<?php
/* Aksi tambahan di baris pesanan penjual. Parameter: $o (baris pesanan).
   Ditambah tombol lain (cetak label, nota) oleh fitur berikutnya. */
?>
<?php if ($o['alasan'] === 'belum_lunas'): ?>
  <?= form_open('seller/ingatkan_lunas/' . (int) $o['id'], array('class' => 'inline-form')) ?>
    <button type="submit" class="btn btn-sm btn-black-hover-outline">Ingatkan pelunasan</button>
  <?= form_close() ?>
<?php endif; ?>
