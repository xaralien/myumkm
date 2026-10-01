<!-- application/views/mobile/v_produk.php -->
<?php
  $harga_dasar = (int) $p['price'];
  $url_gambar  = base_url('upload/produk/' . $p['image']);

  // Foto produk + foto varian/tambahan, tanpa duplikat.
  $galeri = array($url_gambar);
  $sudah  = array($p['image']);
  foreach (array_merge($variants, $addons) as $x) {
    if (! empty($x['image']) && ! in_array($x['image'], $sudah, TRUE)) {
      $sudah[]  = $x['image'];
      $galeri[] = base_url('upload/produk/' . $x['image']);
    }
  }
?>

<div class="mb-foto">
  <!-- Deret foto yang mengunci per gambar: satu sapuan = satu foto,
       bukan berhenti di tengah-tengah dua gambar. -->
  <div class="mb-foto-geser" id="fotoGeser">
    <?php foreach ($galeri as $g): ?>
      <img src="<?= html_escape($g) ?>" alt="<?= html_escape($p['name']) ?>">
    <?php endforeach; ?>
  </div>
  <?php if (count($galeri) > 1): ?>
    <div class="mb-foto-titik" id="fotoTitik" aria-hidden="true">
      <?php foreach ($galeri as $i => $g): ?>
        <span class="<?= $i === 0 ? 'is-aktif' : '' ?>"></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <a href="<?= site_url('shop') ?>" class="mb-kembali" aria-label="Kembali ke katalog">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"></path></svg>
  </a>
</div>

<div class="mb-detail produk-form" data-id="<?= (int) $p['id'] ?>" data-harga="<?= $harga_dasar ?>">

  <?php if (! empty($p['category_name'])): ?>
    <span class="mb-detail-kat"><?= html_escape($p['category_name']) ?></span>
  <?php endif; ?>
  <h1><?= html_escape($p['name']) ?></h1>

  <p class="mb-detail-harga">
    <span id="hargaTampil"><?= rupiah($harga_dasar) ?></span>
    <?php if (count($variants) > 1): ?><em id="hargaKet">harga ukuran terkecil</em><?php endif; ?>
  </p>

  <div class="mb-detail-toko">
    <a href="<?= site_url('shop') . '?store=' . (int) $p['store_id'] ?>">
      <b><?= html_escape($p['store_name']) ?></b>
      <em><?= html_escape($p['store_district']) ?>, <?= html_escape($p['store_regency']) ?></em>
    </a>
    <!-- Produk ini ikut terbawa ke percakapan. -->
    <?= form_open('chat/toko/' . $p['store_slug'], array('class' => 'mb-tanya')) ?>
      <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
      <button type="submit">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
        Tanya
      </button>
    <?= form_close() ?>
  </div>

  <?php if ($variants): ?>
    <p class="mb-detail-label">Ukuran</p>
    <div class="mb-opsi" role="radiogroup" aria-label="Ukuran">
      <?php foreach ($variants as $i => $v): ?>
        <label class="mb-opsi-item">
          <input type="radio" name="variant_id" value="<?= (int) $v['id'] ?>"
                 data-delta="<?= (int) $v['price_delta'] ?>"
                 data-gambar="<?= ! empty($v['image']) ? base_url('upload/produk/' . $v['image']) : '' ?>"
                 <?= $i === 0 ? 'checked' : '' ?>>
          <span>
            <b><?= html_escape($v['name']) ?></b>
            <em><?= rupiah($harga_dasar + (int) $v['price_delta']) ?></em>
          </span>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($addons): ?>
    <p class="mb-detail-label">Tambahan <span>(opsional)</span></p>
    <div class="mb-opsi">
      <?php foreach ($addons as $a): ?>
        <label class="mb-opsi-item">
          <input type="checkbox" name="addons[]" value="<?= (int) $a['id'] ?>"
                 data-delta="<?= (int) $a['price_delta'] ?>"
                 data-gambar="<?= ! empty($a['image']) ? base_url('upload/produk/' . $a['image']) : '' ?>">
          <span>
            <b><?= html_escape($a['name']) ?></b>
            <em><?= (int) $a['price_delta'] ? '+' . rupiah($a['price_delta']) : 'gratis' ?></em>
          </span>
        </label>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="mb-kirim">
    <p><strong>Dikirim dari <?= html_escape($p['store_district']) ?></strong></p>
    <p>Diproses <?= (int) $proses ?> hari kerja setelah pembayaran diterima. Ongkir dihitung saat checkout.</p>
  </div>

  <?php if (! empty($p['description'])): ?>
    <p class="mb-detail-label">Deskripsi</p>
    <div class="mb-deskripsi"><?= html_aman($p['description']) ?></div>
  <?php endif; ?>

  <p class="produk-catatan" data-note></p>
</div>

<?php $this->load->view('mobile/parts/ulasan'); ?>

<?php if ($lainnya): ?>
  <section class="mb-bagian">
    <div class="mb-judul"><h2>Lainnya dari toko ini</h2></div>
    <div class="mb-geser">
      <?php foreach ($lainnya as $l): ?>
        <a href="<?= site_url('produk/' . $p['store_slug'] . '/' . $l['slug']) ?>" class="mb-kartu">
          <div class="mb-kartu-foto">
            <img src="<?= base_url('upload/produk/' . $l['image']) ?>" alt="<?= html_escape($l['name']) ?>" loading="lazy">
          </div>
          <div class="mb-kartu-isi">
            <span class="mb-kartu-nama"><?= html_escape($l['name']) ?></span>
            <span class="mb-kartu-harga"><?= rupiah($l['price']) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<!-- Bilah beli menempel di atas tab bar: di halaman panjang, tombol beli
     yang ikut tergulir ke bawah sering tidak pernah ditemukan. -->
<div class="mb-beli produk-form" data-id="<?= (int) $p['id'] ?>" data-harga="<?= $harga_dasar ?>">
  <div class="mb-beli-total">
    <span>Total</span>
    <strong id="totalTampil"><?= rupiah($harga_dasar) ?></strong>
  </div>
  <div class="qty-box mb-qty">
    <button type="button" class="qty-btn" data-qty="-1" aria-label="Kurangi">&minus;</button>
    <span class="qty-num" id="qtyTampil">1</span>
    <button type="button" class="qty-btn" data-qty="1" aria-label="Tambah">+</button>
  </div>
  <button type="button" class="mb-beli-btn is-garis" data-act="add" aria-label="Tambah ke keranjang">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2.4 11h11l2-8H6.2"></path><circle cx="9" cy="19" r="1.5"></circle><circle cx="17" cy="19" r="1.5"></circle></svg>
  </button>
  <button type="button" class="mb-beli-btn" data-act="buy">Beli sekarang</button>
</div>

<script>
  window.PRODUK_URLS = {
    add: '<?= site_url('cart/add') ?>',
    langsung: '<?= site_url('cart/beli_langsung') ?>',
    clear: '<?= site_url('cart/clear') ?>',
    checkout: '<?= site_url('checkout') ?>'
  };
  window.CSRF = {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= aset('assets/js/produk.js') ?>"></script>
<script src="<?= aset('assets/js/media-penuh.js') ?>"></script>
<script src="<?= aset('assets/js/ulasan-potong.js') ?>"></script>
<script>
  (function () {
    var geser = document.getElementById('fotoGeser');
    if (!geser) { return; }

    var titik = document.getElementById('fotoTitik');

    /* Titik penanda dihitung dari posisi gulir, bukan dari event khusus -
       cara ini jalan baik saat disapu jari maupun digulir mouse. */
    if (titik) {
      var bulat = titik.children;
      geser.addEventListener('scroll', function () {
        var i = Math.round(geser.scrollLeft / geser.clientWidth);
        for (var n = 0; n < bulat.length; n++) {
          bulat[n].classList.toggle('is-aktif', n === i);
        }
      }, { passive: true });
    }

    /* Memilih ukuran yang punya foto sendiri akan menggeser galeri ke foto
       itu. Di desktop fotonya tinggal ditukar karena hanya ada satu gambar
       besar; di sini galerinya berupa deretan, jadi yang dilakukan adalah
       menggulir ke gambar yang cocok - foto lain tetap bisa dilihat. */
    var gambar = geser.querySelectorAll('img');

    function keFoto(alamat) {
      for (var i = 0; i < gambar.length; i++) {
        // Bandingkan alamat setelah dinormalkan browser, bukan teks mentah.
        if (gambar[i].src === alamat || gambar[i].getAttribute('src') === alamat) {
          geser.scrollTo({ left: i * geser.clientWidth, behavior: 'smooth' });
          return;
        }
      }
    }

    document.addEventListener('change', function (e) {
      var t = e.target;
      if (!t.dataset || !t.dataset.gambar) { return; }

      /* Ukuran: fotonya ditampilkan begitu dipilih.
         Tambahan: hanya saat DICENTANG - kalau dilepas centangnya, galeri
         dibiarkan di tempat. Melompat balik ke foto utama saat orang
         membatalkan satu tambahan terasa seperti halaman kehilangan
         tempatnya sendiri. */
      if (t.name === 'variant_id' || (t.name === 'addons[]' && t.checked)) {
        keFoto(t.dataset.gambar);
      }
    });
  })();
</script>
