<!-- application/views/v_toko.php - halaman publik satu toko -->
<?php
  $inisial = strtoupper(mb_substr(trim($toko['name']), 0, 1));

  $url = function (array $ganti = array()) use ($toko, $tab) {
    $q = array_filter(array(
      'tab'      => $tab === 'ulasan' ? 'ulasan' : NULL,
      'kategori' => isset($kategori) && $kategori !== '' ? $kategori : NULL,
      'urut'     => isset($urut) && $urut !== 'baru' ? $urut : NULL,
    ));
    foreach ($ganti as $k => $v) {
      if ($v === NULL || $v === '') { unset($q[$k]); } else { $q[$k] = $v; }
    }
    return site_url('toko/' . $toko['slug']) . ($q ? '?' . http_build_query($q) : '');
  };
?>

<!-- ============================ KEPALA TOKO ============================ -->
<section class="tk-kepala">
  <div class="container">
    <div class="tk-kepala-in">

      <span class="tk-avatar">
        <?php if ($toko['avatar']): ?>
          <img src="<?= base_url('upload/avatar/' . $toko['avatar']) ?>" alt="">
        <?php else: ?><?= html_escape($inisial) ?><?php endif; ?>
      </span>

      <div class="tk-info">
        <h1><?= html_escape($toko['name']) ?></h1>
        <p class="tk-lokasi">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
          <?= html_escape($toko['district_name']) ?>, <?= html_escape($toko['regency_name']) ?>
        </p>

        <?php if ($toko['description']): ?>
          <p class="tk-deskripsi"><?= html_escape(mb_substr($toko['description'], 0, 160)) ?></p>
        <?php endif; ?>

        <div class="tk-aksi">
          <!-- Chat toko tanpa membawa produk: pengunjung di halaman toko
               belum tentu menanyakan satu barang tertentu. Kartu produk
               dikirim lewat tombol Tanya di halaman produk. -->
          <?= form_open('chat/toko/' . $toko['slug'], array('class' => 'tk-form')) ?>
            <button type="submit" class="tk-btn is-utama">
              <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
              Chat toko
            </button>
          <?= form_close() ?>

          <a href="<?= site_url('shop') ?>?store=<?= (int) $toko['id'] ?>&amp;dekat=1" class="tk-btn">
            Lihat di katalog
          </a>
        </div>
      </div>

      <!-- Angka ditaruh di satu blok supaya terbaca sebagai satu kesatuan:
           pembeli memakainya untuk menilai apakah tokonya bisa dipercaya. -->
      <div class="tk-angka">
        <div class="tk-angka-item is-rating">
          <strong>
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
            <?= $statistik['ulasan'] ? number_format($statistik['rating'], 1, ',', '.') : '-' ?>
          </strong>
          <span>
            <?= $statistik['ulasan'] ? (int) $statistik['ulasan'] . ' ulasan' : 'Belum ada ulasan' ?>
          </span>
        </div>

        <div class="tk-angka-item">
          <strong><?= (int) $statistik['produk'] ?></strong>
          <span>Produk</span>
        </div>

        <div class="tk-angka-item">
          <strong><?= (int) $statistik['terjual'] ?></strong>
          <span>Terjual</span>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- =============================== TAB ================================ -->
<nav class="tk-tab" aria-label="Isi toko">
  <div class="container">
    <a href="<?= html_escape($url(array('tab' => NULL))) ?>" class="<?= $tab === 'produk' ? 'is-aktif' : '' ?>">
      Produk <em><?= (int) $statistik['produk'] ?></em>
    </a>
    <a href="<?= html_escape($url(array('tab' => 'ulasan', 'kategori' => NULL, 'urut' => NULL))) ?>"
       class="<?= $tab === 'ulasan' ? 'is-aktif' : '' ?>">
      Ulasan <em><?= (int) $statistik['ulasan'] ?></em>
    </a>
  </div>
</nav>

<div class="untree_co-section product-section before-footer-section tk-isi">
  <div class="container">

    <?php if ($tab === 'ulasan'): ?>

      <?php if ((int) $ringkasan['total'] === 0): ?>
        <p class="ul-kosong">Toko ini belum punya ulasan.</p>
      <?php else: ?>
        <?php $this->load->view('parts/ulasan_ringkas', array(
            'ringkasan' => $ringkasan,
            'tautan_filter' => function ($b) use ($url) { return $url(array('tab' => 'ulasan', 'bintang' => $b)); },
        )); ?>

        <?php if ($media): ?>
          <section class="ul-galeri">
            <h2 class="ul-sub-kecil">Foto &amp; video pembeli</h2>
            <?php $this->load->view('parts/ulasan_media', array('media' => $media)); ?>
          </section>
        <?php endif; ?>

        <div class="ul-isi-utama">
          <?php $this->load->view('parts/ulasan_filter', array(
              'ringkasan' => $ringkasan,
              'filter'    => $filter,
              'url'       => function ($g) use ($url) {
                  return $url(array_merge(array('tab' => 'ulasan'), $g));
              },
          )); ?>

          <div class="ul-daftar-wrap">
            <?php $this->load->view('parts/ulasan_daftar', array(
                'ulasan' => $ulasan, 'tampilkan_produk' => TRUE,
            )); ?>

            <?php if ($hal_total > 1): ?>
              <nav class="ul-halaman" aria-label="Halaman ulasan">
                <?php if ($hal > 1): ?>
                  <a href="<?= html_escape($url(array('tab' => 'ulasan', 'page' => $hal - 1))) ?>">Sebelumnya</a>
                <?php else: ?><span></span><?php endif; ?>
                <span>Halaman <?= (int) $hal ?> dari <?= (int) $hal_total ?></span>
                <?php if ($hal < $hal_total): ?>
                  <a href="<?= html_escape($url(array('tab' => 'ulasan', 'page' => $hal + 1))) ?>">Berikutnya</a>
                <?php endif; ?>
              </nav>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    <?php else: ?>

      <!-- Saringan kategori: hanya kategori yang benar-benar dipakai toko
           ini. Menampilkan seluruh kategori marketplace akan memberi banyak
           saringan yang hasilnya kosong. -->
      <div class="tk-saring">
        <div class="tk-chips">
          <a href="<?= html_escape($url(array('kategori' => NULL))) ?>"
             class="tk-chip <?= $kategori === '' ? 'is-aktif' : '' ?>">
            Semua <em><?= (int) $statistik['produk'] ?></em>
          </a>
          <?php foreach ($kategori_toko as $k): ?>
            <a href="<?= html_escape($url(array('kategori' => $k['slug']))) ?>"
               class="tk-chip <?= $kategori === $k['slug'] ? 'is-aktif' : '' ?>">
              <?= html_escape($k['name']) ?> <em><?= (int) $k['jml'] ?></em>
            </a>
          <?php endforeach; ?>
        </div>

        <label class="tk-urut">
          <span class="visually-hidden">Urutkan</span>
          <select onchange="location = this.value;" aria-label="Urutkan produk">
            <?php foreach (array('baru' => 'Terbaru', 'murah' => 'Harga termurah',
                                 'mahal' => 'Harga tertinggi', 'rating' => 'Rating tertinggi') as $k => $label): ?>
              <option value="<?= html_escape($url(array('urut' => $k === 'baru' ? NULL : $k))) ?>"
                <?= $urut === $k ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>

      <?php if (! $produk): ?>
        <div class="empty-state">
          <p class="empty-title">Belum ada produk di kategori ini</p>
          <a href="<?= html_escape($url(array('kategori' => NULL))) ?>" class="btn btn-black-hover-outline">
            Lihat semua produk toko
          </a>
        </div>
      <?php else: ?>

        <p class="result-count">
          Menampilkan <strong><?= count($produk) ?></strong> dari <strong><?= (int) $total ?></strong> produk
        </p>

        <div class="row">
          <?php foreach ($produk as $p): ?>
            <div class="col-6 col-md-4 col-lg-3 mb-4 mb-lg-5">
              <div class="product-item">
                <a class="product-item-link" href="<?= site_url('produk/' . $toko['slug'] . '/' . $p['slug']) ?>">
                  <img src="<?= base_url('upload/produk/' . $p['image']) ?>"
                       alt="<?= html_escape($p['name']) ?>" class="img-fluid product-thumbnail" loading="lazy">
                  <h3 class="product-title"><?= html_escape($p['name']) ?></h3>
                  <strong class="product-price"><?= rupiah($p['price']) ?></strong>
                </a>

                <div class="product-meta">
                  <?php if ($p['category_name']): ?>
                    <p class="product-cat"><?= html_escape($p['category_name']) ?></p>
                  <?php endif; ?>
                  <?php if ((int) $p['rating_count'] > 0): ?>
                    <p class="product-rating">
                      <svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
                      <?= number_format($p['rating_avg'], 1, ',', '.') ?>
                      <span>(<?= (int) $p['rating_count'] ?>)</span>
                    </p>
                  <?php endif; ?>
                </div>

                <button type="button" class="icon-cross btn-add" data-id="<?= (int) $p['id'] ?>"
                        aria-label="Tambah <?= html_escape($p['name']) ?> ke keranjang">
                  <img src="<?= base_url('assets/') ?>images/cross.svg" alt="" class="img-fluid">
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($hal_total > 1): ?>
          <nav class="ul-halaman" aria-label="Halaman produk">
            <?php if ($hal > 1): ?>
              <a href="<?= html_escape($url(array('page' => $hal - 1))) ?>">Sebelumnya</a>
            <?php else: ?><span></span><?php endif; ?>
            <span>Halaman <?= (int) $hal ?> dari <?= (int) $hal_total ?></span>
            <?php if ($hal < $hal_total): ?>
              <a href="<?= html_escape($url(array('page' => $hal + 1))) ?>">Berikutnya</a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
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
</script>
<script src="<?= aset('assets/js/shop.js') ?>"></script>
<script src="<?= aset('assets/js/media-penuh.js') ?>"></script>
