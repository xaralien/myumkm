<!-- application/views/mobile/v_shop.php -->
<?php
  $url_produk = function ($p) { return site_url('produk/' . $p['store_slug'] . '/' . $p['slug']); };

  // Alamat halaman ini dengan satu parameter diganti - dipakai chip filter.
  $ubah = function (array $ganti) use ($f) {
    $q = array_filter(array(
      'q'        => $f['q'],
      'category' => $f['category'],
      'sort'     => $f['sort'],
      'store'    => $f['store_id'] ?: '',
    ), 'strlen');
    foreach ($ganti as $k => $v) {
      if ($v === NULL || $v === '') { unset($q[$k]); } else { $q[$k] = $v; }
    }
    return site_url('shop') . ($q ? '?' . http_build_query($q) : '');
  };
?>

<?php $this->load->view('parts/kirim_ke'); ?>

<form class="mb-cari" action="<?= site_url('shop') ?>" method="get" role="search">
  <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-4-4"></path></svg>
  <input type="search" name="q" value="<?= html_escape($f['q']) ?>"
         placeholder="Cari produk UMKM..." aria-label="Cari produk">
  <?php if ($f['category']): ?><input type="hidden" name="category" value="<?= html_escape($f['category']) ?>"><?php endif; ?>
</form>

<!-- Kategori sebagai chip yang digeser: di HP, daftar dropdown panjang
     jauh lebih merepotkan daripada deretan yang bisa disapu jempol. -->
<div class="mb-geser mb-chips">
  <a href="<?= $ubah(array('category' => NULL)) ?>"
     class="mb-chip <?= $f['category'] === '' ? 'is-aktif' : '' ?>">Semua</a>
  <?php foreach ($categories as $c): ?>
    <a href="<?= $ubah(array('category' => $c['slug'])) ?>"
       class="mb-chip <?= $f['category'] === $c['slug'] ? 'is-aktif' : '' ?>">
      <?= html_escape($c['name']) ?> <em><?= (int) $c['jml'] ?></em>
    </a>
  <?php endforeach; ?>
</div>

<div class="mb-urut">
  <span><?= (int) $total ?> produk<?= $lokasi ? ' di sekitar ' . html_escape($lokasi['district_name']) : '' ?></span>
  <label>
    <span class="visually-hidden">Urutkan</span>
    <select onchange="location = this.value;" aria-label="Urutkan produk">
      <?php foreach ($sorts as $kunci => $label): ?>
        <option value="<?= html_escape($ubah(array('sort' => $kunci))) ?>"
          <?= $f['sort'] === $kunci ? 'selected' : '' ?>><?= html_escape($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
</div>

<?php if (! $products): ?>
  <p class="mb-kosong">
    Tidak ada produk yang cocok.<br>
    <a href="<?= site_url('shop') ?>" style="color: var(--wn-biru); font-weight: 600;">Hapus semua filter</a>
  </p>
<?php else: ?>

  <div class="mb-produk">
    <?php foreach ($products as $p): ?>
      <a href="<?= $url_produk($p) ?>" class="mb-kartu">
        <div class="mb-kartu-foto">
          <img src="<?= base_url('upload/produk/' . $p['image']) ?>"
               alt="<?= html_escape($p['name']) ?>" loading="lazy">
        </div>
        <div class="mb-kartu-isi">
          <span class="mb-kartu-nama"><?= html_escape($p['name']) ?></span>
          <span class="mb-kartu-toko">
            <?= html_escape($p['store_name']) ?><?php
              if (! empty($p['store_district'])) { echo ' &middot; ' . html_escape($p['store_district']); }
            ?>
          </span>
          <span class="mb-kartu-harga">
            <?php if ((int) $p['variant_count'] > 1): ?><small>mulai </small><?php endif; ?>
            <?= rupiah($p['price']) ?>
          </span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($pagess > 1): ?>
    <nav class="mb-halaman" aria-label="Halaman">
      <?php if ($page > 1): ?>
        <a href="<?= html_escape($ubah(array()) . ($f['q'] || $f['category'] || $f['sort'] ? '&amp;' : '?') . 'page=' . ($page - 1)) ?>">Sebelumnya</a>
      <?php endif; ?>
      <span>Halaman <?= (int) $page ?> dari <?= (int) $pagess ?></span>
      <?php if ($page < $pagess): ?>
        <a href="<?= html_escape($ubah(array()) . ($f['q'] || $f['category'] || $f['sort'] ? '&amp;' : '?') . 'page=' . ($page + 1)) ?>">Berikutnya</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<!-- gps.js dimuat sekali dari kerangka, bukan per halaman. -->

