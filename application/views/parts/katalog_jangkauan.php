<?php
/* Keterangan jangkauan di katalog (desktop & HP). Data dari Shop::index():
   $f['jangkauan'] ('dalam' | 'luar'), $jml_luar, $lokasi.
   Toko yang tidak mengirim ke "Kirim ke" pembeli tidak dicampur ke daftar
   utama - hanya disebut jumlahnya, dengan tautan ke daftar terpisah. */
if (empty($lokasi)) {
    return;
}
$label = $lokasi['district_name'] . ', ' . $lokasi['regency_name'];
$qs = $_GET;
unset($qs['page']);
?>
<?php if ($f['jangkauan'] === 'luar'): ?>
  <div class="katalog-luar-info">
    Produk di bawah ini dari toko yang <strong>tidak mengirim ke <?= html_escape($label) ?></strong>.
    Bisa dilihat-lihat atau ditanyakan lewat chat, tapi belum bisa dipesan ke lokasimu.
    <?php unset($qs['luar']); ?>
    <a href="<?= html_escape(site_url('shop') . ($qs ? '?' . http_build_query($qs) : '')) ?>">Kembali ke produk yang bisa dikirim</a>
  </div>
<?php elseif ( ! empty($jml_luar)): ?>
  <div class="katalog-luar-info">
    Ada <?= (int) $jml_luar ?> produk lain dari toko yang tidak mengirim ke <?= html_escape($label) ?>.
    <?php $qs['luar'] = 1; ?>
    <a href="<?= html_escape(site_url('shop') . '?' . http_build_query($qs)) ?>">Lihat</a>
  </div>
<?php endif; ?>
