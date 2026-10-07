<!-- application/views/v_galat.php - halaman galat di dalam kerangka situs -->
<section class="gl-bagian">
  <div class="container">
    <div class="gl-kotak">

      <p class="gl-kode" aria-hidden="true"><?= html_escape($kode) ?></p>
      <span class="visually-hidden">Galat <?= html_escape($kode) ?></span>

      <h1 class="gl-judul"><?= html_escape($judul) ?></h1>
      <p class="gl-pesan"><?= html_escape($pesan) ?></p>

      <!-- Pencarian ditaruh di halaman ini, bukan hanya tautan: orang yang
           sampai ke 404 biasanya sedang MENCARI sesuatu, dan memberinya
           kolom cari lebih menolong daripada melemparnya ke beranda. -->
      <form class="gl-cari" action="<?= site_url('shop') ?>" method="get" role="search">
        <label class="visually-hidden" for="glCari">Cari produk</label>
        <input type="search" id="glCari" name="q" placeholder="Cari produk, misalnya keripik atau batik..."
               autocomplete="off">
        <button type="submit" aria-label="Cari">
          <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-4-4"></path></svg>
        </button>
      </form>

      <div class="gl-aksi">
        <a href="<?= site_url() ?>" class="nt-tombol nt-tombol-kunyit">Kembali ke beranda</a>
        <a href="<?= site_url('shop') ?>" class="nt-tombol nt-tombol-garis">Lihat katalog</a>
      </div>

      <p class="gl-tautan">
        <a href="<?= site_url('lacak') ?>">Lacak pesanan</a> &middot;
        <a href="<?= site_url('akun/pesanan') ?>">Pesanan saya</a> &middot;
        <a href="<?= site_url('akun/buka_toko') ?>">Buka toko</a>
      </p>

    </div>
  </div>
</section>
