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

<div class="form-card">
  <h3 class="form-card-title">Pre-order</h3>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_preorder" value="1" id="is_preorder"
           <?= $v('is_preorder') ? 'checked' : '' ?>>
    <label class="form-check-label" for="is_preorder">
      Produk ini dibuat setelah dipesan (kue pesanan, katering, kerajinan custom)
    </label>
  </div>
  <div class="row">
    <div class="col-md-4 mb-3">
      <label class="form-label" for="po_hari">Lama pembuatan (hari)</label>
      <input type="number" class="form-control" id="po_hari" name="po_hari" min="1" max="60" value="<?= (int) $v('po_hari', 3) ?>">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label" for="po_kuota_harian">Kuota per hari <span class="hint">(kosong = bebas)</span></label>
      <input type="number" class="form-control" id="po_kuota_harian" name="po_kuota_harian" min="1" max="9999"
             value="<?= html_escape($v('po_kuota_harian')) ?>">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label" for="po_dp_persen">Uang muka (%)</label>
      <select class="form-select" id="po_dp_persen" name="po_dp_persen">
        <?php foreach (array(0 => 'Tanpa DP (bayar penuh)', 30 => '30%', 50 => '50%', 70 => '70%') as $n => $l): ?>
          <option value="<?= $n ?>" <?= (int) $v('po_dp_persen', 0) === $n ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <p class="hint mb-0">Pembeli memilih tanggal kirim saat checkout. Pesanan ber-DP baru bisa dikirim setelah dilunasi.</p>
</div>

<div class="form-card">
  <h3 class="form-card-title">Harga grosir <span class="hint">(opsional)</span></h3>
  <p class="hint mb-3">Harga per pcs turun otomatis kalau pembeli mengambil banyak. Contoh: beli 10 atau lebih Rp 12.000/pcs.</p>
  <div class="baris-dinamis" id="grosirList">
    <?php
      $baris_grosir = ! empty($grosir) ? $grosir : array(array('min_qty' => '', 'harga' => ''));
      foreach ($baris_grosir as $g):
    ?>
      <div class="varian-baris">
        <input type="number" name="grosir_min[]" class="form-control" min="2" max="9999" placeholder="Mulai jumlah"
               value="<?= html_escape($g['min_qty']) ?>">
        <input type="text" name="grosir_harga[]" class="form-control input-rupiah" inputmode="numeric" placeholder="Harga per pcs"
               value="<?= $g['harga'] !== '' ? number_format((int) $g['harga'], 0, ',', '.') : '' ?>">
        <button type="button" class="link-btn" data-hapus-baris>Hapus</button>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-black-hover-outline mt-2" data-tambah-baris="grosirList">Tambah tingkat harga</button>
</div>
