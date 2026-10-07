  </main>

  <?php
    /* Dihitung ulang di sini, bukan diwarisi dari header.php: tiap view
       punya ruang variabelnya sendiri. */
    $CI =& get_instance();
    $CI->load->library(array('cart_lib', 'auth_lib', 'tampilan_lib'));
    $CI->load->helper('form');

    $jml_cart = (int) $CI->cart_lib->count();
    $akun     = $CI->auth_lib->row();
    $toko     = $akun ? $CI->auth_lib->store() : NULL;
    $seg      = $this->uri->segment(1);

    $inisial = function ($n) { return strtoupper(mb_substr(trim((string) $n), 0, 1)); };

    $alamat = function ($row) {
        $bagian = array_filter(array(
            isset($row['address']) ? trim((string) $row['address']) : '',
            isset($row['district_name']) ? $row['district_name'] : '',
        ));
        return implode(', ', $bagian);
    };

    /* Tab bar di BAWAH: saat HP dipegang satu tangan, bagian atas layar
       sulit dijangkau jempol.

       Tab keempat berubah setelah masuk - pengguna berakun punya riwayat
       pesanan sendiri, jadi tidak perlu lagi mengetik nomor pesanan di
       halaman lacak. */
    $tab = array(
      array('', 'Beranda', 'M4 11l8-7 8 7v9H4z|M10 20v-6h4v6', array('', 'home')),
      array('shop', 'Katalog', 'M4 7h16l-1.5 13h-13L4 7z|M9 7V5a3 3 0 0 1 6 0v2', array('shop', 'produk')),
      array('cart', 'Keranjang', 'M3 4h2l2.4 11h11l2-8H6.2', array('cart', 'checkout')),
      $akun
        ? array('akun/pesanan', 'Pesanan saya', 'M5 4h14v16l-7-4-7 4z', array('akun', 'track'))
        : array('track', 'Lacak', 'M5 4h14v16l-7-4-7 4z', array('track')),
    );
  ?>
  <?php if (! ($seg === 'chat' && $this->uri->segment(2))): ?>
  <nav class="mb-tab" aria-label="Menu utama">
    <?php foreach ($tab as $t): ?>
      <?php $aktif = in_array((string) $seg, $t[3], TRUE) && ! ($t[0] === 'akun/pesanan' && $seg === 'track'); ?>
      <a href="<?= site_url($t[0]) ?>" class="mb-tab-item <?= $aktif ? 'is-aktif' : '' ?>"
         <?= $aktif ? 'aria-current="page"' : '' ?>>
        <span class="mb-tab-ikon">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <?php foreach (explode('|', $t[2]) as $d): ?><path d="<?= $d ?>"></path><?php endforeach; ?>
          </svg>
          <?php if ($t[0] === 'cart' && $jml_cart > 0): ?>
            <span class="mb-tab-lencana cart-badge"><?= $jml_cart ?></span>
          <?php endif; ?>
        </span>
        <?= html_escape($t[1]) ?>
      </a>
    <?php endforeach; ?>

    <?php if ($akun): ?>
      <!-- Tombol, bukan tautan: membuka lembar akun di tempat. Isinya sama
           dengan menu akun di tampilan desktop - profil, toko, lalu keluar. -->
      <button type="button" class="mb-tab-item" id="mbAkunTombol"
              aria-controls="mbAkunSheet" aria-expanded="false">
        <span class="mb-tab-ikon">
          <?php if ($akun['avatar']): ?>
            <img src="<?= base_url('upload/avatar/' . $akun['avatar']) ?>" alt="" class="mb-tab-avatar">
          <?php else: ?>
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
          <?php endif; ?>
          <!-- Titik merah tanpa angka: di ikon sekecil ini angka tidak
               terbaca, dan yang perlu diketahui cuma "ada yang menunggu".
               Rinciannya ada di dalam lembar akun. -->
          <span class="mb-tab-titik" data-akun-notif="titik" hidden></span>
        </span>
        Akun
      </button>
    <?php else: ?>
      <!-- Tamu juga perlu jalan masuk ke sakelar tampilan, jadi tab ini
           membuka lembar yang sama - berisi Masuk, Daftar, dan sakelarnya. -->
      <button type="button" class="mb-tab-item" id="mbAkunTombol"
              aria-controls="mbAkunSheet" aria-expanded="false">
        <span class="mb-tab-ikon">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
        </span>
        Masuk
      </button>
    <?php endif; ?>
  </nav>
  <?php endif; ?>

  <div class="mb-sheet-scrim" id="mbAkunScrim" hidden></div>

  <?php if (! $akun): ?>
    <div class="mb-sheet-scrim" id="mbAkunScrim" hidden></div>
    <div class="mb-sheet" id="mbAkunSheet" role="dialog" aria-label="Menu akun" hidden>
      <div class="mb-sheet-tamu">
        <p>Masuk untuk menyimpan riwayat belanja dan membuka toko.</p>
        <a href="<?= site_url('auth/login') . '?next=' . rawurlencode(uri_string()) ?>" class="mb-sheet-btn is-utama">Masuk</a>
        <a href="<?= site_url('auth/register') . '?next=' . rawurlencode(uri_string()) ?>" class="mb-sheet-btn">Daftar</a>
      </div>
      <nav class="mb-sheet-menu" aria-label="Menu tamu">
        <a href="<?= site_url('track') ?>">Lacak pesanan</a>
      </nav>
      <?php $this->load->view('parts/tampilan_toggle'); ?>
    </div>
  <?php endif; ?>

  <?php if ($akun): ?>
    <div class="mb-sheet" id="mbAkunSheet" role="dialog" aria-label="Menu akun" hidden>

      <div class="mb-sheet-bar">
        <span class="mb-sheet-foto">
          <?php if ($akun['avatar']): ?>
            <img src="<?= base_url('upload/avatar/' . $akun['avatar']) ?>" alt="">
          <?php else: ?><?= html_escape($inisial($akun['name'])) ?><?php endif; ?>
        </span>
        <span class="mb-sheet-teks">
          <strong><?= html_escape($akun['name']) ?></strong>
          <em><?= $alamat($akun) !== '' ? html_escape($alamat($akun)) : 'Alamat belum diisi' ?></em>
        </span>
        <a href="<?= site_url('akun/profil') ?>" class="mb-sheet-ubah" aria-label="Ubah profil">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3z"></path><path d="M13.5 6.5l3 3"></path></svg>
        </a>
      </div>

      <?php if ($toko): ?>
        <div class="mb-sheet-bar is-toko">
          <span class="mb-sheet-foto is-toko">
            <?php if (! empty($toko['avatar'])): ?>
              <img src="<?= base_url('upload/avatar/' . $toko['avatar']) ?>" alt="">
            <?php else: ?><?= html_escape($inisial($toko['name'])) ?><?php endif; ?>
          </span>
          <span class="mb-sheet-teks">
            <strong><?= html_escape($toko['name']) ?></strong>
            <?php if (! (int) $toko['is_active']): ?>
              <em class="is-tunggu">Menunggu persetujuan admin</em>
            <?php else: ?>
              <em><?= $alamat($toko) !== '' ? html_escape($alamat($toko)) : 'Alamat toko belum diisi' ?></em>
            <?php endif; ?>
          </span>
          <a href="<?= site_url('seller/profile') ?>" class="mb-sheet-ubah" aria-label="Ubah profil toko">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-3-3L5 17v3z"></path><path d="M13.5 6.5l3 3"></path></svg>
          </a>
        </div>
      <?php else: ?>
        <a href="<?= site_url('akun/buka_toko') ?>" class="mb-sheet-bar is-toko">
          <span class="mb-sheet-foto is-kosong" aria-hidden="true">+</span>
          <span class="mb-sheet-teks">
            <strong>Buka toko</strong>
            <em>Jual produk UMKM-mu di sini</em>
          </span>
        </a>
      <?php endif; ?>

      <nav class="mb-sheet-menu" aria-label="Menu akun">
        <a href="<?= site_url('akun/alamat') ?>">Alamat saya</a>
        <a href="<?= site_url('akun/pesanan') ?>">
          Pesanan saya
          <span class="mb-sheet-angka" data-akun-notif="pesanan_saya" hidden></span>
        </a>
        <a href="<?= site_url('akun/pesan') ?>">
          Pesan
          <span class="mb-sheet-angka is-merah" data-akun-notif="pesan_saya" hidden></span>
        </a>
        <a href="<?= site_url('akun/profil') ?>">Ubah profil</a>
        <a href="<?= site_url('track') ?>">Lacak pesanan</a>

        <?php if ($toko): ?>
          <span class="mb-sheet-sub">Toko</span>
          <a href="<?= site_url('seller') ?>">Produk</a>
          <a href="<?= site_url('seller/orders') ?>">
            Pesanan masuk
            <span class="mb-sheet-angka is-merah" data-akun-notif="toko_pesanan" hidden></span>
          </a>
          <a href="<?= site_url('seller/pesan') ?>">
            Pesan pembeli
            <span class="mb-sheet-angka is-merah" data-akun-notif="toko_pesan" hidden></span>
          </a>
          <a href="<?= site_url('seller/profile') ?>">Pengaturan toko</a>
        <?php endif; ?>

        <?php if ($akun['role'] === 'admin'): ?>
          <span class="mb-sheet-sub">Admin</span>
          <a href="<?= site_url('admin') ?>">Panel admin</a>
        <?php endif; ?>
      </nav>

      <?php $this->load->view('parts/tampilan_toggle'); ?>

      <!-- POST, bukan tautan: tautan keluar bisa terpicu pratinjau tautan
           di aplikasi chat tanpa disengaja pemiliknya. -->
      <?php $this->load->view('parts/tampilan_toggle'); ?>

      <?= form_open('auth/logout', array('class' => 'mb-sheet-keluar')) ?>
        <button type="submit">Keluar</button>
      <?= form_close() ?>
    </div>
  <?php endif; ?>

  <?php $this->load->view('parts/chat_inbox'); ?>

  <?php if ($akun): ?>
    <script>
      window.AKUN_NOTIF = { url: '<?= site_url('akun/notif') ?>' };
    </script>
    <script src="<?= aset('assets/js/akun-notif.js') ?>"></script>
  <?php endif; ?>

  <script>
    window.TAMPILAN = {
      mode:   <?= json_encode($CI->tampilan_lib->mode()) ?>,
      // Pilihan manual tidak boleh dikoreksi otomatis.
      manual: <?= $CI->tampilan_lib->manual() ? 'true' : 'false' ?>
    };
  </script>
  <script src="<?= aset('assets/js/tampilan.js') ?>"></script>
  <script src="<?= aset('assets/js/bootstrap.bundle.min.js') ?>"></script>
  <script>
    // Modal wilayah hanya dimuat di beranda & katalog; di halaman lain tombol
    // lokasi mengarah ke katalog.
    /* Lembar akun. Dibuka dari tab bar, ditutup dengan menyentuh latar
       gelap atau menekan Escape. */
    (function () {
      var tombol = document.getElementById('mbAkunTombol');
      var sheet  = document.getElementById('mbAkunSheet');
      var scrim  = document.getElementById('mbAkunScrim');
      if (!tombol || !sheet) { return; }

      function buka(ya) {
        sheet.hidden = !ya;
        if (scrim) { scrim.hidden = !ya; }
        tombol.setAttribute('aria-expanded', ya ? 'true' : 'false');
        tombol.classList.toggle('is-aktif', ya);
        /* Halaman di belakang dikunci saat lembar terbuka - kalau tidak,
           menggeser lembar ikut menggulirkan halaman di baliknya. */
        document.body.style.overflow = ya ? 'hidden' : '';
      }

      tombol.addEventListener('click', function () { buka(sheet.hidden); });
      if (scrim) { scrim.addEventListener('click', function () { buka(false); }); }
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !sheet.hidden) { buka(false); }
      });
    })();

    /* Tombol lokasi ditangani gps.js lewat atribut data-gps. Kode lama di
       sini mengarahkan halaman ke b.dataset.cadangan - atribut yang sudah
       tidak ada sejak pemilih wilayah diganti GPS, sehingga nilainya
       undefined dan browser pergi ke /undefined. */
  </script>
<!-- Tombol lokasi (data-gps) ada di bilah atas SEMUA halaman, jadi skripnya
     dimuat di kerangka - bukan per halaman. -->
<script>
  window.GPS = window.GPS || { simpan: '<?= site_url('location/titik') ?>' };
  window.CSRF = window.CSRF || {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= aset('assets/js/gps.js') ?>"></script>

</body>

</html>
