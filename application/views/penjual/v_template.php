<!-- application/views/penjual/v_template.php -->
<div class="panel-judul">
  <div>
    <h1>Balasan cepat</h1>
    <p class="hint">Jawaban yang sering kamu ketik. Muncul sebagai tombol di atas kolom chat &mdash; sekali ketuk, langsung terisi.</p>
  </div>
</div>

<div class="row">
  <div class="col-lg-7 mb-4">
    <div class="adm-tabel">
      <?php foreach ($daftar as $t): ?>
        <div class="adm-baris">
          <div><strong><?= html_escape($t['judul']) ?></strong><em><?= html_escape($t['isi']) ?></em></div>
          <div class="adm-aksi">
            <a href="<?= site_url('seller/template?ubah=' . (int) $t['id']) ?>" class="btn btn-sm btn-black-hover-outline">Ubah</a>
            <?= form_open('seller/template/hapus/' . (int) $t['id'], array('class' => 'inline-form')) ?>
              <button type="submit" class="btn btn-sm btn-black-hover-outline" onclick="return confirm('Hapus template ini?')">Hapus</button>
            <?= form_close() ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-5">
    <?= form_open('seller/template/simpan') ?>
    <div class="form-card">
      <h3 class="form-card-title"><?= $ubah ? 'Ubah template' : 'Tambah template' ?></h3>
      <input type="hidden" name="id" value="<?= $ubah ? (int) $ubah['id'] : '' ?>">
      <div class="mb-3">
        <label class="form-label" for="judul">Judul tombol</label>
        <input type="text" class="form-control" id="judul" name="judul" maxlength="40" required placeholder="contoh: Ready"
               value="<?= html_escape($ubah ? $ubah['judul'] : '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label" for="isi">Isi pesan</label>
        <textarea class="form-control" id="isi" name="isi" rows="4" maxlength="1000" required><?= html_escape($ubah ? $ubah['isi'] : '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Simpan</button>
      <?php if ($ubah): ?><a href="<?= site_url('seller/template') ?>" class="btn btn-black-hover-outline">Batal</a><?php endif; ?>
    </div>
    <?= form_close() ?>
  </div>
</div>
