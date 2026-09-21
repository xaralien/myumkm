<!-- application/views/v_navbar.php -->
<?php
  /* Dimuat di sini, bukan diandalkan sudah dimuat controller. Loader CI
     mengabaikan pemanggilan kedua, jadi aman - dan tanpa ini halaman yang
     controllernya tidak memuat keduanya (login, lacak) akan fatal error. */
  $this->load->library(array('cart_lib', 'location_lib'));

  $jml_cart = (int) $this->cart_lib->count();
  $brand    = $this->config->item('nama_brand') ?: 'Nama Brand';
  $slogan   = $this->config->item('slogan') ?: 'Belanja langsung dari pelaku UMKM di kotamu';

  $lokasi_label = $this->location_lib->has() ? $this->location_lib->label() : NULL;

  /* Tautan aktif ditandai dengan aria-current, bukan hanya kelas. Pembaca
     layar mengumumkannya sebagai "halaman saat ini", dan CSS memakai
     atribut yang sama untuk garis bawahnya - satu sumber, dua kegunaan. */
  $seg = $this->uri->segment(1);
  $aktif = function ($nama) use ($seg) {
      $cocok = ($nama === 'beranda') ? ($seg === NULL || $seg === '' || $seg === 'home') : ($seg === $nama);
      return $cocok ? ' aria-current="page"' : '';
  };
?>

<div class="nt-strip">
  <div class="nt-wrap nt-strip-in">
    <span class="nt-strip-teks"><?= html_escape($slogan) ?></span>

    <!-- data-open-location ditangkap location.js untuk membuka modal wilayah.
         Modal itu hanya dimuat di beranda & katalog - di halaman lain,
         skrip di bawah mengarahkan ke katalog sebagai gantinya. -->
    <button type="button" class="nt-lokasi" data-open-location
            data-cadangan="<?= site_url('shop') ?>">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
      Kirim ke: <strong><?= $lokasi_label ? html_escape($lokasi_label) : 'Pilih lokasi' ?></strong>
    </button>
  </div>
</div>

<header class="nt-nav">
  <div class="nt-wrap nt-nav-in">

    <a class="nt-logo" href="<?= base_url() ?>">
      <!-- Tanda kawung kecil - motif yang sama dengan latar hero. -->
      <svg viewBox="0 0 40 40" width="38" height="38" aria-hidden="true"><rect width="40" height="40" rx="10" fill="#A8441F"></rect><g fill="none" stroke="#F3D08A" stroke-width="1.8"><ellipse cx="20" cy="11" rx="5" ry="8"></ellipse><ellipse cx="20" cy="29" rx="5" ry="8"></ellipse><ellipse cx="11" cy="20" rx="8" ry="5"></ellipse><ellipse cx="29" cy="20" rx="8" ry="5"></ellipse></g></svg>
      <span><?= html_escape($brand) ?></span>
    </a>

    <nav class="nt-menu" id="ntMenu" aria-label="Menu utama">
      <a href="<?= base_url() ?>"<?= $aktif('beranda') ?>>Beranda</a>
      <a href="<?= site_url('shop') ?>"<?= $aktif('shop') ?>>Katalog</a>
      <a href="<?= site_url('shop') ?>?dekat=1&amp;radius=10">Toko Terdekat</a>
      <a href="<?= site_url('track') ?>"<?= $aktif('track') ?>>Lacak Pesanan</a>
    </nav>

    <!-- Pencarian lewat GET ke katalog. Katalog sudah membaca ?q=, jadi
         tidak ada endpoint baru yang perlu dibuat. -->
    <form class="nt-cari" action="<?= site_url('shop') ?>" method="get" role="search">
      <label>
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-4-4"></path></svg>
        <input type="search" name="q" aria-label="Cari produk"
               placeholder="Cari keripik, tas anyaman, batik…"
               value="<?= html_escape((string) $this->input->get('q', TRUE)) ?>">
      </label>
    </form>

    <a class="nt-keranjang cart-link" href="<?= site_url('cart') ?>"
       aria-label="Keranjang<?= $jml_cart ? ', ' . $jml_cart . ' barang' : ', kosong' ?>">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2.4 11h11l2-8H6.2"></path><circle cx="9" cy="19" r="1.5"></circle><circle cx="17" cy="19" r="1.5"></circle></svg>
      <!-- Kelas cart-badge dipertahankan: shop.js & produk.js memperbarui
           angkanya lewat kelas ini setelah produk ditambahkan. -->
      <span class="cart-badge"<?= $jml_cart ? '' : ' hidden' ?>><?= $jml_cart ?></span>
    </a>

    <button type="button" class="nt-burger" id="ntBurger"
            aria-controls="ntMenu" aria-expanded="false" aria-label="Buka menu">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
    </button>

  </div>
</header>

<script>
(function () {
  'use strict';

  /* Menu HP. Tanpa Bootstrap collapse - cukup satu kelas, dan aria-expanded
     ikut diperbarui supaya pembaca layar tahu menunya terbuka. */
  var burger = document.getElementById('ntBurger');
  var menu   = document.getElementById('ntMenu');

  if (burger && menu) {
    burger.addEventListener('click', function () {
      var buka = menu.classList.toggle('is-buka');
      burger.setAttribute('aria-expanded', buka ? 'true' : 'false');
      burger.setAttribute('aria-label', buka ? 'Tutup menu' : 'Buka menu');
    });
  }

  /* Tombol lokasi. Modal wilayah (v_location_modal) hanya dimuat di
     beranda & katalog. Di halaman lain location.js tidak ada, jadi tombol
     ini akan diam saja kalau tidak diberi cadangan - diarahkan ke katalog,
     tempat modalnya tersedia. Diperiksa saat diklik, bukan saat dimuat,
     karena location.js dimuat di bagian bawah halaman. */
  document.querySelectorAll('.nt-lokasi').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!document.querySelector('script[src*="location.js"]')) {
        window.location = btn.dataset.cadangan;
      }
    });
  });
})();
</script>
