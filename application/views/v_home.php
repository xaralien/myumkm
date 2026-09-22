<!-- application/views/v_home.php -->
<?php
  /* Ikon kategori dipetakan dari kolom categories.icon. Ditaruh di view,
     bukan disimpan sebagai berkas gambar per kategori - mengganti gaya
     ikon cukup di satu tempat ini. */
  $ikon = array(
    'makanan'    => '<path d="M4 8h13v5a6 6 0 0 1-6 6h-1a6 6 0 0 1-6-6V8z"></path><path d="M17 10h2a2 2 0 0 1 0 4h-2"></path><path d="M8 3v2M12 3v2"></path>',
    'kerajinan'  => '<path d="M3 10h18l-2 10H5L3 10z"></path><path d="M8 10l4-6 4 6"></path><path d="M9 14v3M12 14v3M15 14v3"></path>',
    'fashion'    => '<path d="M8 3l-5 3 2 4 3-1v12h8V9l3 1 2-4-5-3-2 2h-4L8 3z"></path>',
    'kecantikan' => '<path d="M9 3h6v4H9z"></path><path d="M8 7h8l1 4v9a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-9l1-4z"></path>',
    'hadiah'     => '<path d="M4 10h16v10H4z"></path><path d="M3 7h18v3H3z"></path><path d="M12 7v13"></path><path d="M12 7c-2-3-5-3-5-1s3 1 5 1c2 0 5 1 5-1s-3-2-5 1"></path>',
    'rumah'      => '<path d="M4 11l8-7 8 7v9H4z"></path><path d="M10 20v-6h4v6"></path>',
    'umum'       => '<path d="M4 7h16l-1.5 13h-13L4 7z"></path><path d="M9 7V5a3 3 0 0 1 6 0v2"></path>',
  );

  $svg_ikon = function ($kunci) use ($ikon) {
    $isi = isset($ikon[$kunci]) ? $ikon[$kunci] : $ikon['umum'];
    return '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $isi . '</svg>';
  };

  $url_produk = function ($p) {
    return site_url('produk/' . $p['store_slug'] . '/' . $p['slug']);
  };

  // Inisial untuk avatar toko yang belum mengunggah logo.
  $inisial = function ($nama) {
    $kata = preg_split('/\s+/', trim($nama));
    $out = '';
    foreach (array_slice($kata, 0, 2) as $k) {
      $out .= function_exists('mb_substr') ? mb_substr($k, 0, 1) : substr($k, 0, 1);
    }
    return strtoupper($out);
  };

  $cek = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l4 4 10-10"></path></svg>';
?>

<!-- =============================== HERO =============================== -->
<section class="nt-hero">
  <div class="nt-wrap nt-hero-in">

    <div class="nt-hero-teks">
      <span class="nt-kicker">Dari tangan-tangan lokal</span>
      <h1>Produk UMKM terbaik, dikirim dari kota yang sama.</h1>
      <p class="nt-lead">
        Makanan rumahan, kerajinan tangan, batik, sampai perawatan alami —
        semuanya dari pelaku usaha di sekitarmu, langsung dari dapur dan
        bengkel mereka.
      </p>

      <div class="nt-aksi">
        <a href="<?= site_url('shop') ?>" class="nt-tombol">
          Cari produk di sekitarmu
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
        </a>
        <a href="#kategori" class="nt-tombol-garis">Lihat kategori</a>
      </div>

      <!-- Tiga janji ini sesuai fitur yang memang berjalan - bukan klaim
           pemasaran. Kalau salah satunya dimatikan, hapus juga dari sini. -->
      <ul class="nt-janji">
        <li><?= $cek ?>Tanpa perlu daftar akun</li>
        <li><?= $cek ?>Transfer, e-wallet, QRIS</li>
        <li><?= $cek ?>Chat langsung penjual</li>
      </ul>
    </div>

    <div class="nt-panel">
      <?php if ($unggulan): ?>
        <div class="nt-panel-foto">
          <img src="<?= base_url('upload/produk/' . $unggulan['image']) ?>"
               alt="<?= html_escape($unggulan['name']) ?>">
        </div>

        <a class="nt-panel-kartu" href="<?= $url_produk($unggulan) ?>">
          <?php if (! empty($unggulan['category_name'])): ?>
            <span class="nt-label"><?= html_escape($unggulan['category_name']) ?></span>
          <?php endif; ?>
          <b><?= html_escape($unggulan['name']) ?></b>
          <span class="nt-meta">
            <?= html_escape($unggulan['store_name']) ?><?php
              if (! empty($unggulan['store_district'])) { echo ', ' . html_escape($unggulan['store_district']); }
            ?>
          </span>
          <span class="nt-harga"><?= rupiah($unggulan['price']) ?></span>
        </a>
      <?php else: ?>
        <!-- Belum ada produk sama sekali: panel tetap tampil dengan motif
             saja, tanpa kartu kosong yang terlihat rusak. -->
        <div class="nt-panel-foto"></div>
      <?php endif; ?>

      <?php if ($lokasi): ?>
        <span class="nt-panel-lencana">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
          Di sekitar <?= html_escape($lokasi['district_name']) ?>
        </span>
      <?php endif; ?>
    </div>

  </div>
</section>

<!-- ============================= KATEGORI ============================= -->
<?php if ($categories): ?>
<section class="nt-bagian" id="kategori">
  <div class="nt-wrap">
    <div class="nt-judul-baris">
      <h2>Jelajahi kategori</h2>
      <a href="<?= site_url('shop') ?>" class="nt-tautan">Semua kategori →</a>
    </div>

    <div class="nt-kategori">
      <?php foreach ($categories as $c): ?>
        <a href="<?= site_url('shop') . '?category=' . rawurlencode($c['slug']) ?>" class="nt-kat">
          <span class="nt-kat-ikon"><?= $svg_ikon($c['icon']) ?></span>
          <b><?= html_escape($c['name']) ?></b>
          <small>
            <?= $c['keterangan']
                  ? html_escape($c['keterangan'])
                  : ((int) $c['jml'] . ' produk') ?>
          </small>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============================== PRODUK ============================== -->
<section class="nt-bagian">
  <div class="nt-wrap">
    <div class="nt-judul-baris">
      <h2><?= $lokasi ? 'Terbaru di sekitarmu' : 'Produk terbaru' ?></h2>

      <?php if ($categories): ?>
        <!-- Tautan, bukan tombol filter. Filter sungguhan ada di katalog -
             di sini cukup jalan pintas ke sana. -->
        <nav class="nt-chips" aria-label="Kategori cepat">
          <a href="<?= site_url('shop') ?>" class="nt-chip is-aktif">Semua</a>
          <?php foreach (array_slice($categories, 0, 3) as $c): ?>
            <a href="<?= site_url('shop') . '?category=' . rawurlencode($c['slug']) ?>" class="nt-chip">
              <?= html_escape($c['name']) ?>
            </a>
          <?php endforeach; ?>
        </nav>
      <?php endif; ?>
    </div>

    <?php if ($products): ?>
      <div class="nt-produk">
        <?php foreach ($products as $p): ?>
          <article class="nt-kartu">
            <!-- Tautan dan tombol + dipisah. <button> di dalam <a> itu HTML
                 tidak sah, dan browser bingung mana yang dijalankan. -->
            <a class="nt-kartu-tautan" href="<?= $url_produk($p) ?>">
              <div class="nt-kartu-foto">
                <img src="<?= base_url('upload/produk/' . $p['image']) ?>"
                     alt="<?= html_escape($p['name']) ?>" loading="lazy">
              </div>
              <div class="nt-kartu-isi">
                <?php if (! empty($p['category_name'])): ?>
                  <span class="nt-label"><?= html_escape($p['category_name']) ?></span>
                <?php endif; ?>
                <b><?= html_escape($p['name']) ?></b>
                <span class="nt-meta">
                  <?= html_escape($p['store_name']) ?><?php
                    if (! empty($p['store_district'])) { echo ', ' . html_escape($p['store_district']); }
                  ?>
                </span>
              </div>
            </a>

            <div class="nt-kartu-bawah">
              <span class="nt-harga">
                <?php if ((int) $p['variant_count'] > 1): ?>
                  <small style="font-family: var(--nt-sans); font-size: 12px; font-weight: 500;">mulai</small>
                <?php endif; ?>
                <?= rupiah($p['price']) ?>
              </span>
              <!-- .btn-add + data-id dipakai shop.js untuk membuka panel
                   pilihan ukuran & tambahan. -->
              <button type="button" class="nt-tambah btn-add"
                      data-id="<?= (int) $p['id'] ?>"
                      aria-label="Tambah <?= html_escape($p['name']) ?> ke keranjang">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
              </button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="nt-kosong">
        <p class="mb-3">Belum ada produk di wilayah ini.</p>
        <a href="<?= site_url('shop') ?>" class="nt-tombol-garis">Lihat semua produk</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- =========================== TOKO DEKAT ============================ -->
<?php if ($toko_dekat): ?>
<section class="nt-pita">
  <div class="nt-wrap">
    <div class="nt-judul-baris">
      <div>
        <h2>Toko di sekitarmu</h2>
        <p class="nt-sub">
          <?= $lokasi
                ? 'Diurutkan dari yang paling dekat dengan ' . html_escape($lokasi['district_name']) . '.'
                : 'Pilih lokasimu supaya toko terdekat tampil lebih dulu.' ?>
        </p>
      </div>
      <a href="<?= site_url('shop') ?>?dekat=1&amp;radius=10" class="nt-tautan">Cari berdasarkan jarak →</a>
    </div>

    <div class="nt-toko">
      <?php foreach ($toko_dekat as $i => $t): ?>
        <div class="nt-toko-kartu">
          <div class="nt-toko-kepala">
            <span class="nt-avatar nt-avatar-<?= $i % 3 ?>">
              <?php if (! empty($t['avatar'])): ?>
                <img src="<?= base_url('upload/avatar/' . $t['avatar']) ?>" alt="">
              <?php else: ?>
                <?= html_escape($inisial($t['name'])) ?>
              <?php endif; ?>
            </span>
            <div class="nt-toko-nama">
              <b><?= html_escape($t['name']) ?></b>
              <span class="nt-meta">
                <?= (int) $t['jml_produk'] ?> produk<?php
                  if (! empty($t['district_name'])) { echo ', ' . html_escape($t['district_name']); }
                ?>
              </span>
            </div>
            <?php if (! empty($t['label_jarak'])): ?>
              <span class="nt-jarak"><?= html_escape($t['label_jarak']) ?></span>
            <?php endif; ?>
          </div>

          <?php if (! empty($t['foto'])): ?>
            <div class="nt-mini">
              <?php foreach ($t['foto'] as $f): ?>
                <span><img src="<?= base_url('upload/produk/' . $f) ?>" alt="" loading="lazy"></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <a href="<?= site_url('shop') . '?store=' . (int) $t['id'] ?>" class="nt-tautan">Kunjungi toko →</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- =========================== CARA BELANJA ========================== -->
<section class="nt-langkah-bagian">
  <div class="nt-wrap">
    <h2 class="nt-h2">Belanja semudah ke pasar</h2>
    <!-- <ol>, bukan tiga <div>: urutannya memang berurutan, dan pembaca
         layar mengumumkan "daftar, 3 butir". -->
    <ol class="nt-langkah">
      <li>
        <b>Pilih lokasimu</b>
        <span>Kami tampilkan UMKM terdekat lebih dulu, supaya pesananmu cepat sampai dan ongkirnya ringan.</span>
      </li>
      <li>
        <b>Pesan tanpa akun</b>
        <span>Isi alamat pengiriman, lalu bayar lewat transfer bank, e-wallet, atau QRIS.</span>
      </li>
      <li>
        <b>Pantau &amp; chat penjual</b>
        <span>Lacak pesanan kapan saja, dan tanya langsung ke penjualnya lewat chat.</span>
      </li>
    </ol>
  </div>
</section>

<!-- ============================ AJAKAN UMKM ========================== -->
<section class="nt-cta-wrap">
  <div class="nt-wrap">
    <div class="nt-cta">
      <div>
        <h2>Punya usaha? Jualkan di sini.</h2>
        <p>Buka tokomu dan jangkau pembeli di sekitar. Kelola produk, pesanan, dan chat pelanggan dari satu tempat.</p>
      </div>
      <!-- Buka toko sendiri dari akun. Kalau belum masuk, Member_Controller
           mengarahkan ke login lalu kembali ke sini sesudahnya. -->
      <a href="<?= site_url('akun/buka_toko') ?>" class="nt-tombol nt-tombol-kunyit">Buka toko</a>
    </div>
  </div>
</section>

<script>
  window.SHOP_URLS = {
    options:  '<?= site_url('cart/options') ?>',
    add:      '<?= site_url('cart/add') ?>',
    clear:    '<?= site_url('cart/clear') ?>',
    checkout: '<?= site_url('checkout') ?>'
  };
  window.CSRF = {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= base_url('assets/js/shop.js') ?>"></script>
<?php $this->load->view('v_location_modal'); ?>
