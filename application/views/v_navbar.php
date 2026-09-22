<!-- application/views/v_navbar.php -->
<?php
/* Lewat get_instance(), BUKAN $this. Di dalam view, $this adalah salinan
     properti controller yang dibuat CodeIgniter SEKALI saat view mulai
     dimuat - library yang dimuat di tengah view tidak ikut tersalin. */
$CI = &get_instance();
$CI->load->library(array('cart_lib', 'location_lib', 'auth_lib'));

// form_open() di tombol Keluar butuh helper form - tidak semua
// controller publik memuatnya (Home misalnya), jadi dimuat di sini.
$CI->load->helper('form');

$jml_cart = (int) $CI->cart_lib->count();
$brand    = $this->config->item('nama_brand') ?: 'Nama Brand';
$slogan   = $this->config->item('slogan') ?: 'Belanja langsung dari pelaku UMKM di kotamu';

$lokasi_label = $CI->location_lib->has() ? $CI->location_lib->label() : NULL;

// Akun & toko. Auth_lib menyimpan hasilnya per permintaan, jadi
// controller yang memanggilnya lagi tidak menambah query.
$akun = $CI->auth_lib->row();
$toko = $akun ? $CI->auth_lib->store() : NULL;

$seg = $this->uri->segment(1);
$aktif = function ($nama) use ($seg) {
  $cocok = ($nama === 'beranda') ? ($seg === NULL || $seg === '' || $seg === 'home') : ($seg === $nama);
  return $cocok ? ' aria-current="page"' : '';
};

$inisial = function ($nama) {
  return strtoupper(function_exists('mb_substr') ? mb_substr(trim($nama), 0, 1) : substr(trim($nama), 0, 1));
};

// Satu baris alamat: jalan + kecamatan. Dipotong CSS kalau kepanjangan.
$alamat = function ($row) {
  $bagian = array_filter(array(
    isset($row['address']) ? trim((string) $row['address']) : '',
    isset($row['district_name']) ? $row['district_name'] : '',
  ));
  return implode(', ', $bagian);
};

$pensil = '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3z"></path><path d="M13.5 6.5l3 3"></path></svg>';
?>

<div class="nt-strip">
  <div class="nt-wrap nt-strip-in">
    <span class="nt-strip-teks"><?= html_escape($slogan) ?></span>
    <button type="button" class="nt-lokasi" data-open-location data-cadangan="<?= site_url('shop') ?>">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"></path>
        <circle cx="12" cy="10" r="2.5"></circle>
      </svg>
      Kirim ke: <strong><?= $lokasi_label ? html_escape($lokasi_label) : 'Pilih lokasi' ?></strong>
    </button>
  </div>
</div>

<header class="nt-nav">
  <div class="nt-wrap nt-nav-in">

    <a class="nt-logo" href="<?= base_url() ?>">
      <svg viewBox="0 0 40 40" width="38" height="38" aria-hidden="true">
        <rect width="40" height="40" rx="10" fill="#A8441F"></rect>
        <g fill="none" stroke="#F3D08A" stroke-width="1.8">
          <ellipse cx="20" cy="11" rx="5" ry="8"></ellipse>
          <ellipse cx="20" cy="29" rx="5" ry="8"></ellipse>
          <ellipse cx="11" cy="20" rx="8" ry="5"></ellipse>
          <ellipse cx="29" cy="20" rx="8" ry="5"></ellipse>
        </g>
      </svg>
      <span><?= html_escape($brand) ?></span>
    </a>

    <nav class="nt-menu" id="ntMenu" aria-label="Menu utama">
      <a href="<?= base_url() ?>" <?= $aktif('beranda') ?>>Beranda</a>
      <a href="<?= site_url('shop') ?>" <?= $aktif('shop') ?>>Katalog</a>
      <a href="<?= site_url('shop') ?>?dekat=1&amp;radius=10">Toko Terdekat</a>
      <a href="<?= site_url('track') ?>" <?= $aktif('track') ?>>Lacak Pesanan</a>
    </nav>

    <form class="nt-cari" action="<?= site_url('shop') ?>" method="get" role="search">
      <label>
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
          <circle cx="11" cy="11" r="7"></circle>
          <path d="M20 20l-4-4"></path>
        </svg>
        <input type="search" name="q" aria-label="Cari produk"
          placeholder="Cari keripik, tas anyaman, batik…"
          value="<?= html_escape((string) $this->input->get('q', TRUE)) ?>">
      </label>
    </form>

    <a class="nt-keranjang cart-link" href="<?= site_url('cart') ?>"
      aria-label="Keranjang<?= $jml_cart ? ', ' . $jml_cart . ' barang' : ', kosong' ?>">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M3 4h2l2.4 11h11l2-8H6.2"></path>
        <circle cx="9" cy="19" r="1.5"></circle>
        <circle cx="17" cy="19" r="1.5"></circle>
      </svg>
      <span class="cart-badge" <?= $jml_cart ? '' : ' hidden' ?>><?= $jml_cart ?></span>
    </a>

    <!-- ======================= AKUN ======================= -->
    <div class="nt-akun">
      <button type="button" class="nt-akun-tombol" id="ntAkunTombol"
        aria-expanded="false" aria-controls="ntAkunPanel"
        aria-label="<?= $akun ? 'Menu akun ' . html_escape($akun['name']) : 'Masuk atau daftar' ?>">
        <?php if ($akun && $akun['avatar']): ?>
          <img src="<?= base_url('upload/avatar/' . $akun['avatar']) ?>" alt="">
        <?php elseif ($akun): ?>
          <span aria-hidden="true"><?= html_escape($inisial($akun['name'])) ?></span>
        <?php else: ?>
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="8" r="4"></circle>
            <path d="M4 21a8 8 0 0 1 16 0"></path>
          </svg>
        <?php endif; ?>
      </button>

      <!-- Panel biasa, bukan role="menu": isinya campuran teks, tautan,
           dan form keluar. role="menu" menjanjikan navigasi tombol panah
           yang tidak ada di sini, dan itu justru menyesatkan pembaca layar. -->
      <div class="nt-akun-panel" id="ntAkunPanel" hidden>

        <?php if ($akun): ?>

          <!-- ---------- bar profil ---------- -->
          <div class="nt-akun-bar">
            <span class="nt-akun-foto">
              <?php if ($akun['avatar']): ?>
                <img src="<?= base_url('upload/avatar/' . $akun['avatar']) ?>" alt="">
              <?php else: ?>
                <?= html_escape($inisial($akun['name'])) ?>
              <?php endif; ?>
            </span>
            <span class="nt-akun-teks">
              <strong><?= html_escape($akun['name']) ?></strong>
              <em><?= $alamat($akun) !== '' ? html_escape($alamat($akun)) : 'Alamat belum diisi' ?></em>
            </span>
            <a href="<?= site_url('akun/profil') ?>" class="nt-akun-ubah" aria-label="Ubah profil"><?= $pensil ?></a>
          </div>

          <!-- ---------- bar toko ---------- -->
          <?php if ($toko): ?>
            <div class="nt-akun-bar nt-akun-bar-toko">
              <span class="nt-akun-foto nt-akun-foto-toko">
                <?php if (! empty($toko['avatar'])): ?>
                  <img src="<?= base_url('upload/avatar/' . $toko['avatar']) ?>" alt="">
                <?php else: ?>
                  <?= html_escape($inisial($toko['name'])) ?>
                <?php endif; ?>
              </span>
              <span class="nt-akun-teks">
                <strong><?= html_escape($toko['name']) ?></strong>
                <?php if (! (int) $toko['is_active']): ?>
                  <!-- Toko baru belum tampil di katalog - pemiliknya perlu
                       tahu itu, bukan menebak kenapa produknya tidak muncul. -->
                  <em class="nt-akun-tunggu">Menunggu persetujuan admin</em>
                <?php else: ?>
                  <em><?= $alamat($toko) !== '' ? html_escape($alamat($toko)) : 'Alamat toko belum diisi' ?></em>
                <?php endif; ?>
              </span>
              <a href="<?= site_url('seller/profile') ?>" class="nt-akun-ubah" aria-label="Ubah profil toko"><?= $pensil ?></a>
            </div>
          <?php else: ?>
            <a href="<?= site_url('akun/buka_toko') ?>" class="nt-akun-bar nt-akun-buka">
              <span class="nt-akun-foto nt-akun-foto-kosong" aria-hidden="true">+</span>
              <span class="nt-akun-teks">
                <strong>Buka toko</strong>
                <em>Jual produk UMKM-mu di sini</em>
              </span>
            </a>
          <?php endif; ?>

          <!-- ---------- menu ---------- -->
          <nav class="nt-akun-menu" aria-label="Menu akun">
            <a href="<?= site_url('akun/pesanan') ?>">Pesanan saya</a>
            <!-- <a href="<?= site_url('track') ?>">Lacak pesanan</a> -->

            <?php if ($toko): ?>
              <hr>
              <span class="nt-akun-sub">Toko</span>
              <a href="<?= site_url('seller') ?>">Produk</a>
              <a href="<?= site_url('seller/orders') ?>">Pesanan masuk</a>
              <a href="<?= site_url('seller/profile') ?>">Pengaturan toko</a>
            <?php endif; ?>

            <?php if ($akun['role'] === 'admin'): ?>
              <span class="nt-akun-sub">Admin</span>
              <a href="<?= site_url('admin/stores') ?>">Kelola toko</a>
            <?php endif; ?>
          </nav>

          <!-- POST, bukan tautan. Tautan GET untuk keluar bisa dipicu dari
               situs lain (atau pemindai tautan) tanpa sepengetahuan pengguna. -->
          <?= form_open('auth/logout', array('class' => 'nt-akun-keluar')) ?>
          <button type="submit">Keluar</button>
          <?= form_close() ?>

        <?php else: ?>

          <div class="nt-akun-tamu">
            <p>Masuk untuk menyimpan riwayat belanja dan membuka toko.</p>
            <a href="<?= site_url('auth/login') . '?next=' . rawurlencode(uri_string()) ?>" class="nt-tombol">Masuk</a>
            <a href="<?= site_url('auth/register') . '?next=' . rawurlencode(uri_string()) ?>" class="nt-tombol-garis">Daftar</a>
          </div>
          <nav class="nt-akun-menu" aria-label="Menu tamu">
            <a href="<?= site_url('track') ?>">Lacak pesanan</a>
          </nav>

        <?php endif; ?>
      </div>
    </div>

    <button type="button" class="nt-burger" id="ntBurger"
      aria-controls="ntMenu" aria-expanded="false" aria-label="Buka menu">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
        <path d="M4 7h16M4 12h16M4 17h16"></path>
      </svg>
    </button>

  </div>
</header>

<?php
/* Pesan sukses & info untuk semua halaman publik. Pesan ERROR tidak di
     sini - halaman yang bersangkutan sudah menampilkannya di dekat
     formulirnya, dan flashdata yang sama terbaca dua kali dalam satu
     permintaan akan muncul ganda. */
$flash_ok   = $this->session->flashdata('sukses');
$flash_info = $this->session->flashdata('info');
?>
<?php if ($flash_ok || $flash_info): ?>
  <div class="nt-wrap">
    <div class="nt-kabar <?= $flash_ok ? 'is-ok' : '' ?>" role="status">
      <?= html_escape($flash_ok ?: $flash_info) ?>
    </div>
  </div>
<?php endif; ?>

<script>
  (function() {
    'use strict';

    /* ---- menu HP ---- */
    var burger = document.getElementById('ntBurger');
    var menu = document.getElementById('ntMenu');
    if (burger && menu) {
      burger.addEventListener('click', function() {
        var buka = menu.classList.toggle('is-buka');
        burger.setAttribute('aria-expanded', buka ? 'true' : 'false');
        burger.setAttribute('aria-label', buka ? 'Tutup menu' : 'Buka menu');
      });
    }

    /* ---- panel akun ---- */
    var tombol = document.getElementById('ntAkunTombol');
    var panel = document.getElementById('ntAkunPanel');

    function tutup(kembalikanFokus) {
      if (panel.hidden) {
        return;
      }
      panel.hidden = true;
      tombol.setAttribute('aria-expanded', 'false');
      if (kembalikanFokus) {
        tombol.focus();
      }
    }

    if (tombol && panel) {
      tombol.addEventListener('click', function(e) {
        e.stopPropagation();
        var buka = panel.hidden;
        panel.hidden = !buka;
        tombol.setAttribute('aria-expanded', buka ? 'true' : 'false');
      });

      // Klik di luar panel menutupnya.
      document.addEventListener('click', function(e) {
        if (!panel.contains(e.target) && e.target !== tombol) {
          tutup(false);
        }
      });

      // Escape menutup dan mengembalikan fokus ke tombol - tanpa itu,
      // pengguna keyboard kehilangan posisinya di halaman.
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          tutup(true);
        }
      });
    }

    /* ---- tombol lokasi: cadangan kalau modal wilayah tidak dimuat ---- */
    document.querySelectorAll('.nt-lokasi').forEach(function(btn) {
      btn.addEventListener('click', function() {
        if (!document.querySelector('script[src*="location.js"]')) {
          window.location = btn.dataset.cadangan;
        }
      });
    });
  })();
</script>