<!-- application/views/v_shop.php -->
<style>
  .hero-page {
    background-image: url('<?= base_url('assets/') ?>images/flower_bg2.png');
    background-color: rgba(0, 0, 0, 0.6);
    background-blend-mode: darken;
    background-size: cover;
    background-position: center;
  }
</style>

<!-- Start Hero Section -->
<div class="hero hero-page">
  <div class="container">
    <div class="row justify-content-between">
      <div class="col-lg-12">
        <div class="intro-excerpt text-center">
          <h1>Catalog</h1>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- End Hero Section -->

<div class="untree_co-section product-section before-footer-section">
  <div class="container">


    <!-- =============== KIRIM KE =============== -->
    <?php $this->load->view('parts/kirim_ke'); ?>

    <?php if ($mode_dekat && $total > 0): ?>
      <?php
        /* Jelaskan kenapa ada toko dari luar kecamatan tujuan. Tanpa kalimat
           ini, pengunjung mengira saringannya rusak - padahal memang
           disengaja: produk jauh tetap tampil, hanya di belakang. */
        $dekat_n = (int) $sebaran[1];
        $kota_n  = (int) $sebaran[2];
        $prov_n  = (int) $sebaran[3];
      ?>
      <?php if ($dekat_n > 0 && ($kota_n + $prov_n) > 0): ?>
        <p class="locbar-info">
          <strong><?= $dekat_n ?></strong> produk dari toko di kecamatan tujuan, ditampilkan lebih dulu.
          <?= $kota_n + $prov_n ?> lainnya dari sekitar dan luar kota.
        </p>
      <?php elseif ($dekat_n === 0): ?>
        <p class="locbar-info">
          Belum ada toko di kecamatan tujuan. Ini toko terdekat dari sana.
        </p>
      <?php endif; ?>
    <?php endif; ?>

    <!-- =============== PENCARIAN & FILTER ===============
         Satu <form> GET saja. Semua kontrol di dalamnya otomatis ikut
         terkirim, jadi filter bisa dikombinasikan bebas dan URL-nya
         bisa di-bookmark maupun dibagikan. -->
    <form method="get" action="<?= site_url('shop') ?>" class="shop-filter" id="shopFilter">

      <div class="filter-bar">
        <div class="filter-search">
          <input type="search" name="q" class="form-control"
            placeholder="Cari produk, misalnya: keripik tempe"
            value="<?= html_escape($f['q']) ?>" aria-label="Cari produk">
          <button type="submit" class="filter-search-btn" aria-label="Cari">Cari</button>
        </div>

        <div class="filter-right">
          <label class="filter-sort">
            <span>Urutkan</span>
            <select name="sort" class="form-control" data-autosubmit>
              <?php foreach ($sorts as $k => $label): ?>
                <option value="<?= $k ?>" <?= $f['sort'] === $k ? 'selected' : '' ?>>
                  <?= html_escape($label) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <button type="button" class="btn-filter-toggle" id="btnFilter" aria-expanded="false">
            Filter<?= $ada_filter ? ' <span class="dot"></span>' : '' ?>
          </button>
        </div>
      </div>

      <div class="filter-panel" id="filterPanel">
        <div class="filter-group">
          <p class="filter-label">Kategori</p>
          <div class="filter-chips">
            <label class="chip">
              <input type="radio" name="category" value="" <?= $f['category'] === '' ? 'checked' : '' ?> data-autosubmit>
              <span>Semua</span>
            </label>
            <?php foreach ($categories as $c): ?>
              <label class="chip<?= (int) $c['jml'] === 0 ? ' is-kosong' : '' ?>">
                <input type="radio" name="category" value="<?= html_escape($c['slug']) ?>"
                  <?= $f['category'] === $c['slug'] ? 'checked' : '' ?> data-autosubmit>
                <span><?= html_escape($c['name']) ?> <em><?= (int) $c['jml'] ?></em></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="filter-group">
          <p class="filter-label">Rentang harga</p>
          <div class="filter-price">
            <input type="number" name="min" class="form-control" min="0" step="1000"
              placeholder="<?= number_format($range['min'], 0, ',', '.') ?>"
              value="<?= $f['min'] === NULL ? '' : (int) $f['min'] ?>" aria-label="Harga minimum">
            <span>&mdash;</span>
            <input type="number" name="max" class="form-control" min="0" step="1000"
              placeholder="<?= number_format($range['max'], 0, ',', '.') ?>"
              value="<?= $f['max'] === NULL ? '' : (int) $f['max'] ?>" aria-label="Harga maksimum">
          </div>
        </div>

        <div class="filter-actions">
          <button type="submit" class="btn btn-primary">Terapkan</button>
          <?php if ($ada_filter): ?>
            <a href="<?= site_url('shop') ?>" class="btn btn-black-hover-outline">Hapus filter</a>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <!-- =============== JUMLAH HASIL =============== -->
    <p class="result-count">
      <?php if ($total === 0): ?>
        Tidak ada produk yang cocok
      <?php else: ?>
        Menampilkan <strong><?= $from ?>&ndash;<?= $to ?></strong> dari
        <strong><?= $total ?></strong> produk
        <?php if ($f['q'] !== ''): ?>
          untuk &ldquo;<?= html_escape($f['q']) ?>&rdquo;
        <?php endif; ?>
      <?php endif; ?>
    </p>

    <!-- =============== DAFTAR PRODUK =============== -->
    <?php if ($total === 0): ?>

      <div class="empty-state">
        <p class="empty-title">Belum ada yang cocok</p>
        <p class="hint mb-4">
          Coba kata kunci lain, atau lebarkan rentang harganya.
        </p>
        <a href="<?= site_url('shop') ?>" class="btn btn-primary">Lihat semua produk</a>
      </div>

    <?php else: ?>

      <div class="row">
        <?php foreach ($products as $p): ?>
          <div class="col-6 col-md-4 col-lg-3 mb-4 mb-lg-5">
            <div class="product-item">

              <!-- Dua segmen: slug produk hanya unik di dalam satu toko, jadi
                   satu segmen saja akan menampilkan produk toko yang salah. -->
              <a class="product-item-link"
                href="<?= site_url('produk/' . $p['store_slug'] . '/' . $p['slug']) ?>">
                <img src="<?= base_url('upload/produk/' . $p['image']) ?>"
                  alt="<?= html_escape($p['name']) ?>"
                  class="img-fluid product-thumbnail" loading="lazy">
                <h3 class="product-title"><?= html_escape($p['name']) ?></h3>
                <strong class="product-price">
                  <?php if ((int) $p['variant_count'] > 1): ?>
                    <span class="price-prefix">Mulai</span>
                  <?php endif; ?>
                  <?= rupiah($p['price']) ?>
                </strong>
              </a>

              <!-- Dikelompokkan supaya bisa didorong ke dasar kartu sekaligus.
                   Kalau dibiarkan terpisah, nama toko yang panjangnya
                   berbeda-beda membuat tiap kartu berakhir di tinggi
                   yang tidak sama. -->
              <div class="product-meta">
                <?php if (!empty($p['category_name'])): ?>
                  <p class="product-cat"><?= html_escape($p['category_name']) ?></p>
                <?php endif; ?>

                <?php if (isset($p['jarak_km'])): ?>
                  <!-- Dikeluarkan dari <p class="product-store">: paragraf di
                       dalam paragraf tidak sah, dan browser menutup yang luar
                       lebih awal sehingga nama tokonya terlempar keluar
                       kelompok dan jarak antarkartu jadi tidak rata. -->
                  <p class="product-jarak"><?= number_format($p['jarak_km'], 1, ',', '.') ?> km dari kamu</p>
                <?php endif; ?>

                <p class="product-store">
                  <?= html_escape($p['store_name']) ?>
                  <?php if (!empty($p['store_district'])): ?>
                    <em><?= html_escape($p['store_district']) ?><?php
                      if (isset($p['proximity']) && (int) $p['proximity'] > 1) {
                          echo ', ' . html_escape($p['store_regency']);
                      }
                    ?></em>
                  <?php endif; ?>
                </p>

                <?php $teks_terjual = isset($p['terjual']) ? terjual_teks($p['terjual']) : ''; ?>
                <?php if ((int) $p['rating_count'] > 0 || $teks_terjual): ?>
                  <!-- Rating PRODUK, bukan rating toko: kartu ini tentang satu
                       barang, dan toko bagus pun bisa punya produk yang
                       mengecewakan. -->
                  <p class="product-nilai">
                    <?php if ((int) $p['rating_count'] > 0): ?>
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
                      <b><?= number_format($p['rating_avg'], 1, ',', '.') ?></b>
                      <span>(<?= (int) $p['rating_count'] ?>)</span>
                    <?php endif; ?>

                    <?php if ((int) $p['rating_count'] > 0 && $teks_terjual): ?>
                      <i aria-hidden="true">&middot;</i>
                    <?php endif; ?>

                    <?php if ($teks_terjual): ?><span><?= $teks_terjual ?></span><?php endif; ?>
                  </p>
                <?php endif; ?>
              </div>

              <button type="button" class="icon-cross btn-add"
                data-id="<?= (int) $p['id'] ?>"
                aria-label="Tambah <?= html_escape($p['name']) ?> ke keranjang">
                <img src="<?= base_url('assets/') ?>images/cross.svg" alt="" class="img-fluid">
              </button>

            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($pagess > 1): ?>
        <nav class="pagination-wrap" aria-label="Halaman produk">
          <?= $pagination ?>
          <p class="hint text-center mt-2">Halaman <?= $page ?> dari <?= $pagess ?></p>
        </nav>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

<script>
  window.SHOP_URLS = {
    options: '<?= site_url('cart/options') ?>',
    add: '<?= site_url('cart/add') ?>',
    clear: '<?= site_url('cart/clear') ?>',
    checkout: '<?= site_url('checkout') ?>'
  };
  window.CSRF = {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
  window.FILTER_OPEN = <?= $ada_filter ? 'true' : 'false' ?>;
</script>
<script src="<?= aset('assets/js/shop.js') ?>"></script>
<script src="<?= aset('assets/js/filter.js') ?>"></script>

<script>
  window.LOKASI_URLS = { titik: '<?= site_url('location/titik') ?>' };
</script>
<script src="<?= aset('assets/js/nearby.js') ?>"></script>
<!-- Modal pemilihan wilayah tidak dipakai lagi - lokasi ditentukan GPS.
     Berkasnya dibiarkan ada kalau sewaktu-waktu perlu dikembalikan.
     Elemen pesan sudah ada di bilah lokasi di atas; id harus unik, jadi
     tidak digandakan di sini. -->
<script>
  window.GPS = { simpan: '<?= site_url('location/titik') ?>' };
  window.CSRF = window.CSRF || {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>