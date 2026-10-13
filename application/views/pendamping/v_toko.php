<!-- application/views/pendamping/v_toko.php -->
<div class="panel-judul">
  <div>
    <h1><?= html_escape($t['name']) ?></h1>
    <p class="hint"><?= html_escape($t['pemilik']) ?> &middot; <?= html_escape($t['district_name']) ?>, <?= html_escape($t['regency_name']) ?>
      &middot; <a href="https://wa.me/<?= html_escape($t['phone']) ?>" target="_blank" rel="noopener">WA <?= html_escape($t['phone']) ?></a></p>
  </div>
  <a href="<?= site_url('pendamping') ?>" class="btn btn-black-hover-outline">Semua toko</a>
</div>

<div class="adm-angka">
  <div class="adm-kartu"><span class="adm-nilai"><?= $nk['level']['ikon'] ?> <?= (int) $nk['skor'] ?></span><span class="adm-label">Skor &middot; <?= html_escape($nk['level']['nama']) ?></span></div>
  <div class="adm-kartu"><span class="adm-nilai"><?= rupiah($jual['total']['omzet']) ?></span><span class="adm-label">Omzet 30 hari</span>
    <span class="adm-catatan"><?= (int) $jual['total']['pesanan'] ?> pesanan &middot; <?= (int) $jual['total']['pembeli'] ?> pembeli</span></div>
  <div class="adm-kartu"><span class="adm-nilai"><?= rupiah(end($bulan)['laba']) ?></span><span class="adm-label">Laba bulan ini (pembukuan)</span>
    <span class="adm-catatan">Bulan lalu <?= rupiah($bulan[1]['laba']) ?></span></div>
</div>

<div class="row">
  <div class="col-lg-6 mb-4">
    <h2 class="panel-sub">Yang perlu dibantu</h2>
    <div class="adm-tabel mb-3">
      <?php foreach ($nk['kriteria'] as $k): if ($k['ok']) { continue; } ?>
        <div class="adm-baris"><div><strong><?= html_escape($k['judul']) ?></strong><em><?= html_escape($k['saran']) ?></em></div>
          <div class="adm-aksi"><span class="hint">+<?= (int) $k['poin'] ?></span></div></div>
      <?php endforeach; ?>
    </div>
    <h2 class="panel-sub">Legalitas</h2>
    <?php if ( ! $dok): ?><p class="hint">Belum mengunggah dokumen apa pun.</p><?php endif; ?>
    <?php foreach ($dok as $d): ?>
      <p class="mb-1"><strong><?= strtoupper(html_escape($d['jenis'])) ?></strong> <?= html_escape($d['nomor']) ?> &middot;
        <span class="badge-status <?= $d['status'] === 'terverifikasi' ? 'is-ok' : 'is-wait' ?>"><?= html_escape($d['status']) ?></span>
        <?php if ($d['berkas']): ?><a href="<?= site_url('pendamping/berkas/' . (int) $d['id']) ?>" target="_blank">berkas</a><?php endif; ?></p>
    <?php endforeach; ?>
  </div>
  <div class="col-lg-6">
    <h2 class="panel-sub">Catat pendampingan</h2>
    <?= form_open('pendamping/catat/' . (int) $t['id']) ?>
    <div class="form-card">
      <div class="row">
        <div class="col-6 mb-2"><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></div>
        <div class="col-6 mb-2"><select name="jenis" class="form-select"><option value="kunjungan">Kunjungan</option><option value="telepon">Telepon/WA</option><option value="kelas">Kelas</option><option value="lainnya">Lainnya</option></select></div>
      </div>
      <textarea name="catatan" class="form-control mb-2" rows="4" required placeholder="Apa yang dibahas, kondisi usaha, kendala..."></textarea>
      <input type="text" name="tindak_lanjut" class="form-control mb-2" maxlength="255" placeholder="Tindak lanjut (contoh: urus NIB minggu depan)">
      <button type="submit" class="btn btn-primary">Simpan catatan</button>
    </div>
    <?= form_close() ?>
    <?php foreach ($catatan as $c): ?>
      <div class="fitur-kartu mt-2">
        <span class="hint"><?= tgl_id($c['tanggal']) ?> &middot; <?= html_escape(ucfirst($c['jenis'])) ?> &middot; <?= html_escape($c['penulis']) ?></span>
        <p class="mb-1"><?= nl2br(html_escape($c['catatan'])) ?></p>
        <?php if ($c['tindak_lanjut']): ?><p class="mb-0"><strong>Tindak lanjut:</strong> <?= html_escape($c['tindak_lanjut']) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
