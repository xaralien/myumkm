<!-- application/views/mobile/v_home.php -->
<?php
  $ikon = array(
    'makanan'    => '<path d="M4 8h13v5a6 6 0 0 1-6 6h-1a6 6 0 0 1-6-6V8z"></path><path d="M17 10h2a2 2 0 0 1 0 4h-2"></path><path d="M8 3v2M12 3v2"></path>',
    'kerajinan'  => '<path d="M3 10h18l-2 10H5L3 10z"></path><path d="M8 10l4-6 4 6"></path>',
    'fashion'    => '<path d="M8 3l-5 3 2 4 3-1v12h8V9l3 1 2-4-5-3-2 2h-4L8 3z"></path>',
    'kecantikan' => '<path d="M9 3h6v4H9z"></path><path d="M8 7h8l1 4v9a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-9l1-4z"></path>',
    'hadiah'     => '<path d="M4 10h16v10H4z"></path><path d="M3 7h18v3H3z"></path><path d="M12 7v13"></path>',
    'rumah'      => '<path d="M4 11l8-7 8 7v9H4z"></path><path d="M10 20v-6h4v6"></path>',
    'umum'       => '<path d="M4 7h16l-1.5 13h-13L4 7z"></path><path d="M9 7V5a3 3 0 0 1 6 0v2"></path>',
  );
  $svg = function ($k) use ($ikon) {
    return '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
      . (isset($ikon[$k]) ? $ikon[$k] : $ikon['umum']) . '</svg>';
  };
  $url_produk = function ($p) { return site_url('produk/' . $p['store_slug'] . '/' . $p['slug']); };
  $inisial = function ($n) { return strtoupper(mb_substr(trim($n), 0, 1)); };
?>

<section class="mb-hero">
  <h1>Produk UMKM terbaik, dari kota yang sama.</h1>
  <p>Makanan rumahan, kerajinan, batik, sampai perawatan alami &mdash; langsung dari pelaku usaha di sekitarmu.</p>
  <a href="<?= site_url('shop') ?>" class="mb-hero-aksi">
    Mulai belanja
    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
  </a>
</section>

<?php if ($categories): ?>
  <section class="mb-bagian">
    <div class="mb-judul">
      <h2>Kategori</h2>
      <a href="<?= site_url('shop') ?>">Semua</a>
    </div>
    <div class="mb-geser">
      <?php foreach ($categories as $c): ?>
        <a href="<?= site_url('shop') . '?category=' . rawurlencode($c['slug']) ?>" class="mb-kat">
          <span><?= $svg($c['icon']) ?></span>
          <b><?= html_escape($c['name']) ?></b>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<section class="mb-bagian">
  <div class="mb-judul">
    <h2><?= $lokasi ? 'Terbaru di sekitarmu' : 'Produk terbaru' ?></h2>
    <a href="<?= site_url('shop') ?>">Lihat semua</a>
  </div>

  <?php if (! $products): ?>
    <p class="mb-kosong">Belum ada produk di wilayah ini.</p>
  <?php else: ?>
    <div class="mb-produk">
      <?php foreach (array_slice($products, 0, 6) as $p): ?>
        <a href="<?= $url_produk($p) ?>" class="mb-kartu">
          <div class="mb-kartu-foto">
            <img src="<?= base_url('upload/produk/' . $p['image']) ?>"
                 alt="<?= html_escape($p['name']) ?>" loading="lazy">
          </div>
          <div class="mb-kartu-isi">
            <span class="mb-kartu-nama"><?= html_escape($p['name']) ?></span>
            <span class="mb-kartu-toko"><?= html_escape($p['store_name']) ?></span>
            <span class="mb-kartu-harga">
              <?php if ((int) $p['variant_count'] > 1): ?><small>mulai </small><?php endif; ?>
              <?= rupiah($p['price']) ?>
            </span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php if ($toko_dekat): ?>
  <section class="mb-bagian">
    <div class="mb-judul">
      <h2>Toko di sekitarmu</h2>
      <a href="<?= site_url('shop') ?>?dekat=1&amp;radius=10">Peta</a>
    </div>
    <div class="mb-geser">
      <?php foreach ($toko_dekat as $t): ?>
        <a href="<?= site_url('toko/' . $t['slug']) ?>" class="mb-toko">
          <span class="mb-toko-avatar">
            <?php if (! empty($t['avatar'])): ?>
              <img src="<?= base_url('upload/avatar/' . $t['avatar']) ?>" alt="">
            <?php else: ?><?= html_escape($inisial($t['name'])) ?><?php endif; ?>
          </span>
          <span class="mb-toko-teks">
            <b><?= html_escape($t['name']) ?></b>
            <em>
              <?= (int) $t['jml_produk'] ?> produk<?php
                if (! empty($t['label_jarak'])) { echo ' &middot; ' . html_escape($t['label_jarak']); }
              ?>
            </em>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<section class="mb-bagian">
  <div class="mb-judul"><h2>Cara belanja</h2></div>
  <ol class="mb-langkah">
    <li><span><b>Pilih lokasimu</b>Toko terdekat tampil lebih dulu, ongkir lebih ringan.</span></li>
    <li><span><b>Pesan tanpa akun</b>Bayar lewat transfer, e-wallet, atau QRIS.</span></li>
    <li><span><b>Pantau &amp; chat penjual</b>Lacak pesanan dan tanya langsung ke penjualnya.</span></li>
  </ol>
</section>

<section class="mb-cta">
  <h2>Punya usaha?</h2>
  <p>Buka tokomu dan jangkau pembeli di sekitar.</p>
  <a href="<?= site_url('akun/buka_toko') ?>">Buka toko</a>
</section>

<p class="gps-info" id="gpsInfo" aria-live="polite"></p>

<script>
  window.GPS = { simpan: '<?= site_url('location/titik') ?>' };
  window.CSRF = window.CSRF || {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= aset('assets/js/gps.js') ?>"></script>
