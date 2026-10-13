<?php /* Daftar voucher - panel penjual (seller/voucher) & admin (admin/voucher). */ ?>
<div class="panel-judul">
  <div>
    <h1><?= html_escape($judul) ?></h1>
    <p class="hint"><?= html_escape($sub) ?></p>
  </div>
  <a href="<?= site_url($basis . '/form') ?>" class="btn btn-primary">+ Buat voucher</a>
</div>

<?php if ( ! $voucher): ?>
  <div class="fitur-kosong">
    <p class="mb-3">Belum ada voucher. Voucher membantu menarik pembeli baru dan membuat pelanggan kembali.</p>
    <a href="<?= site_url($basis . '/form') ?>" class="btn btn-primary btn-besar">Buat voucher pertama</a>
  </div>
<?php else: ?>
  <div class="adm-tabel">
    <?php foreach ($voucher as $v):
      $kini = date('Y-m-d H:i:s');
      $berlaku = $v['is_active'] && ( ! $v['mulai'] || $v['mulai'] <= $kini) && ( ! $v['selesai'] || $v['selesai'] >= $kini)
                 && ($v['kuota'] === NULL || (int) $v['terpakai'] < (int) $v['kuota']);
    ?>
      <div class="adm-baris">
        <div>
          <strong class="voucher-kode"><?= html_escape($v['kode']) ?></strong>
          <?= html_escape($v['nama']) ?>
          <em>
            <?= html_escape($this->voucher_lib->keterangan($v)) ?>
            <?= (int) $v['min_belanja'] ? '&middot; min. belanja ' . rupiah($v['min_belanja']) : '' ?>
            &middot; terpakai <?= (int) $v['terpakai'] ?><?= $v['kuota'] !== NULL ? '/' . (int) $v['kuota'] : '' ?>
            &middot; total potongan <?= rupiah($v['total_potongan']) ?>
            <?php if ($v['selesai']): ?>&middot; s.d. <?= tgl_id(substr($v['selesai'], 0, 10)) ?><?php endif; ?>
          </em>
        </div>
        <div class="adm-aksi">
          <span class="badge-status <?= $berlaku ? 'is-ok' : 'is-wait' ?>"><?= $berlaku ? 'Berlaku' : ($v['is_active'] ? 'Tidak berlaku' : 'Nonaktif') ?></span>
          <a href="<?= site_url($basis . '/form/' . (int) $v['id']) ?>" class="btn btn-sm btn-black-hover-outline">Ubah</a>
          <?= form_open($basis . '/tukar/' . (int) $v['id'], array('class' => 'inline-form')) ?>
            <button type="submit" class="btn btn-sm btn-black-hover-outline"><?= $v['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
          <?= form_close() ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
