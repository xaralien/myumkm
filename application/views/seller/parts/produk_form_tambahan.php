<?php
/* Kartu tambahan di formulir produk: label UMKM (dan pengaturan lain yang
   ditambahkan kemudian). $product NULL saat menambah produk baru. */
$v = function ($k, $bawaan = '') use ($product) {
    return set_value($k, $product && isset($product[$k]) ? $product[$k] : $bawaan);
};
?>
<div class="form-card">
  <h3 class="form-card-title">Label produk</h3>
  <p class="hint mb-3">Tampil sebagai lencana di halaman produk. Pembeli lebih yakin membeli produk yang jelas asal-usulnya.</p>

  <div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" name="buatan_tangan" value="1" id="lbl_tangan"
           <?= $v('buatan_tangan') ? 'checked' : '' ?>>
    <label class="form-check-label" for="lbl_tangan">Buatan tangan (handmade)</label>
  </div>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="bahan_lokal" value="1" id="lbl_lokal"
           <?= $v('bahan_lokal') ? 'checked' : '' ?>>
    <label class="form-check-label" for="lbl_lokal">Memakai bahan lokal</label>
  </div>

  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label" for="halal_no">Nomor sertifikat Halal <span class="hint">(kalau ada)</span></label>
      <input type="text" class="form-control" id="halal_no" name="halal_no" maxlength="60" value="<?= html_escape($v('halal_no')) ?>">
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label" for="pirt_no">Nomor P-IRT <span class="hint">(kalau ada)</span></label>
      <input type="text" class="form-control" id="pirt_no" name="pirt_no" maxlength="60" value="<?= html_escape($v('pirt_no')) ?>">
    </div>
  </div>
  <p class="hint mb-0">
    Unggah dokumennya di <a href="<?= site_url('seller/legalitas') ?>">Legalitas</a> supaya lencananya bertanda ✓ terverifikasi.
  </p>
</div>
