<!-- application/views/seller/v_products.php -->
<?php
/* Membuat URL yang MEMPERTAHANKAN parameter lain. Tanpa ini, mengklik
     sortir akan menghapus kata kunci pencarian, dan pindah halaman akan
     menghapus urutan - keluhan paling umum pada tabel semacam ini. */
$url = function (array $ubah = array()) use ($cari, $urut, $arah, $page) {
  $q = array_filter(array_merge(
    array('q' => $cari, 'sort' => $urut, 'dir' => $arah, 'page' => $page),
    $ubah
  ), function ($v) {
    return $v !== '' && $v !== NULL;
  });
  return site_url('seller') . ($q ? '?' . http_build_query($q) : '');
};

/* Klik kolom yang sedang aktif -> balik arah. Klik kolom lain -> mulai
     dari urutan yang paling wajar untuk kolom itu: teks dari A, angka dari
     yang terbesar. */
$sortir = function ($kunci, $label, $angka = FALSE) use ($url, $urut, $arah) {
  $aktif = ($urut === $kunci);
  $baru  = $aktif ? ($arah === 'ASC' ? 'DESC' : 'ASC') : ($angka ? 'DESC' : 'ASC');
  $panah = $aktif ? ($arah === 'ASC' ? '&uarr;' : '&darr;') : '';
  echo '<a class="gt-head' . ($aktif ? ' is-sorted' : '') . '" href="'
    . html_escape($url(array('sort' => $kunci, 'dir' => $baru, 'page' => 1)))
    . '">' . html_escape($label) . ' <span>' . $panah . '</span></a>';
};
?>

<div class="panel-judul">
  <div>
    <h1>Produk</h1>
    <p class="hint"><?= html_escape($store['name']) ?></p>
  </div>
  <a href="<?= site_url('seller/form') ?>" class="btn btn-primary">Tambah produk</a>
</div>

<!-- Pencarian lewat GET: hasilnya bisa di-bookmark dan tidak memunculkan
     dialog "kirim ulang formulir" saat halaman di-refresh. -->
<form method="get" action="<?= site_url('seller') ?>" class="seller-cari">
  <input type="search" name="q" class="form-control" value="<?= html_escape($cari) ?>"
    placeholder="Cari nama produk, kategori, atau harga">
  <!-- Urutan ikut terbawa saat mencari, halaman kembali ke 1. -->
  <input type="hidden" name="sort" value="<?= html_escape($urut) ?>">
  <input type="hidden" name="dir" value="<?= html_escape($arah) ?>">
  <button type="submit" class="btn btn-primary">Cari</button>
  <?php if ($cari !== ''): ?>
    <a href="<?= site_url('seller') ?>" class="btn btn-black-hover-outline">Hapus</a>
  <?php endif; ?>
</form>

<p class="result-count">
  <?php if ($total === 0): ?>
    Tidak ada produk
  <?php else: ?>
    Menampilkan <strong><?= $dari ?>&ndash;<?= $sampai ?></strong> dari
    <strong><?= $total ?></strong> produk
    <?php if ($cari !== ''): ?> untuk &ldquo;<?= html_escape($cari) ?>&rdquo;<?php endif; ?>
    <?php endif; ?>
</p>

<?php if ($total === 0): ?>

  <div class="empty-state">
    <?php if ($cari !== ''): ?>
      <!-- Dibedakan dari "belum punya produk". Menyuruh penjual menambah
           produk padahal dia cuma salah ketik kata kunci itu membingungkan. -->
      <p class="empty-title">Tidak ada yang cocok</p>
      <p class="hint mb-4">Coba kata kunci lain.</p>
      <a href="<?= site_url('seller') ?>" class="btn btn-primary">Lihat semua produk</a>
    <?php else: ?>
      <p class="empty-title">Belum ada produk</p>
      <p class="hint mb-4">Tambahkan produk pertama supaya toko kamu muncul di katalog.</p>
      <a href="<?= site_url('seller/form') ?>" class="btn btn-primary">Tambah produk</a>
    <?php endif; ?>
  </div>

<?php else: ?>

  <!-- CSS Grid, bukan <table>. Di layar kecil tiap baris berubah jadi
       kartu - itu yang tidak bisa dilakukan tabel biasa tanpa akal-akalan. -->
  <div class="gt" role="table" aria-label="Daftar produk">

    <div class="gt__head" role="row">
      <span role="columnheader"></span>
      <span role="columnheader"><?php $sortir('nama', 'Produk'); ?></span>
      <span role="columnheader"><?php $sortir('kategori', 'Kategori'); ?></span>
      <span role="columnheader"><?php $sortir('harga', 'Harga', TRUE); ?></span>
      <span role="columnheader"><?php $sortir('status', 'Status', TRUE); ?></span>
      <span role="columnheader">Aksi</span>
    </div>

    <?php foreach ($products as $p): ?>
      <div class="gt__row" role="row">

        <span class="gt__cell gt__cell--img" role="cell">
          <img src="<?= base_url('upload/produk/' . $p['image']) ?>"
            alt="" loading="lazy">
        </span>

        <span class="gt__cell gt__cell--nama" role="cell">
          <strong><?= html_escape($p['name']) ?></strong>
          <!-- Di mobile, kategori & harga pindah ke sini sebagai baris
               kedua - kolomnya sendiri disembunyikan. -->
          <em class="gt__meta">
            <?= html_escape($p['category_name'] ?: 'Tanpa kategori') ?>
            &middot; <?= rupiah($p['price']) ?>
          </em>
        </span>

        <span class="gt__cell" role="cell" data-label="Kategori">
          <?= html_escape($p['category_name'] ?: '-') ?>
        </span>

        <span class="gt__cell gt__cell--harga" role="cell" data-label="Harga">
          <?= rupiah($p['price']) ?>
        </span>

        <span class="gt__cell" role="cell" data-label="Status">
          <span class="badge-status <?= $p['is_active'] ? 'is-ok' : '' ?>">
            <?= $p['is_active'] ? 'Tayang' : 'Disembunyikan' ?>
          </span>
        </span>

        <span class="gt__cell gt__cell--aksi" role="cell">
          <a href="<?= site_url('seller/form/' . $p['id']) ?>">Ubah</a>
          <?= form_open('seller/toggle/' . $p['id'], array('class' => 'inline-form')) ?>
          <button type="submit" class="link-btn">
            <?= $p['is_active'] ? 'Sembunyikan' : 'Tayangkan' ?>
          </button>
          <?= form_close() ?>
        </span>

      </div>
    <?php endforeach; ?>
  </div>

  <?php if ($halaman > 1): ?>
    <nav class="gt-pagination" aria-label="Halaman produk">
      <a class="page-btn<?= $page <= 1 ? ' is-off' : '' ?>"
        <?= $page > 1 ? 'href="' . html_escape($url(array('page' => $page - 1))) . '"' : '' ?>>&larr;</a>

      <?php
      // Tampilkan maksimal 5 nomor di sekitar halaman aktif, supaya
      // 40 halaman tidak membuat baris ini melebar tak terkendali.
      $awal = max(1, $page - 2);
      $akhir = min($halaman, $awal + 4);
      $awal = max(1, $akhir - 4);
      ?>
      <?php if ($awal > 1): ?>
        <a class="page-btn" href="<?= html_escape($url(array('page' => 1))) ?>">1</a>
        <?php if ($awal > 2): ?><span class="page-gap">&hellip;</span><?php endif; ?>
      <?php endif; ?>

      <?php for ($i = $awal; $i <= $akhir; $i++): ?>
        <a class="page-btn<?= $i === $page ? ' is-active' : '' ?>"
          href="<?= html_escape($url(array('page' => $i))) ?>"><?= $i ?></a>
      <?php endfor; ?>

      <?php if ($akhir < $halaman): ?>
        <?php if ($akhir < $halaman - 1): ?><span class="page-gap">&hellip;</span><?php endif; ?>
        <a class="page-btn" href="<?= html_escape($url(array('page' => $halaman))) ?>"><?= $halaman ?></a>
      <?php endif; ?>

      <a class="page-btn<?= $page >= $halaman ? ' is-off' : '' ?>"
        <?= $page < $halaman ? 'href="' . html_escape($url(array('page' => $page + 1))) . '"' : '' ?>>&rarr;</a>
    </nav>
  <?php endif; ?>

<?php endif; ?>