<?php
/* Formulir voucher - penjual & admin. $v NULL = voucher baru. */
$x = function ($k, $bawaan = '') use ($v) { return $v && isset($v[$k]) && $v[$k] !== NULL ? $v[$k] : $bawaan; };
?>
<div class="panel-judul">
  <h1><?= $v ? 'Ubah voucher' : 'Buat voucher' ?><?= $platform ? ' platform' : '' ?></h1>
  <a href="<?= site_url($basis) ?>" class="btn btn-black-hover-outline">Kembali</a>
</div>

<?= form_open($basis . '/simpan') ?>
<input type="hidden" name="id" value="<?= $v ? (int) $v['id'] : '' ?>">
<div class="form-card">
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label" for="kode">Kode voucher *</label>
      <input type="text" class="form-control" id="kode" name="kode" maxlength="30" required style="text-transform:uppercase"
             placeholder="contoh: LEBARAN10" value="<?= html_escape($x('kode')) ?>">
      <p class="hint mb-0">Yang diketik pembeli saat checkout. Huruf & angka saja.</p>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label" for="nama">Nama / keterangan *</label>
      <input type="text" class="form-control" id="nama" name="nama" maxlength="100" required
             placeholder="contoh: Promo Lebaran" value="<?= html_escape($x('nama')) ?>">
    </div>
  </div>

  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="form-label" for="jenis">Jenis potongan</label>
      <select class="form-select" id="jenis" name="jenis">
        <?php foreach (array('nominal' => 'Potongan rupiah', 'persen' => 'Potongan persen', 'ongkir' => 'Potongan ongkir') as $k => $l): ?>
          <option value="<?= $k ?>" <?= $x('jenis', 'nominal') === $k ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label" for="nilai">Besar potongan *</label>
      <input type="text" class="form-control" id="nilai" name="nilai" inputmode="numeric" required
             placeholder="Rp atau %" value="<?= html_escape($x('nilai')) ?>">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label" for="maks_potongan">Maks. potongan <span class="hint">(untuk persen)</span></label>
      <input type="text" class="form-control" id="maks_potongan" name="maks_potongan" inputmode="numeric"
             value="<?= html_escape($x('maks_potongan')) ?>">
    </div>
  </div>

  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="form-label" for="min_belanja">Minimal belanja (Rp)</label>
      <input type="text" class="form-control" id="min_belanja" name="min_belanja" inputmode="numeric" value="<?= html_escape($x('min_belanja', 0)) ?>">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label" for="kuota">Kuota pemakaian <span class="hint">(kosong = tak terbatas)</span></label>
      <input type="number" class="form-control" id="kuota" name="kuota" min="1" value="<?= html_escape($x('kuota')) ?>">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label" for="per_pembeli">Batas per pembeli</label>
      <input type="number" class="form-control" id="per_pembeli" name="per_pembeli" min="0" max="99" value="<?= html_escape($x('per_pembeli', 1)) ?>">
      <p class="hint mb-0">0 = boleh berkali-kali.</p>
    </div>
  </div>

  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="form-label" for="mulai">Berlaku mulai</label>
      <input type="date" class="form-control" id="mulai" name="mulai" value="<?= html_escape(substr($x('mulai'), 0, 10)) ?>">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label" for="selesai">Berlaku sampai</label>
      <input type="date" class="form-control" id="selesai" name="selesai" value="<?= html_escape(substr($x('selesai'), 0, 10)) ?>">
    </div>
    <div class="col-md-4 mb-3 d-flex align-items-end">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" <?= $x('is_active', 1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_active">Aktif</label>
      </div>
    </div>
  </div>
</div>
<button type="submit" class="btn btn-primary btn-besar">Simpan voucher</button>
<?= form_close() ?>
