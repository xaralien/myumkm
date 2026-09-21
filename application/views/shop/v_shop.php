<style>
  .hero-page {
    background-image: url('<?= base_url('assets/') ?>images/flower_bg2.png');
    background-color: rgba(0, 0, 0, 0.6);
    /* Tint color */
    background-blend-mode: darken;
    /* Blends the color and image */
    background-size: cover;
    background-position: center;
  }

  /* ==========================================================================
   KARTU PRODUK SIMETRIS
   Masalah: tiap foto bunga punya rasio berbeda, jadi tinggi gambar berbeda
   dan judul/harga jadi tidak sejajar antar kartu.

   Solusi: (1) kunci area gambar jadi kotak 1:1, foto di-"contain" di dalamnya
           (2) kartu jadi flex-column, harga didorong ke dasar pakai margin-top:auto
   Tempel di paling bawah css/style.css
   ========================================================================== */

  /* Kolom ikut meregang setinggi kartu tertinggi dalam satu baris */
  .product-section .row>[class*="col-"] {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
  }

  .product-item {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
    -ms-flex-direction: column;
    flex-direction: column;
    width: 100%;
    height: 100%;
  }

  /* Bagian yang bisa diklik menuju detail produk */
  .product-item .product-item-link {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
    -ms-flex-direction: column;
    flex-direction: column;
    -webkit-box-flex: 1;
    -ms-flex: 1 1 auto;
    flex: 1 1 auto;
    text-decoration: none;
  }

  /* (1) Area gambar dikunci jadi kotak. object-fit: contain = foto tidak
       terpotong, hanya diberi ruang kosong di sisi yang kurang. */
  .product-item .product-thumbnail {
    width: 100%;
    aspect-ratio: 1 / 1;
    -o-object-fit: contain;
    object-fit: contain;
    margin-bottom: 20px;
  }

  .product-item .product-title {
    margin-bottom: 6px;
  }

  /* (2) Harga menempel ke dasar kartu -> semua harga sejajar,
       walaupun ada judul yang panjangnya 2 baris. */
  .product-item .product-price {
    margin-top: auto;
    display: block;
  }

  /* Tombol tambah: sekarang <button>, bukan <span> di dalam <a> ------------- */
  .product-item .icon-cross {
    border: 0;
    padding: 0;
    cursor: pointer;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    -webkit-box-pack: center;
    -ms-flex-pack: center;
    justify-content: center;
  }

  .product-item .icon-cross:focus-visible {
    outline: 3px solid #f9bf29;
    outline-offset: 3px;
  }

  /* Di layar sentuh tidak ada hover, jadi tombolnya selalu terlihat */
  @media (max-width: 991.98px) {
    .product-item .icon-cross {
      opacity: 1;
      visibility: visible;
      bottom: 0;
    }
  }

  /* Kartu lebih rapat di HP kecil supaya judul tidak terpotong -------------- */
  @media (max-width: 400px) {
    .product-item .product-title {
      font-size: 14px;
      line-height: 22px;
    }

    .product-item .product-price {
      font-size: 16px !important;
    }

    .product-item .product-thumbnail {
      margin-bottom: 14px;
    }
  }

  /* Cadangan untuk browser lama yang belum kenal aspect-ratio -------------- */
  @supports not (aspect-ratio: 1 / 1) {
    .product-item .product-thumbnail {
      height: 240px;
    }
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
      <!-- <div class="col-lg-7">
        <div class="hero-img-wrap">
          <img style="max-height: 200px; margin-left:30vw" src="<?= base_url('assets/') ?>images/flower1.png" alt="Flower Bouquet" class="img-fluid">
        </div>
      </div> -->
    </div>
  </div>
</div>
<!-- End Hero Section -->


<div class="untree_co-section product-section before-footer-section">
  <div class="container">

    <!-- =============== PENCARIAN & FILTER ===============
         Satu <form> GET saja. Semua kontrol di dalamnya otomatis ikut
         terkirim, jadi filter bisa dikombinasikan bebas dan URL-nya
         bisa di-bookmark maupun dibagikan. -->
    <form method="get" action="<?= site_url('shop') ?>" class="shop-filter" id="shopFilter">

      <div class="filter-bar">
        <div class="filter-search">
          <input type="search" name="q" class="form-control"
            placeholder="Cari bunga, misalnya: mawar"
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
              <label class="chip">
                <input type="radio" name="category" value="<?= html_escape($c['category']) ?>"
                  <?= $f['category'] === $c['category'] ? 'checked' : '' ?> data-autosubmit>
                <span><?= html_escape($c['category']) ?> <em><?= (int) $c['jml'] ?></em></span>
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

              <a class="product-item-link" href="<?= site_url('produk/' . $p['slug']) ?>">
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
    checkout: '<?= site_url('checkout') ?>'
  };
  window.CSRF = {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= base_url('assets/js/shop.js') ?>"></script>