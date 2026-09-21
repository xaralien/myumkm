<!-- application/views/v_footer.php -->
<?php
  $brand  = $this->config->item('nama_brand') ?: 'Nama Brand';
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

      <nav aria-label="Untuk penjual">
        <h3>Untuk penjual</h3>
        <ul>
          <?php if ($wa): ?>
            <li><a href="https://wa.me/<?= html_escape($wa) ?>?text=<?= rawurlencode('Halo, saya ingin mendaftarkan UMKM saya di ' . $brand) ?>"
                   target="_blank" rel="noopener">Daftarkan UMKM</a></li>
          <?php endif; ?>
          <!-- Pindah dari ikon orang di navbar lama. Pembeli tidak butuh
               login sama sekali, jadi ikon itu justru membingungkan mereka. -->
          <li><a href="<?= site_url('auth/login') ?>">Masuk sebagai penjual</a></li>
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
      <span>&copy; <?= date('Y') ?> <?= html_escape($brand) ?></span>
    </div>

  </div>
</footer>
