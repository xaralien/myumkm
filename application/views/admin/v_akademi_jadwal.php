<!-- application/views/admin/v_akademi_jadwal.php -->
<div class="panel-judul">
  <h1><?= $j ? 'Ubah jadwal' : 'Jadwal baru' ?></h1>
  <a href="<?= site_url('admin/akademi') ?>" class="btn btn-black-hover-outline">Kembali</a>
</div>
<div class="row">
  <div class="col-lg-7">
    <?= form_open('admin/akademi/jadwal_simpan') ?>
    <input type="hidden" name="id" value="<?= $j ? (int) $j['id'] : '' ?>">
    <div class="form-card">
      <div class="mb-3"><label class="form-label">Judul *</label>
        <input type="text" name="judul" class="form-control" maxlength="150" required value="<?= html_escape($j['judul'] ?? '') ?>" placeholder="contoh: Kelas Foto Produk - Kec. Coblong"></div>
      <div class="row">
        <div class="col-md-4 mb-3"><label class="form-label">Jenis</label>
          <select name="jenis" class="form-select"><option value="kelas">Kelas</option><option value="konsultasi" <?= ($j['jenis'] ?? '') === 'konsultasi' ? 'selected' : '' ?>>Konsultasi</option></select></div>
        <div class="col-md-4 mb-3"><label class="form-label">Tanggal *</label>
          <input type="date" name="tanggal" class="form-control" required value="<?= $j ? substr($j['waktu'], 0, 10) : '' ?>"></div>
        <div class="col-md-4 mb-3"><label class="form-label">Jam *</label>
          <input type="time" name="jam" class="form-control" required value="<?= $j ? substr($j['waktu'], 11, 5) : '09:00' ?>"></div>
      </div>
      <div class="mb-3"><label class="form-label">Tempat / tautan</label>
        <input type="text" name="tempat" class="form-control" maxlength="255" value="<?= html_escape($j['tempat'] ?? '') ?>" placeholder="Balai desa ... atau https://meet.google.com/..."></div>
      <div class="mb-3"><label class="form-label">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="3" maxlength="500"><?= html_escape($j['keterangan'] ?? '') ?></textarea></div>
      <div class="mb-3"><label class="form-label">Kuota peserta <span class="hint">(kosong = tidak dibatasi)</span></label>
        <input type="number" name="kuota" class="form-control" min="1" value="<?= html_escape($j['kuota'] ?? '') ?>"></div>
      <button type="submit" class="btn btn-primary">Simpan jadwal</button>
    </div>
    <?= form_close() ?>
  </div>
  <div class="col-lg-5">
    <h2 class="panel-sub">Peserta (<?= count($peserta) ?>)</h2>
    <?php if ( ! $peserta): ?><p class="hint">Belum ada yang mendaftar.</p><?php endif; ?>
    <div class="adm-tabel">
      <?php foreach ($peserta as $p): ?>
        <div class="adm-baris"><div><strong><?= html_escape($p['name']) ?></strong><em><?= html_escape($p['phone']) ?></em></div>
          <div class="adm-aksi"><a href="https://wa.me/<?= html_escape($p['phone']) ?>" target="_blank" class="btn btn-sm btn-black-hover-outline">WA</a></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
