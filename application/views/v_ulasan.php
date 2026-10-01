<!-- application/views/v_ulasan.php - semua ulasan, tab produk & toko -->
<?php
  $dasar = site_url('ulasan/produk/' . $p['store_slug'] . '/' . $p['slug']);

  $url = function (array $ganti = array()) use ($dasar, $tab, $filter) {
    $q = array_filter(array(
      'tab'     => $tab === 'toko' ? 'toko' : NULL,
      'bintang' => $filter['rating'] ?: NULL,
      'media'   => $filter['media'] ? '1' : NULL,
    ));
    foreach ($ganti as $k => $v) {
      if ($v === NULL) { unset($q[$k]); } else { $q[$k] = $v; }
    }
    return $dasar . ($q ? '?' . http_build_query($q) : '');
  };
?>

<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Ulasan</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container">

    <p class="ul-balik">
      <a href="<?= site_url('produk/' . $p['store_slug'] . '/' . $p['slug']) ?>">&larr; Kembali ke produk</a>
    </p>

    <!-- Dua tab: ulasan produk ini saja, atau seluruh produk toko. Dibuat
         sebagai tautan biasa supaya tiap tab punya alamat sendiri yang bisa
         dibagikan dan tercatat di riwayat browser. -->
    <nav class="ul-tab" aria-label="Jenis ulasan">
      <a href="<?= html_escape($url(array('tab' => NULL))) ?>"
         class="<?= $tab === 'produk' ? 'is-aktif' : '' ?>">
        Produk ini
      </a>
      <a href="<?= html_escape($url(array('tab' => 'toko'))) ?>"
         class="<?= $tab === 'toko' ? 'is-aktif' : '' ?>">
        Semua produk toko
      </a>
    </nav>

    <div class="ul-kepala-produk">
      <img src="<?= base_url('upload/produk/' . $p['image']) ?>" alt="" loading="lazy">
      <div>
        <strong><?= $tab === 'toko' ? html_escape($p['store_name']) : html_escape($p['name']) ?></strong>
        <em><?= $tab === 'toko' ? 'Ulasan dari seluruh produk toko' : 'Ulasan produk ini' ?></em>
      </div>
    </div>

    <?php $this->load->view('parts/ulasan_ringkas', array(
        'ringkasan' => $ringkasan,
        'tautan_filter' => function ($b) use ($url) { return $url(array('bintang' => $b)); },
    )); ?>

    <?php if ($media): ?>
      <section class="ul-galeri">
        <h2 class="ul-sub-kecil">Foto &amp; video pembeli</h2>
        <?php $this->load->view('parts/ulasan_media', array(
            'media' => $media, 'maks' => 6,
        )); ?>
      </section>
    <?php endif; ?>

    <div class="ul-isi-utama">

      <?php $this->load->view('parts/ulasan_filter', array(
          'ringkasan' => $ringkasan,
          'filter'    => $filter,
          'url'       => $url,
      )); ?>

      <section class="ul-daftar-wrap">
        <div class="ul-daftar-kepala">
          <h2 class="ul-sub">Ulasan pembeli</h2>
          <span class="hint">
            <?= $total ? 'Menampilkan ' . count($ulasan) . ' dari ' . (int) $total . ' ulasan' : '' ?>
          </span>
        </div>

        <?php $this->load->view('parts/ulasan_daftar', array(
            'ulasan' => $ulasan,
            'tampilkan_produk' => $tab === 'toko',
        )); ?>

        <?php if ($hal_total > 1): ?>
          <nav class="ul-halaman" aria-label="Halaman ulasan">
            <?php if ($hal > 1): ?>
              <a href="<?= html_escape($url(array('page' => $hal - 1))) ?>">Sebelumnya</a>
            <?php endif; ?>
            <span>Halaman <?= (int) $hal ?> dari <?= (int) $hal_total ?></span>
            <?php if ($hal < $hal_total): ?>
              <a href="<?= html_escape($url(array('page' => $hal + 1))) ?>">Berikutnya</a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
      </section>

    </div>
  </div>
</div>

<script src="<?= aset('assets/js/media-penuh.js') ?>"></script>
