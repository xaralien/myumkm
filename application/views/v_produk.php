<!-- application/views/v_produk.php -->
<?php
$harga_dasar = (int) $p['price'];
$url_gambar = base_url('upload/produk/' . $p['image']);
$link_toko = site_url('shop') . '?store=' . (int) $p['store_id'];

// Pesan WhatsApp disiapkan lengkap supaya penjual langsung tahu
// produk mana yang ditanyakan, tanpa pembeli perlu menjelaskan ulang.
$pesan_wa = rawurlencode(
    'Halo ' . $p['store_name'] . ', saya mau tanya produk "'
    . $p['name'] . '" - ' . current_url()
);

$label_jarak = array(
    1 => 'Toko di kecamatanmu',
    2 => 'Toko di kotamu',
    3 => 'Toko di provinsimu',
);
?>

<div class="untree_co-section before-footer-section produk-detail">
    <div class="container">

        <!-- Jejak navigasi: pembeli sering mendarat di sini dari tautan yang
         dibagikan, jadi perlu tahu sedang ada di mana. -->
        <nav class="remah" aria-label="Jejak navigasi">
            <a href="<?= site_url('shop') ?>">Katalog</a>
            <?php if (!empty($p['category_slug'])): ?>
                <span>/</span>
                <a href="<?= site_url('shop') . '?category=' . html_escape($p['category_slug']) ?>">
                    <?= html_escape($p['category_name']) ?>
                </a>
            <?php endif; ?>
            <span>/</span>
            <strong><?= html_escape($p['name']) ?></strong>
        </nav>

        <?php if ($jarak === 4): ?>
            <div class="alert-box alert-warn mb-4">
                Toko ini berada di <?= html_escape($p['store_province']) ?>, di luar provinsi
                yang kamu pilih. Bunga segar umumnya tidak dikirim antarprovinsi &mdash;
                hubungi tokonya dulu sebelum memesan.
            </div>
        <?php endif; ?>

        <div class="row">

            <!-- ==================== FOTO ==================== -->
            <div class="col-lg-6 mb-4 mb-lg-0">
  <div class="produk-foto">
    <img id="fotoUtama" src="<?= $url_gambar ?>"
         data-asal="<?= $url_gambar ?>"
         alt="<?= html_escape($p['name']) ?>" class="img-fluid">
  </div>

  <?php
/* Kumpulkan semua gambar yang ada: produk, lalu varian, lalu tambahan.
   Duplikat dibuang - beberapa varian bisa memakai foto yang sama. */
$galeri = array(array('url' => $url_gambar, 'label' => 'Foto utama'));
$sudah = array($p['image']);

foreach ($variants as $v) {
    if (!empty($v['image']) && !in_array($v['image'], $sudah, TRUE)) {
        $sudah[] = $v['image'];
        $galeri[] = array(
            'url' => base_url('upload/produk/' . $v['image']),
            'label' => $v['name'],
        );
    }
}
foreach ($addons as $a) {
    if (!empty($a['image']) && !in_array($a['image'], $sudah, TRUE)) {
        $sudah[] = $a['image'];
        $galeri[] = array(
            'url' => base_url('upload/produk/' . $a['image']),
            'label' => $a['name'],
        );
    }
}
?>
 
  <?php if (count($galeri) > 1): ?>
    <!-- Deretan thumbnail. Tetap ditampilkan walau gambar juga berubah
         otomatis saat memilih varian - pembeli sering ingin kembali
         melihat foto utama tanpa mengubah pilihannya. -->
    <div class="produk-galeri" id="produkGaleri">
      <?php foreach ($galeri as $i => $g): ?>
        <button type="button" class="produk-thumb<?= $i === 0 ? ' is-aktif' : '' ?>"
                data-gambar="<?= html_escape($g['url']) ?>"
                aria-label="Lihat <?= html_escape($g['label']) ?>">
          <img src="<?= html_escape($g['url']) ?>" alt="" loading="lazy">
        </button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

            <!-- ==================== INFORMASI ==================== -->
            <div class="col-lg-6">

                <?php if (!empty($p['category_name'])): ?>
                    <p class="produk-kategori"><?= html_escape($p['category_name']) ?></p>
                <?php endif; ?>

                <h1 class="produk-nama"><?= html_escape($p['name']) ?></h1>

                <!-- Harga ikut berubah saat varian dan tambahan dipilih.
             Nilainya hanya untuk ditampilkan - server menghitung ulang
             semuanya saat produk masuk keranjang. -->
                <p class="produk-harga">
                    <span id="hargaTampil"><?= rupiah($harga_dasar) ?></span>
                    <?php if (count($variants) > 1): ?>
                        <em id="hargaKet">harga ukuran terkecil</em>
                    <?php endif; ?>
                </p>

                <!-- ---------- TOKO ---------- -->
                <div class="produk-toko">
                    <div>
                        <p class="produk-toko-nama"><?= html_escape($p['store_name']) ?></p>
                        <p class="produk-toko-lokasi">
                            <?= html_escape($p['store_district']) ?>,
                            <?= html_escape($p['store_regency']) ?>
                            <?php if ($jarak && isset($label_jarak[$jarak])): ?>
                                <span class="badge-jarak"><?= $label_jarak[$jarak] ?></span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="<?= html_escape($link_toko) ?>" class="produk-toko-link">Lihat toko</a>
                </div>

                <!-- ---------- PILIHAN ---------- -->
                <div class="produk-form"
                    data-id="<?= (int) $p['id'] ?>"
                    data-harga="<?= $harga_dasar ?>">

                    <?php if ($variants): ?>
    <p class="produk-label">Ukuran</p>
    <div class="produk-opsi" role="radiogroup" aria-label="Ukuran">
        <?php foreach ($variants as $i => $v): ?>
            <label class="produk-opt">
                <input type="radio" name="variant_id" value="<?= (int) $v['id'] ?>"
                    data-delta="<?= (int) $v['price_delta'] ?>"
                    data-gambar="<?= !empty($v['image']) ? base_url('upload/produk/' . $v['image']) : '' ?>"
                    <?= $i === 0 ? 'checked' : '' ?>>
                <span>
                    <em><?= html_escape($v['name']) ?></em>
                    <b><?= rupiah($harga_dasar + (int) $v['price_delta']) ?></b>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

                    <?php if ($addons): ?>
    <p class="produk-label">Tambahan <span>(opsional)</span></p>
    <div class="produk-chips">
        <?php foreach ($addons as $a): ?>
            <label class="produk-chip">
                <input type="checkbox" name="addons[]" value="<?= (int) $a['id'] ?>"
                    data-delta="<?= (int) $a['price_delta'] ?>"
                    data-gambar="<?= !empty($a['image']) ? base_url('upload/produk/' . $a['image']) : '' ?>">
                <span>
                    <?= html_escape($a['name']) ?>
                    <em><?= (int) $a['price_delta'] ? '+' . rupiah($a['price_delta']) : 'gratis' ?></em>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

                    <!-- ---------- JUMLAH & TOTAL ---------- -->
                    <div class="produk-bawah">
                        <div>
                            <p class="produk-total-label">Total</p>
                            <p class="produk-total" id="totalTampil"><?= rupiah($harga_dasar) ?></p>
                        </div>
                        <div class="qty-box">
                            <button type="button" class="qty-btn" data-qty="-1" aria-label="Kurangi">&minus;</button>
                            <span class="qty-num" id="qtyTampil">1</span>
                            <button type="button" class="qty-btn" data-qty="1" aria-label="Tambah">+</button>
                        </div>
                    </div>

                    <div class="produk-aksi">
                        <button type="button" class="btn btn-black-hover-outline" data-act="add">
                            Tambah ke keranjang
                        </button>
                        <!-- <button type="button" class="btn btn-primary" data-act="buy">
                            Pesan sekarang
                        </button> -->
                    </div>

                    <p class="produk-catatan" data-note></p>

                    <!-- Tombol WhatsApp, bukan chat di dalam aplikasi. Penjual bunga
               memang hidup di WhatsApp dan akan membalas jauh lebih cepat
               di sana. -->
                    <!-- <a class="produk-wa" href="https://wa.me/<?= $wa_toko ?>?text=<?= $pesan_wa ?>"
                        target="_blank" rel="noopener">
                        Tanya penjual lewat WhatsApp
                    </a> -->
                </div>

                <!-- ---------- PENGIRIMAN ---------- -->
                <div class="produk-kirim">
                    <p class="produk-label">Pengiriman</p>
                    <?php if ($same_day): ?>
                        <p class="produk-kirim-ok">Bisa dikirim hari ini &mdash; paling cepat pukul <?= $jam_siap ?></p>
                    <?php else: ?>
                        <p class="produk-kirim-warn">
                            Pengiriman hari ini sudah tidak memungkinkan &mdash; toko tutup pukul
                            <?= $jam_tutup ?> dan bunganya perlu dirangkai dulu. Paling cepat besok.
                        </p>
                    <?php endif; ?>
                    <p class="produk-kirim-slot">
                        Jam antar: <?= $jam_buka ?> &ndash; <?= $jam_tutup ?>
                    </p>
                    <p class="hint">Tanggal dan alamat penerima diisi saat checkout.</p>
                </div>

            </div>
        </div>

        <!-- ==================== DESKRIPSI ==================== -->
        <?php if (!empty($p['description'])): ?>
            <div class="row mt-5">
                <div class="col-lg-8">
                    <h2 class="produk-sub">Deskripsi</h2>
                    <p class="produk-desk"><?= nl2br(html_escape($p['description'])) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- ==================== PRODUK LAIN ==================== -->
        <?php if ($lainnya): ?>
            <div class="mt-5">
                <h2 class="produk-sub">Produk lain dari <?= html_escape($p['store_name']) ?></h2>
                <div class="row mt-3">
                    <?php foreach ($lainnya as $l): ?>
                        <div class="col-6 col-md-3 mb-4">
                            <div class="product-item">
                                <a class="product-item-link"
                                    href="<?= site_url('produk/' . $p['store_slug'] . '/' . $l['slug']) ?>">
                                    <img src="<?= base_url('upload/produk/' . $l['image']) ?>"
                                        alt="<?= html_escape($l['name']) ?>"
                                        class="img-fluid product-thumbnail" loading="lazy">
                                    <h3 class="product-title"><?= html_escape($l['name']) ?></h3>
                                    <strong class="product-price">
                                        <?php if ((int) $l['variant_count'] > 1): ?>
                                            <span class="price-prefix">Mulai</span>
                                        <?php endif; ?>
                                        <?= rupiah($l['price']) ?>
                                    </strong>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<script>
    window.PRODUK_URLS = {
        add: '<?= site_url('cart/add') ?>',
        clear: '<?= site_url('cart/clear') ?>',
        checkout: '<?= site_url('checkout') ?>'
    };
    window.CSRF = {
        name: '<?= $this->security->get_csrf_token_name() ?>',
        hash: '<?= $this->security->get_csrf_hash() ?>'
    };
</script>
<script src="<?= base_url('assets/js/produk.js') ?>"></script>
<script src="<?= base_url('assets/js/produk-galeri.js') ?>"></script>
