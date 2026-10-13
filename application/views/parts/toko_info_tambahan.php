<?php
/* Bagian tambahan di halaman toko (desktop & HP): ikuti toko, lencana
   toko, bagikan. Data dari Toko::index() -> $toko_ekstra. */
$e = isset($toko_ekstra) ? $toko_ekstra : array();
?>
<div class="toko-ekstra">
  <?php if ( ! empty($e['lencana'])): ?>
    <div class="lencana-baris mb-2">
      <?php foreach ($e['lencana'] as $l): ?>
        <span class="lencana <?= html_escape($l[0]) ?>"><?= html_escape($l[1]) ?></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="produk-ekstra">
    <?php $this->load->view('parts/tombol_ikuti', array(
        'toko_id'  => $toko['id'],
        'aktif'    => ! empty($e['mengikuti']),
        'pengikut' => isset($e['pengikut']) ? $e['pengikut'] : 0,
    )); ?>
    <?php $this->load->view('parts/bagikan', array(
        'teks' => $toko['name'] . ' - UMKM dari ' . $toko['regency_name'],
        'url'  => site_url('toko/' . $toko['slug']),
    )); ?>
  </div>
</div>
