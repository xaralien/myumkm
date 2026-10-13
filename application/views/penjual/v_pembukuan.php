<!-- application/views/penjual/v_pembukuan.php -->
<div class="panel-judul">
  <div>
    <h1>Pembukuan</h1>
    <p class="hint">Catat uang masuk & keluar usahamu. Penjualan di <?= html_escape($this->config->item('nama_brand')) ?> tercatat otomatis.</p>
  </div>
  <a href="<?= site_url('seller/pembukuan/ekspor?periode=' . $periode['kode']) ?>" class="btn btn-black-hover-outline">⬇ Unduh Excel</a>
</div>

<nav class="fitur-saring mb-3">
  <?php foreach ($pilihan as $k => $l): ?>
    <a href="<?= site_url('seller/pembukuan?periode=' . $k) ?>" class="<?= $periode['kode'] === (string) $k ? 'aktif' : '' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</nav>
<p class="hint"><?= tgl_id($periode['dari']) ?> &ndash; <?= tgl_id($periode['sampai']) ?></p>

<div class="adm-angka">
  <div class="adm-kartu">
    <span class="adm-nilai"><?= rupiah($ringkasan['pendapatan']) ?></span>
    <span class="adm-label">Uang masuk</span>
    <span class="adm-catatan">Marketplace <?= rupiah($ringkasan['marketplace']) ?> &middot; lainnya <?= rupiah($ringkasan['catatan']) ?></span>
  </div>
  <div class="adm-kartu">
    <span class="adm-nilai"><?= rupiah($ringkasan['pengeluaran']) ?></span>
    <span class="adm-label">Uang keluar</span>
  </div>
  <div class="adm-kartu <?= $ringkasan['laba'] < 0 ? 'is-rugi' : 'is-laba' ?>">
    <span class="adm-nilai"><?= $ringkasan['laba'] < 0 ? '&minus;' : '' ?><?= rupiah(abs($ringkasan['laba'])) ?></span>
    <span class="adm-label"><?= $ringkasan['laba'] < 0 ? 'Rugi' : 'Laba' ?></span>
    <span class="adm-catatan">Uang masuk dikurangi uang keluar</span>
  </div>
</div>

<div class="row">
  <div class="col-lg-5 mb-4">
    <div class="kas-tombol">
      <a href="<?= site_url('seller/pembukuan?periode=' . $periode['kode'] . '&catat=masuk#formKas') ?>" class="kas-masuk <?= $jenis === 'masuk' ? 'aktif' : '' ?>">+ Uang masuk</a>
      <a href="<?= site_url('seller/pembukuan?periode=' . $periode['kode'] . '&catat=keluar#formKas') ?>" class="kas-keluar <?= $jenis === 'keluar' ? 'aktif' : '' ?>">&minus; Uang keluar</a>
    </div>
    <?= form_open('seller/pembukuan/simpan', array('id' => 'formKas')) ?>
    <input type="hidden" name="jenis" value="<?= $jenis ?>">
    <input type="hidden" name="periode" value="<?= html_escape($periode['kode']) ?>">
    <div class="form-card">
      <h3 class="form-card-title"><?= $jenis === 'masuk' ? 'Catat uang masuk' : 'Catat uang keluar' ?></h3>
      <div class="mb-3">
        <label class="form-label" for="jumlah">Jumlah (Rp)</label>
        <input type="text" class="form-control form-control-lg input-rupiah" id="jumlah" name="jumlah" inputmode="numeric" required autofocus placeholder="0">
      </div>
      <div class="mb-3">
        <span class="form-label">Untuk apa?</span>
        <div class="kas-kategori">
          <?php foreach ($kategori[$jenis] as $i => $k): ?>
            <label><input type="radio" name="kategori" value="<?= html_escape($k) ?>" <?= $i === 0 ? 'checked' : '' ?>><span><?= html_escape($k) ?></span></label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="row">
        <div class="col-6 mb-3">
          <label class="form-label" for="tanggal">Tanggal</label>
          <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-6 mb-3">
          <label class="form-label" for="keterangan">Catatan</label>
          <input type="text" class="form-control" id="keterangan" name="keterangan" maxlength="200" placeholder="opsional">
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-besar w-100">Simpan</button>
    </div>
    <?= form_close() ?>

    <?php if ($ringkasan['per_kategori']['keluar']): ?>
      <div class="fitur-kartu mt-3">
        <h3>Pengeluaran terbesar</h3>
        <?php $maks = max($ringkasan['per_kategori']['keluar']); ?>
        <?php foreach ($ringkasan['per_kategori']['keluar'] as $k => $n): ?>
          <div class="batang-baris">
            <span><?= html_escape($k) ?></span>
            <span class="batang"><i style="width: <?= $maks ? round($n / $maks * 100) : 0 ?>%"></i></span>
            <strong><?= rupiah($n) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-7">
    <h2 class="panel-sub">Riwayat</h2>
    <?php if ( ! $arus): ?>
      <div class="fitur-kosong">Belum ada catatan di periode ini. Mulai dengan menekan <strong>+ Uang masuk</strong> atau <strong>&minus; Uang keluar</strong>.</div>
    <?php else: ?>
      <div class="adm-tabel">
        <?php foreach ($arus as $a): ?>
          <div class="adm-baris">
            <div>
              <strong><?= html_escape($a['kategori']) ?></strong>
              <em><?= tgl_id($a['tanggal']) ?><?= $a['keterangan'] ? ' &middot; ' . html_escape($a['keterangan']) : '' ?></em>
            </div>
            <div class="adm-aksi">
              <strong class="<?= $a['jenis'] === 'masuk' ? 'kas-angka-masuk' : 'kas-angka-keluar' ?>">
                <?= $a['jenis'] === 'masuk' ? '+' : '&minus;' ?><?= rupiah($a['jumlah']) ?>
              </strong>
              <?php if ($a['sumber'] === 'catatan'): ?>
                <?= form_open('seller/pembukuan/hapus/' . (int) $a['id'], array('class' => 'inline-form')) ?>
                  <input type="hidden" name="periode" value="<?= html_escape($periode['kode']) ?>">
                  <button type="submit" class="link-btn" onclick="return confirm('Hapus catatan ini?')" aria-label="Hapus">&times;</button>
                <?= form_close() ?>
              <?php else: ?>
                <span class="lencana" title="Tercatat otomatis dari pesanan">otomatis</span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<script src="<?= aset('assets/js/format-rupiah.js') ?>"></script>
