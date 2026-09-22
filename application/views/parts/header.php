<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="shortcut icon" href="<?= base_url('favicon.png') ?>">
  <meta name="description" content="<?= html_escape($this->config->item('slogan') ?: '') ?>">
  <title><?= html_escape($this->config->item('nama_brand') ?: 'Nama Brand') ?></title>

  <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/tiny-slider.css') ?>" rel="stylesheet">
  <link href="<?= base_url('assets/css/style.css') ?>" rel="stylesheet">

  <!-- order.css WAJIB ada di sini. Sebelumnya hanya dimuat di panel admin,
       jadi keranjang, checkout, filter katalog, bilah lokasi, dan widget
       chat tampil tanpa gaya sama sekali di halaman pembeli. -->
  <link href="<?= base_url('assets/css/order.css') ?>" rel="stylesheet">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">

  <!-- Tema PALING AKHIR, sesudah order.css. Tema mendefinisikan ulang
       variabel --ord-* milik order.css; kalau urutannya terbalik,
       order.css menimpanya balik dan warnanya kembali hijau Furni. -->
  <link rel="stylesheet" href="<?= base_url('assets/css/tema-nusantara.css') ?>">
</head>

<body>