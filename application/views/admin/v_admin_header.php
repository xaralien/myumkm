<!-- application/views/admin/v_admin_header.php -->
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel &middot; <?= html_escape($me['name']) ?></title>
  <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/order.css') ?>" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
  <!-- Tema sesudah order.css, sama seperti halaman publik. -->
  <link rel="stylesheet" href="<?= base_url('assets/css/tema-nusantara.css') ?>">
</head>

<body class="panel-body">

  <nav class="panel-nav">
    <div class="container">
      <a href="<?= site_url($me['role'] === 'admin' ? 'admin/stores' : 'seller') ?>" class="panel-brand">
        <?= $me['role'] === 'admin' ? 'Admin' : 'Toko' ?>
      </a>

      <div class="panel-links">
        <?php if ($me['role'] === 'admin'): ?>
          <a href="<?= site_url('admin/stores') ?>">Daftar Toko</a>
        <?php else: ?>
          <a href="<?= site_url('seller') ?>">Produk</a>
          <a href="<?= site_url('seller/orders') ?>">Pesanan</a>
          <a href="<?= site_url('seller/profile') ?>">Profil Toko</a>
        <?php endif; ?>
        <a href="<?= site_url('shop') ?>">Lihat katalog</a>
        <a href="<?= site_url('auth/logout') ?>" class="panel-keluar">Keluar</a>
      </div>
    </div>
  </nav>

  <div class="container panel-isi">
    <?php if ($this->session->flashdata('sukses')): ?>
      <div class="alert-box alert-ok mb-4"><?= html_escape($this->session->flashdata('sukses')) ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert-box alert-error mb-4"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>