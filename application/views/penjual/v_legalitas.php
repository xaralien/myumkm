<!-- application/views/penjual/v_legalitas.php -->
<div class="panel-judul">
  <div>
    <h1>Legalitas usaha</h1>
    <p class="hint">Dokumen yang sudah diperiksa admin tampil sebagai lencana <span class="lencana lencana-hijau">✓ terverifikasi</span>
      di tokomu &mdash; pembeli lebih yakin, dan langkahmu ke KUR lebih mudah. KTP & NPWP tidak pernah ditampilkan ke pembeli.</p>
  </div>
  <a href="<?= site_url('seller/akademi') ?>" class="btn btn-black-hover-outline">📚 Panduan mengurus izin</a>
</div>

<div class="fitur-grid dok-grid">
  <?php foreach ($jenis as $kode => $j): $d = isset($dok[$kode]) ? $dok[$kode] : NULL; ?>
    <div class="fitur-kartu">
      <div class="d-flex justify-content-between align-items-start gap-2">
        <h3><?= html_escape($j[0]) ?></h3>
        <?php if ($d): ?>
          <span class="badge-status <?= $d['status'] === 'terverifikasi' ? 'is-ok' : ($d['status'] === 'ditolak' ? 'is-bad' : 'is-wait') ?>">
            <?= $d['status'] === 'terverifikasi' ? '✓ Terverifikasi' : ($d['status'] === 'ditolak' ? 'Ditolak' : 'Diperiksa admin') ?>
          </span>
        <?php endif; ?>
      </div>
      <p class="hint"><?= html_escape($j[1]) ?></p>
      <?php if ($d && $d['status'] === 'ditolak' && $d['catatan_admin']): ?>
        <p class="alert-box alert-error py-2">Alasan: <?= html_escape($d['catatan_admin']) ?></p>
      <?php endif; ?>
      <?php if ($d): ?>
        <p class="mb-2"><strong><?= html_escape($d['nomor']) ?></strong>
          <?php if ($d['berkas']): ?> &middot; <a href="<?= site_url('seller/legalitas/berkas/' . (int) $d['id']) ?>" target="_blank">lihat berkas</a><?php endif; ?></p>
      <?php endif; ?>

      <details <?= ! $d || $d['status'] === 'ditolak' ? 'open' : '' ?>>
        <summary><?= $d ? 'Ganti / perbarui' : 'Unggah' ?></summary>
        <?= form_open_multipart('seller/legalitas/simpan', array('class' => 'mt-2')) ?>
          <input type="hidden" name="jenis" value="<?= $kode ?>">
          <input type="text" name="nomor" class="form-control mb-2" maxlength="80" placeholder="Nomor dokumen"
                 value="<?= html_escape($d ? $d['nomor'] : '') ?>">
          <label class="form-label hint mb-1">Berlaku sampai (kalau ada)</label>
          <input type="date" name="berlaku_sampai" class="form-control mb-2" value="<?= html_escape($d ? $d['berlaku_sampai'] : '') ?>">
          <input type="file" name="berkas" class="form-control mb-2" accept="image/jpeg,image/png,image/webp,application/pdf">
          <button type="submit" class="btn btn-primary btn-sm">Kirim untuk diperiksa</button>
        <?= form_close() ?>
      </details>
    </div>
  <?php endforeach; ?>
</div>
