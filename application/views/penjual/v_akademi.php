<!-- application/views/penjual/v_akademi.php -->
<div class="panel-judul">
  <div>
    <h1>Akademi UMKM</h1>
    <p class="hint">Belajar 5 menit sehari: foto produk, harga, pemasaran, keuangan, dan perizinan. Selesaikan semua modul untuk mendapat sertifikat.</p>
  </div>
  <?php if ($modul && $selesai === count($modul)): ?>
    <a href="<?= site_url('seller/akademi/sertifikat') ?>" target="_blank" class="btn btn-primary">🎓 Sertifikat</a>
  <?php endif; ?>
</div>

<div class="mulai-kemajuan mb-4">
  <strong><?= (int) $selesai ?> dari <?= count($modul) ?> modul selesai</strong>
  <div class="nk-bar"><i style="width: <?= count($modul) ? round($selesai / count($modul) * 100) : 0 ?>%"></i></div>
</div>

<?php if ($jadwal): ?>
  <h2 class="panel-sub">Kelas &amp; konsultasi terdekat</h2>
  <div class="fitur-grid mb-4">
    <?php foreach ($jadwal as $j): ?>
      <div class="fitur-kartu">
        <span class="lencana <?= $j['jenis'] === 'kelas' ? 'lencana-hijau' : 'lencana-emas' ?>"><?= $j['jenis'] === 'kelas' ? 'Kelas' : 'Konsultasi' ?></span>
        <h3 class="mt-2"><?= html_escape($j['judul']) ?></h3>
        <p class="hint mb-1">🗓 <?= tgl_id(substr($j['waktu'], 0, 10)) ?>, pukul <?= substr($j['waktu'], 11, 5) ?></p>
        <?php if ($j['tempat']): ?><p class="hint mb-1">📍 <?= html_escape($j['tempat']) ?></p><?php endif; ?>
        <?php if ($j['keterangan']): ?><p class="mb-2"><?= html_escape($j['keterangan']) ?></p><?php endif; ?>
        <?php if ( ! empty($j['terdaftar'])): ?>
          <span class="badge-status is-ok">✓ Kamu terdaftar</span>
        <?php elseif ($j['kuota'] !== NULL && (int) $j['peserta'] >= (int) $j['kuota']): ?>
          <span class="badge-status">Penuh</span>
        <?php else: ?>
          <?= form_open('seller/akademi/daftar/' . (int) $j['id']) ?>
            <button type="submit" class="btn btn-primary btn-sm">Daftar<?= $j['kuota'] !== NULL ? ' (sisa ' . ((int) $j['kuota'] - (int) $j['peserta']) . ')' : '' ?></button>
          <?= form_close() ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<h2 class="panel-sub">Modul belajar</h2>
<?php if ( ! $modul): ?>
  <div class="fitur-kosong">Modul belum tersedia.</div>
<?php else: ?>
  <div class="fitur-grid">
    <?php foreach ($modul as $m): ?>
      <a href="<?= site_url('seller/akademi/modul/' . $m['slug']) ?>" class="fitur-kartu nk-saran modul-kartu <?= $m['selesai_at'] ? 'is-ok' : '' ?>">
        <span class="hint"><?= html_escape($m['kategori']) ?> &middot; <?= (int) $m['durasi_menit'] ?> menit<?= $m['video_url'] ? ' &middot; ▶ video' : '' ?></span>
        <h3><?= html_escape($m['judul']) ?></h3>
        <p class="hint mb-2"><?= html_escape($m['ringkas']) ?></p>
        <?= $m['selesai_at'] ? '<span class="badge-status is-ok">✓ Selesai</span>' : '<span class="badge-status is-wait">Mulai belajar →</span>' ?>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
