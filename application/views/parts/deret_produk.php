<?php
/* Deret produk berjudul. Parameter: $judul, $produk, $sub, $tautan, [$id].
   Kirim 'sub' & 'tautan' (boleh NULL) - variabel view CI terbawa antar-pemanggilan. */
if (empty($produk)) { return; }
?>
<section class="deret"<?= ! empty($id) ? ' id="' . html_escape($id) . '"' : '' ?>>
  <div class="deret-kepala">
    <div>
      <h2><?= html_escape($judul) ?></h2>
      <?php if ( ! empty($sub)): ?><p class="hint mb-0"><?= html_escape($sub) ?></p><?php endif; ?>
    </div>
    <?php if ( ! empty($tautan)): ?><a href="<?= $tautan ?>" class="deret-semua">Lihat semua &rarr;</a><?php endif; ?>
  </div>
  <div class="deret-geser">
    <?php foreach ($produk as $p): ?>
      <?php $this->load->view('parts/kartu_produk', array('p' => $p, 'redup' => FALSE)); ?>
    <?php endforeach; ?>
  </div>
</section>
