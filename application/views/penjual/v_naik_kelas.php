<!-- application/views/penjual/v_naik_kelas.php -->
<?php $lv = $n['level']; $persen = $n['skor']; ?>
<div class="panel-judul">
  <div>
    <h1>Naik Kelas</h1>
    <p class="hint">Skor dihitung otomatis dari kelengkapan toko, penjualan, layanan, legalitas, pembukuan, dan belajar.</p>
  </div>
  <a href="<?= site_url('seller/laporan/kur') ?>" target="_blank" class="btn btn-black-hover-outline">🏦 Laporan siap KUR</a>
</div>

<div class="nk-kepala">
  <div class="nk-level nk-<?= $lv['kode'] ?>">
    <span class="nk-ikon"><?= $lv['ikon'] ?></span>
    <div>
      <span class="hint">Level tokomu</span>
      <strong><?= html_escape($lv['nama']) ?></strong>
    </div>
  </div>
  <div class="nk-skor">
    <div class="nk-angka"><strong><?= (int) $persen ?></strong>/100</div>
    <div class="nk-bar" role="progressbar" aria-valuenow="<?= (int) $persen ?>" aria-valuemin="0" aria-valuemax="100">
      <i style="width: <?= (int) $persen ?>%"></i>
      <span style="left: 40%" title="Berkembang"></span><span style="left: 70%" title="Unggul"></span>
    </div>
    <p class="hint mb-0">
      <?= $lv['berikut'] ? 'Kurang <strong>' . ($lv['batas'] - $persen) . ' poin</strong> lagi untuk menjadi <strong>' . $lv['berikut'] . '</strong>.' : 'Selamat! Tokomu sudah di level tertinggi.' ?>
    </p>
  </div>
</div>

<?php if ($n['saran']): ?>
  <h2 class="panel-sub">Langkah berikutnya</h2>
  <div class="fitur-grid mb-4">
    <?php foreach ($n['saran'] as $s): ?>
      <a href="<?= site_url($s['tautan']) ?>" class="fitur-kartu nk-saran">
        <span class="lencana lencana-emas">+<?= (int) $s['poin'] ?> poin</span>
        <h3><?= html_escape($s['judul']) ?></h3>
        <p class="hint mb-0"><?= html_escape($s['saran']) ?> &rarr;</p>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-lg-7 mb-4">
    <h2 class="panel-sub">Rincian penilaian</h2>
    <div class="adm-tabel">
      <?php foreach ($n['kriteria'] as $k): ?>
        <div class="adm-baris">
          <div>
            <span class="nk-cek <?= $k['ok'] ? 'is-ok' : '' ?>" aria-hidden="true"><?= $k['ok'] ? '✓' : '' ?></span>
            <strong><?= html_escape($k['judul']) ?></strong>
            <em><?= html_escape($k['kelompok']) ?><?= $k['ok'] ? '' : ' &middot; ' . html_escape($k['saran']) ?></em>
          </div>
          <div class="adm-aksi">
            <span class="hint"><?= $k['ok'] ? (int) $k['poin'] : 0 ?>/<?= (int) $k['poin'] ?></span>
            <?php if ( ! $k['ok']): ?><a href="<?= site_url($k['tautan']) ?>" class="btn btn-sm btn-black-hover-outline">Perbaiki</a><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-5">
    <h2 class="panel-sub">Pendamping UMKM</h2>
    <?php if ($pendamping): ?>
      <div class="fitur-kartu mb-3">
        <h3><?= html_escape($pendamping['name']) ?></h3>
        <p class="hint">Pendampingmu dari program pembinaan. Tanyakan apa saja soal usaha.</p>
        <?php if ($pendamping['phone']): ?>
          <a href="https://wa.me/<?= html_escape($pendamping['phone']) ?>" target="_blank" rel="noopener" class="bagikan-wa">Hubungi lewat WhatsApp</a>
        <?php endif; ?>
      </div>
      <?php foreach ($catatan as $c): ?>
        <div class="fitur-kartu mb-2">
          <span class="hint"><?= tgl_id($c['tanggal']) ?> &middot; <?= html_escape(ucfirst($c['jenis'])) ?></span>
          <p class="mb-1"><?= nl2br(html_escape($c['catatan'])) ?></p>
          <?php if ($c['tindak_lanjut']): ?><p class="mb-0"><strong>Tindak lanjut:</strong> <?= html_escape($c['tindak_lanjut']) ?></p><?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="fitur-kartu">
        <p class="mb-2">Belum ada pendamping untuk tokomu.</p>
        <p class="hint mb-0">Ikuti kelas di <a href="<?= site_url('seller/akademi') ?>">Akademi</a> &mdash; pendamping biasanya ditetapkan untuk peserta program pembinaan daerahmu.</p>
      </div>
    <?php endif; ?>
  </div>
</div>
