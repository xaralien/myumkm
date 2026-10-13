<!-- application/views/admin/v_verifikasi.php -->
<div class="panel-judul">
  <div>
    <h1>Verifikasi legalitas</h1>
    <p class="hint">Cocokkan nomor dengan berkas, dan untuk NIB cek di oss.go.id. Dokumen terverifikasi memberi lencana di toko.</p>
  </div>
</div>
<nav class="fitur-saring mb-3">
  <?php foreach (array('menunggu' => 'Menunggu', 'terverifikasi' => 'Terverifikasi', 'ditolak' => 'Ditolak') as $k => $l): ?>
    <a href="<?= site_url('admin/verifikasi?status=' . $k) ?>" class="<?= $status === $k ? 'aktif' : '' ?>"><?= $l ?> (<?= (int) ($jumlah[$k] ?? 0) ?>)</a>
  <?php endforeach; ?>
</nav>
<?php if ( ! $dok): ?>
  <div class="fitur-kosong">Tidak ada dokumen di sini.</div>
<?php else: ?>
  <div class="adm-tabel">
    <?php foreach ($dok as $d): ?>
      <div class="adm-baris">
        <div>
          <strong><?= html_escape($jenis[$d['jenis']][0]) ?></strong> &middot;
          <a href="<?= site_url('toko/' . $d['store_slug']) ?>" target="_blank"><?= html_escape($d['store_name']) ?></a>
          <em>No. <?= html_escape($d['nomor'] ?: '-') ?> &middot; <?= html_escape($d['pemilik']) ?>
            <?= $d['berlaku_sampai'] ? '&middot; berlaku s.d. ' . tgl_id($d['berlaku_sampai']) : '' ?>
            &middot; dikirim <?= tgl_id(substr($d['updated_at'] ?: $d['created_at'], 0, 10)) ?>
            <?= $d['catatan_admin'] ? '&middot; catatan: ' . html_escape($d['catatan_admin']) : '' ?></em>
        </div>
        <div class="adm-aksi">
          <?php if ($d['berkas']): ?><a href="<?= site_url('admin/verifikasi/berkas/' . (int) $d['id']) ?>" target="_blank" class="btn btn-sm btn-black-hover-outline">Lihat berkas</a><?php endif; ?>
          <?php if ($status !== 'terverifikasi'): ?>
            <?= form_open('admin/verifikasi/putus/' . (int) $d['id'], array('class' => 'inline-form')) ?>
              <input type="hidden" name="setuju" value="1">
              <button type="submit" class="btn btn-sm btn-primary">Verifikasi</button>
            <?= form_close() ?>
          <?php endif; ?>
          <?php if ($status !== 'ditolak'): ?>
            <details class="tolak-detail">
              <summary class="btn btn-sm btn-black-hover-outline">Tolak</summary>
              <?= form_open('admin/verifikasi/putus/' . (int) $d['id']) ?>
                <input type="hidden" name="setuju" value="0">
                <input type="text" name="catatan" class="form-control form-control-sm mb-1" placeholder="Alasan (dikirim ke penjual)" required maxlength="255">
                <button type="submit" class="btn btn-sm btn-black-hover-outline">Kirim penolakan</button>
              <?= form_close() ?>
            </details>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
