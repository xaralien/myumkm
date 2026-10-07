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
  <span><?= (int) $total ?> produk</span>

  <div class="mb-urut-aksi">
    <label>
      <span class="visually-hidden">Urutkan</span>
      <select onchange="location = this.value;" aria-label="Urutkan produk">
        <?php foreach ($sorts as $kunci => $label): ?>
          <option value="<?= html_escape($ubah(array('sort' => $kunci))) ?>"
            <?= $f['sort'] === $kunci ? 'selected' : '' ?>><?= html_escape($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <?php $filter_aktif = ($f['min'] !== NULL || $f['max'] !== NULL); ?>
    <button type="button" class="mb-filter-tombol <?= $filter_aktif ? 'is-aktif' : '' ?>" id="mbFilterBuka">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"></path></svg>
      Filter<?= $filter_aktif ? ' <i>1</i>' : '' ?>
    </button>
  </div>
</div>

<!-- Lembar filter naik dari bawah, bukan halaman terpisah: pindah halaman
     berarti kehilangan posisi gulir, dan pembeli yang sudah menggulir jauh
     harus mencarinya lagi dari awal. -->
<div class="mb-filter-scrim" id="mbFilterScrim" hidden></div>

<aside class="mb-filter-sheet" id="mbFilterSheet" hidden aria-label="Filter produk">
  <div class="mb-filter-kepala">
    <strong>Filter</strong>
    <button type="button" id="mbFilterTutup" aria-label="Tutup">&times;</button>
  </div>

  <form method="get" action="<?= site_url('shop') ?>">
    <!-- Pencarian, kategori, dan urutan yang sedang aktif ikut dibawa.
         Tanpa ini, menerapkan filter harga diam-diam menghapus kata kunci
         yang baru saja diketik pembeli. -->
    <?php foreach (array('q' => $f['q'], 'category' => $f['category'], 'sort' => $f['sort']) as $k => $v): ?>
      <?php if ($v !== '' && $v !== NULL): ?>
        <input type="hidden" name="<?= $k ?>" value="<?= html_escape($v) ?>">
      <?php endif; ?>
    <?php endforeach; ?>

    <p class="mb-filter-label">Rentang harga</p>
    <div class="mb-filter-harga">
      <label class="visually-hidden" for="mbMin">Harga terendah</label>
      <input type="number" id="mbMin" name="min" inputmode="numeric" min="0" placeholder="Rp terendah"
             value="<?= $f['min'] !== NULL ? (int) $f['min'] : '' ?>">
      <span aria-hidden="true">&ndash;</span>
      <label class="visually-hidden" for="mbMax">Harga tertinggi</label>
      <input type="number" id="mbMax" name="max" inputmode="numeric" min="0" placeholder="Rp tertinggi"
             value="<?= $f['max'] !== NULL ? (int) $f['max'] : '' ?>">
    </div>

    <?php if (! empty($range['min']) || ! empty($range['max'])): ?>
      <p class="mb-filter-bantu">
        Produk di katalog: <?= rupiah($range['min']) ?> &ndash; <?= rupiah($range['max']) ?>
      </p>
    <?php endif; ?>

    <p class="mb-filter-label">Pintasan</p>
    <div class="mb-filter-cepat">
      <?php foreach (array(
          array('Di bawah 50rb', NULL, 50000),
          array('50rb - 150rb', 50000, 150000),
          array('150rb - 500rb', 150000, 500000),
          array('Di atas 500rb', 500000, NULL),
      ) as $c): ?>
        <a href="<?= html_escape($ubah(array('min' => $c[1], 'max' => $c[2]))) ?>"
           class="<?= ((int) $f['min'] === (int) $c[1] && (int) $f['max'] === (int) $c[2]) ? 'is-aktif' : '' ?>">
          <?= $c[0] ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="mb-filter-aksi">
      <a href="<?= html_escape($ubah(array('min' => NULL, 'max' => NULL))) ?>" class="mb-filter-reset">Hapus filter</a>
      <button type="submit">Terapkan</button>
    </div>
  </form>
</aside>

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
          <?php $teks_terjual = isset($p['terjual']) ? terjual_teks($p['terjual']) : ''; ?>
          <?php if ((int) $p['rating_count'] > 0 || $teks_terjual): ?>
            <!-- Rating PRODUK, bukan rating toko: kartu ini tentang satu
                 barang, dan toko bagus pun bisa punya produk yang
                 mengecewakan. -->
            <span class="mb-kartu-rating">
              <?php if ((int) $p['rating_count'] > 0): ?>
                <svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
                <?= number_format($p['rating_avg'], 1, ',', '.') ?>
                <em>(<?= (int) $p['rating_count'] ?>)</em>
              <?php endif; ?>

              <?php if ((int) $p['rating_count'] > 0 && $teks_terjual): ?><i aria-hidden="true">&middot;</i><?php endif; ?>
              <?php if ($teks_terjual): ?><em><?= $teks_terjual ?></em><?php endif; ?>
            </span>
          <?php endif; ?>

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

<script>
  /* Lembar filter. Dibuka/ditutup dengan atribut hidden, bukan kelas:
     elemen ber-hidden juga disembunyikan dari pembaca layar, sedangkan
     kelas yang cuma mengatur tampilan tetap terbaca di sana. */
  (function () {
    var sheet = document.getElementById('mbFilterSheet');
    var scrim = document.getElementById('mbFilterScrim');
    var buka  = document.getElementById('mbFilterBuka');
    var tutup = document.getElementById('mbFilterTutup');
    if (!sheet || !buka) { return; }

    function setBuka(ya) {
      sheet.hidden = !ya;
      if (scrim) { scrim.hidden = !ya; }
      document.body.style.overflow = ya ? 'hidden' : '';
    }

    buka.addEventListener('click', function () { setBuka(true); });
    if (tutup) { tutup.addEventListener('click', function () { setBuka(false); }); }
    if (scrim) { scrim.addEventListener('click', function () { setBuka(false); }); }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !sheet.hidden) { setBuka(false); }
    });
  })();
</script>
