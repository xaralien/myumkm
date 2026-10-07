<!-- application/views/v_footer.php -->
<?php
$brand  = $this->config->item('nama_brand') ?: 'Sapa UMKM';
$wa     = $this->config->item('wa_admin');
$alamat = $this->config->item('alamat');
$email  = $this->config->item('email');
?>

<footer class="nt-footer">
  <div class="nt-wrap">

    <div class="nt-footer-kolom">
      <div>
        <h2><?= html_escape($brand) ?></h2>
        <p>Marketplace untuk pelaku UMKM lokal. Belanja dari tetangga, dukung usaha di kotamu.</p>
      </div>

      <nav aria-label="Belanja">
        <h3>Belanja</h3>
        <ul>
          <li><a href="<?= site_url('shop') ?>">Semua produk</a></li>
          <li><a href="<?= site_url('shop') ?>?dekat=1&amp;radius=10">Toko terdekat</a></li>
          <li><a href="<?= site_url('track') ?>">Lacak pesanan</a></li>
        </ul>
      </nav>

      <nav aria-label="Akun">
        <h3>Akun</h3>
        <ul>
          <li><a href="<?= site_url('auth/login') ?>">Masuk</a></li>
          <li><a href="<?= site_url('auth/register') ?>">Daftar</a></li>
          <li><a href="<?= site_url('akun/buka_toko') ?>">Buka toko</a></li>
        </ul>
      </nav>

      <div>
        <h3>Kontak</h3>
        <ul>
          <?php if ($alamat): ?><li><?= html_escape($alamat) ?></li><?php endif; ?>
          <?php if ($wa): ?>
            <li><a href="https://wa.me/<?= html_escape($wa) ?>" target="_blank" rel="noopener">WhatsApp</a></li>
          <?php endif; ?>
          <?php if ($email): ?><li><?= html_escape($email) ?></li><?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="nt-footer-bawah">
      <!-- <span>&copy; <?= date('Y') ?> <?= html_escape($brand) ?></span> -->
      <span>&copy; <?= date('Y') ?> <?= html_escape($brand) ?></span>
    </div>

  </div>
</footer>

<!-- Tombol lokasi (data-gps) ada di bilah atas SEMUA halaman, jadi skripnya
     dimuat di kerangka - bukan per halaman. Sebelumnya hanya beranda dan
     katalog yang memuatnya, sehingga tombol yang sama diam saja di halaman
     produk, keranjang, dan lainnya. -->
<script>
  window.GPS = window.GPS || { simpan: '<?= site_url('location/titik') ?>' };
  window.CSRF = window.CSRF || {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= aset('assets/js/gps.js') ?>"></script>
