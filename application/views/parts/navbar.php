<!-- application/views/v_navbar.php -->
<?php
/* Dimuat di sini, bukan diandalkan sudah dimuat controller. Loader CI
     mengabaikan pemanggilan kedua, jadi ini aman - dan tanpa baris ini
     halaman yang controllernya tidak memuat cart_lib (login, lacak pesanan)
     akan fatal error di baris badge di bawah. */
$this->load->library('cart_lib');
$jml_cart = $this->cart_lib->count();
?>

<!-- Start Header/Navigation -->
<nav class="custom-navbar navbar navbar-expand-lg navbar-dark bg-dark" aria-label="Furni navigation bar">
  <div class="container">

    <a class="navbar-brand" href="<?= base_url() ?>">MyFlorist<span>.</span></a>

    <!-- Keranjang + tombol menu.
         Blok ini SENGAJA di luar .navbar-collapse supaya keranjang tetap
         terlihat di mobile tanpa harus membuka menu dulu. Di desktop,
         CSS order memindahkannya ke ujung kanan. -->
    <div class="navbar-cart-wrap">
      <a class="cart-link" href="<?= site_url('cart') ?>"
        aria-label="Keranjang<?= $jml_cart ? ', ' . $jml_cart . ' item' : ', kosong' ?>">
        <img src="<?= base_url('assets/') ?>images/cart.svg" alt="" class="cart-img">
        <span class="cart-badge" <?= $jml_cart ? '' : ' hidden' ?>><?= $jml_cart ?></span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
        data-bs-target="#navbarsFurni" aria-controls="navbarsFurni"
        aria-expanded="false" aria-label="Buka menu">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>

    <div class="collapse navbar-collapse" id="navbarsFurni">
      <ul class="custom-navbar-nav navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= base_url() ?>">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('shop') ?>">Katalog</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('track') ?>">Lacak Pesanan</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= site_url('#about') ?>">Tentang</a></li>
        <!-- <li class="nav-item"><a class="nav-link" href="<?= site_url('contact') ?>">Kontak</a></li> -->
      </ul>

      <ul class="custom-navbar-cta navbar-nav mb-2 mb-lg-0 ms-lg-4">
        <li class="nav-item">
          <a class="nav-link" href="<?= site_url('auth/login') ?>" aria-label="Masuk">
            <img src="<?= base_url('assets/') ?>images/user.svg" alt="">
          </a>
        </li>
      </ul>
    </div>

  </div>
</nav>
<!-- End Header/Navigation -->