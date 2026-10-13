<!-- application/views/admin/v_admin_header.php -->
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <!-- viewport-fit=cover: halaman digambar sampai tepi layar, lalu isinya
       dijauhkan dari poni & bilah bawah lewat env(safe-area-inset-*) di CSS. -->
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Panel &middot; <?= html_escape($me['name']) ?></title>
  <link href="<?= aset('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link href="<?= aset('assets/css/style.css') ?>" rel="stylesheet">
  <link href="<?= aset('assets/css/order.css') ?>" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
  <!-- Tema sesudah order.css, sama seperti halaman publik. -->
  <link rel="stylesheet" href="<?= aset('assets/css/tema-nusantara.css') ?>">
  <link rel="stylesheet" href="<?= aset('assets/css/fitur.css') ?>">
</head>

<body class="panel-body">

  <nav class="panel-nav">
    <div class="container">
      <a href="<?= site_url($me['role'] === 'admin' ? 'admin' : ($me['role'] === 'pendamping' ? 'pendamping' : 'seller')) ?>" class="panel-brand">
        <?= $me['role'] === 'admin' ? 'Admin' : ($me['role'] === 'pendamping' ? 'Pendamping' : 'Toko') ?>
      </a>

      <?php
        /* Menu dikelompokkan supaya tetap muat di layar - tiap kelompok
           <details> (bekerja tanpa JavaScript, bisa dibuka dengan keyboard). */
        $aktif = function ($awalan) {
          foreach ((array) $awalan as $a) { if (strpos(uri_string(), $a) === 0) return ' is-aktif'; }
          return '';
        };
        $grup = function ($judul, array $isi, $awalan) use ($aktif) {
          echo '<details class="panel-grup' . $aktif($awalan) . '"><summary>' . $judul . '</summary><div class="panel-grup-isi">';
          foreach ($isi as $url => $label) { echo '<a href="' . site_url($url) . '">' . $label . '</a>'; }
          echo '</div></details>';
        };
      ?>
      <div class="panel-links">
        <?php if ($me['role'] === 'admin'): ?>
          <a href="<?= site_url('admin') ?>" class="<?= trim($aktif(array('admin/dashboard'))) ?>">Ringkasan</a>
          <?php $grup('Toko', array('admin/stores' => 'Daftar toko', 'admin/verifikasi' => 'Verifikasi legalitas',
                                    'admin/pendamping' => 'Pendamping', 'pendamping' => 'Panel pendamping', 'admin/peta' => 'Peta sebaran'),
                      array('admin/stores', 'admin/verifikasi', 'admin/pendamping', 'admin/peta')); ?>
          <?php $grup('Katalog', array('admin/categories' => 'Kategori', 'admin/moderasi' => 'Moderasi produk',
                                       'admin/moderasi/ulasan' => 'Moderasi ulasan', 'admin/banner' => 'Banner & promo'),
                      array('admin/categories', 'admin/moderasi', 'admin/banner')); ?>
          <?php $grup('Transaksi', array('admin/keuangan' => 'Keuangan & pencairan', 'admin/sengketa' => 'Pusat sengketa',
                                         'admin/voucher' => 'Voucher platform'),
                      array('admin/keuangan', 'admin/sengketa', 'admin/voucher')); ?>
          <a href="<?= site_url('admin/analitik') ?>" class="<?= trim($aktif('admin/analitik')) ?>">Analitik</a>
          <a href="<?= site_url('admin/akademi') ?>" class="<?= trim($aktif('admin/akademi')) ?>">Akademi</a>
          <?php $grup('Lainnya', array('admin/users' => 'Akun', 'admin/broadcast' => 'Broadcast', 'admin/pengaturan' => 'Pengaturan',
                                       'admin/pengaturan/notifikasi' => 'Notifikasi keluar', 'admin/pengaturan/audit' => 'Log audit'),
                      array('admin/users', 'admin/broadcast', 'admin/pengaturan')); ?>
        <?php elseif ($me['role'] === 'pendamping'): ?>
          <a href="<?= site_url('pendamping') ?>">Toko binaan</a>
        <?php else: ?>
          <a href="<?= site_url('seller') ?>" class="<?= uri_string() === 'seller' || strpos(uri_string(), 'seller/form') === 0 ? 'is-aktif' : '' ?>">Produk</a>
          <a href="<?= site_url('seller/orders') ?>" class="panel-link-notif<?= $aktif(array('seller/orders', 'seller/cetak')) ?>">
            Pesanan
            <!-- Lencana diisi panel-notif.js, berjalan di semua halaman
                 panel. Disembunyikan sampai ada isinya supaya tidak ada
                 titik kosong di menu. -->
            <span class="panel-lencana" data-notif="pesanan" hidden>0</span>
          </a>
          <a href="<?= site_url('seller/pesan') ?>" class="panel-link-notif<?= $aktif('seller/pesan') ?>">
            Pesan
            <span class="panel-lencana" data-notif="pesan" hidden>0</span>
          </a>
          <?php $grup('Keuangan', array('seller/pendapatan' => 'Pendapatan & pencairan', 'seller/pembukuan' => 'Pembukuan',
                                        'seller/laporan' => 'Laporan penjualan', 'seller/refund' => 'Refund', 'seller/sengketa' => 'Sengketa'),
                      array('seller/pendapatan', 'seller/pembukuan', 'seller/laporan', 'seller/refund', 'seller/sengketa')); ?>
          <?php $grup('Promosi', array('seller/voucher' => 'Voucher toko', 'seller/qr' => 'QR code toko', 'seller/template' => 'Balasan cepat chat'),
                      array('seller/voucher', 'seller/qr', 'seller/template')); ?>
          <?php $grup('Naik Kelas', array('seller/mulai' => 'Langkah awal', 'seller/naik_kelas' => 'Skor naik kelas',
                                          'seller/legalitas' => 'Legalitas', 'seller/akademi' => 'Akademi UMKM'),
                      array('seller/mulai', 'seller/naik_kelas', 'seller/legalitas', 'seller/akademi')); ?>
          <?php $grup('Toko', array('seller/profile' => 'Profil toko', 'seller/libur' => 'Mode libur', 'seller/ulasan' => 'Ulasan',
                                    'seller/massal' => 'Unggah massal', 'toko/' . (isset($store['slug']) ? $store['slug'] : '') => 'Lihat tokoku'),
                      array('seller/profile', 'seller/libur', 'seller/ulasan', 'seller/massal')); ?>
        <?php endif; ?>
        <a href="<?= site_url('shop') ?>">Katalog</a>
        <a href="<?= site_url('auth/logout') ?>" class="panel-keluar">Keluar</a>
      </div>
    </div>
  </nav>

  <div class="container panel-isi">
    <?php if ($this->session->flashdata('sukses')): ?>
      <div class="alert-box alert-ok mb-4"><?= html_escape($this->session->flashdata('sukses')) ?></div>
    <?php endif; ?>
    <?php
      /* Pengumuman admin yang belum dibaca penjual - tampil di semua halaman panel. */
      if ($me['role'] !== 'admin' && ! empty($store['id']) && $this->db->table_exists('broadcast_toko')):
        $umum = $this->db->select('b.id, b.judul, b.isi, b.created_at', FALSE)->from('broadcast_toko t')
          ->join('broadcast b', 'b.id = t.broadcast_id')->where('t.store_id', (int) $store['id'])
          ->where('t.dibaca_at IS NULL', NULL, FALSE)->order_by('b.id', 'DESC')->limit(2)->get()->result_array();
        foreach ($umum as $u): ?>
          <div class="pengumuman mb-3">
            <div><strong>📢 <?= html_escape($u['judul']) ?></strong><p class="mb-0"><?= nl2br(html_escape($u['isi'])) ?></p></div>
            <?= form_open('seller/pengumuman/baca/' . (int) $u['id']) ?>
              <input type="hidden" name="balik" value="<?= html_escape(uri_string()) ?>">
              <button class="btn btn-sm btn-black-hover-outline">Sudah dibaca</button>
            <?= form_close() ?>
          </div>
    <?php endforeach; endif; ?>
    <?php if ($this->session->flashdata('info')): ?>
      <div class="alert-box alert-warn mb-4"><?= html_escape($this->session->flashdata('info')) ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert-box alert-error mb-4"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>