<!-- application/views/penjual/v_mulai.php -->
<div class="panel-judul">
  <div>
    <h1>Toko siap jualan</h1>
    <p class="hint">Lima langkah singkat. Kerjakan berurutan &mdash; masing-masing hanya beberapa menit.</p>
  </div>
</div>

<div class="mulai-kemajuan mb-4">
  <strong><?= (int) $l['selesai'] ?> dari <?= (int) $l['total'] ?> langkah selesai</strong>
  <div class="nk-bar"><i style="width: <?= round($l['selesai'] / max(1, $l['total']) * 100) ?>%"></i></div>
</div>

<ol class="mulai-daftar">
  <?php $sudah_aktif = FALSE; foreach ($l['langkah'] as $i => $x): $aktif = ! $x['ok'] && ! $sudah_aktif; if ($aktif) { $sudah_aktif = TRUE; } ?>
    <li class="<?= $x['ok'] ? 'is-ok' : '' ?> <?= $aktif ? 'is-aktif' : '' ?>">
      <span class="mulai-no"><?= $x['ok'] ? '✓' : $i + 1 ?></span>
      <div class="mulai-isi">
        <h3><?= html_escape($x['judul']) ?></h3>
        <p class="hint mb-2"><?= html_escape($x['ket']) ?></p>
        <?php if ( ! $x['ok']): ?>
          <a href="<?= site_url($x['tautan']) ?>" class="btn <?= $aktif ? 'btn-primary btn-besar' : 'btn-black-hover-outline btn-sm' ?>">Kerjakan sekarang</a>
          <?php if ($x['video']): ?><a href="<?= site_url($x['video']) ?>" class="btn btn-sm btn-black-hover-outline">▶ Lihat panduan</a><?php endif; ?>
        <?php endif; ?>
      </div>
    </li>
  <?php endforeach; ?>
</ol>

<?php if ($l['selesai'] === $l['total']): ?>
  <div class="fitur-kartu text-center">
    <h3>🎉 Tokomu siap jualan!</h3>
    <p class="hint">Langkah berikutnya: naikkan level tokomu.</p>
    <a href="<?= site_url('seller/naik_kelas') ?>" class="btn btn-primary">Lihat skor Naik Kelas</a>
  </div>
<?php endif; ?>
