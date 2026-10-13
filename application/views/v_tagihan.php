<!-- application/views/v_tagihan.php - status tagihan yang sudah tidak menunggu dibayar -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Tagihan</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-lg-6">
    <?php $this->load->view('parts/auth_pesan'); ?>
    <div class="form-card text-center">
      <p class="hint mb-1">Nomor tagihan</p>
      <p class="order-number"><?= html_escape($tagihan['kode']) ?></p>
      <p class="mb-3">
        <span class="badge-status <?= $tagihan['status'] === 'paid' ? 'is-ok' : ($tagihan['status'] === 'unpaid' ? 'is-wait' : 'is-bad') ?>">
          <?= label_bayar($tagihan['status']) ?>
        </span>
      </p>

      <?php $this->load->view('parts/tagihan_rincian'); ?>

      <div class="summary-row summary-total mb-3">
        <span>Total tagihan</span><strong><?= rupiah($tagihan['jumlah']) ?></strong>
      </div>

      <?php if ($tagihan['status'] === 'paid'): ?>
        <p class="mb-0">Pembayaran diterima. Tiap toko memproses pesanannya masing-masing &mdash; buka nomor pesanan di atas untuk memantau dan mengobrol dengan penjualnya.</p>
      <?php elseif ($tagihan['status'] === 'expired'): ?>
        <p class="mb-0">Batas waktu pembayaran sudah lewat dan pesanannya dibatalkan. Silakan pesan ulang dari katalog.</p>
      <?php else: ?>
        <p class="mb-3">Halaman pembayaran belum bisa dibuka.</p>
        <a href="<?= html_escape($done_url) ?>" class="btn btn-primary">Coba lagi</a>
      <?php endif; ?>
    </div>
  </div></div></div>
</div>
