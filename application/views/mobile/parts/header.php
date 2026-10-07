<?php
/* =============================================================================
   Kepala halaman versi mobile.
   ========================================================================== */
$CI =& get_instance();
$CI->load->library(array('cart_lib', 'location_lib', 'auth_lib', 'tampilan_lib'));
$CI->load->helper('form');

$brand    = $this->config->item('nama_brand') ?: 'Nama Brand';
$jml_cart = (int) $CI->cart_lib->count();
$akun     = $CI->auth_lib->row();
$lokasi   = $CI->location_lib->has() ? $CI->location_lib->label() : NULL;
$seg      = $this->uri->segment(1);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <!-- viewport-fit=cover: halaman digambar sampai tepi layar, lalu isinya
       dijauhkan dari poni & bilah bawah lewat env(safe-area-inset-*) di CSS. -->
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#22466C">
  <link rel="shortcut icon" href="<?= base_url('favicon.png') ?>">
  <title><?= html_escape($brand) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">

  <!-- Hanya token warna & komponen pesanan dari tema desktop yang dipakai;
       tata letaknya punya berkas sendiri. -->
  <!-- Bootstrap & style.css ikut dimuat karena halaman yang BELUM punya
       versi HP memakai .container, .row, dan .btn dari keduanya. Tanpa itu,
       halaman seperti keranjang menempel ke tepi layar dan tombolnya
       menciut. Urutannya tetap: tema lalu mobile.css di paling akhir, jadi
       keduanya tetap menang. -->
  <link href="<?= aset('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= aset('assets/css/style.css') ?>" rel="stylesheet">
  <link href="<?= aset('assets/css/order.css') ?>" rel="stylesheet">
  <link rel="stylesheet" href="<?= aset('assets/css/tema-nusantara.css') ?>">
  <link rel="stylesheet" href="<?= aset('assets/css/mobile.css') ?>">
</head>

<?php
  /* Halaman percakapan tampil penuh: tanpa bilah atas situs dan tanpa tab
     bar. Keduanya memakan ruang yang justru paling dibutuhkan di sini, dan
     membuat tinggi isinya naik-turun saat papan ketik muncul. */
  $halaman_chat = ($seg === 'chat' && $this->uri->segment(2));
?>
<body class="mb <?= $halaman_chat ? 'is-chat' : '' ?>">

  <?php if (! $halaman_chat): ?>
  <header class="mb-atas">
    <a href="<?= base_url() ?>" class="mb-logo" aria-label="<?= html_escape($brand) ?>">
      <img src="<?= aset('assets/images/sapa_umkm_icon.svg') ?>" alt="" width="103" height="52">
    </a>

    <button type="button" class="mb-lokasi" data-gps>
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
      <span><?= $lokasi ? html_escape($lokasi) : 'Pilih lokasi' ?></span>
      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"></path></svg>
    </button>

    <a href="<?= site_url('shop') ?>" class="mb-ikon" aria-label="Cari produk">
      <svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-4-4"></path></svg>
    </a>
  </header>
  <?php endif; ?>

  <?php if ($this->session->flashdata('sukses') || $this->session->flashdata('info')): ?>
    <div class="mb-kabar is-ok" role="status">
      <?= html_escape($this->session->flashdata('sukses') ?: $this->session->flashdata('info')) ?>
    </div>
  <?php endif; ?>

  <main class="mb-isi">
