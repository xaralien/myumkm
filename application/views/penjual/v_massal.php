<!-- application/views/penjual/v_massal.php -->
<div class="panel-judul">
  <div>
    <h1>Unggah banyak produk</h1>
    <p class="hint">Isi daftar produk di Excel, lalu unggah sekaligus &mdash; tidak perlu mengetik satu per satu.</p>
  </div>
  <a href="<?= site_url('seller') ?>" class="btn btn-black-hover-outline">Kembali</a>
</div>

<div class="langkah-baris mb-4">
  <div class="fitur-kartu">
    <span class="langkah-no">1</span>
    <h3>Unduh templat</h3>
    <p class="hint">Berkas Excel berisi kolom yang dibutuhkan dan 2 contoh baris.</p>
    <a href="<?= site_url('seller/massal/contoh') ?>" class="btn btn-primary">⬇ Unduh templat Excel</a>
  </div>
  <div class="fitur-kartu">
    <span class="langkah-no">2</span>
    <h3>Isi di Excel</h3>
    <p class="hint mb-1">Satu baris = satu produk. Kolom bertanda * wajib.</p>
    <ul class="hint mb-0">
      <?php foreach ($kolom as $k): ?><li><?= html_escape($k) ?></li><?php endforeach; ?>
    </ul>
  </div>
  <div class="fitur-kartu">
    <span class="langkah-no">3</span>
    <h3>Unggah di sini</h3>
    <?= form_open_multipart('seller/massal/unggah') ?>
      <input type="file" name="berkas" class="form-control mb-2" accept=".xlsx,.csv" required>
      <button type="submit" class="btn btn-primary w-100">⬆ Unggah</button>
    <?= form_close() ?>
    <p class="hint mt-2 mb-0">Produk masuk sebagai <strong>draf</strong>. Tambahkan fotonya, lalu tekan Tayangkan.</p>
  </div>
</div>

<?php if ($hasil && $hasil['galat']): ?>
  <div class="alert-box alert-warn">
    <strong>Catatan:</strong>
    <ul class="mb-0">
      <?php foreach ($hasil['galat'] as $g): ?><li><?= html_escape($g) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<p class="hint">Kategori yang dikenali: <?= html_escape(implode(', ', array_column($kategori, 'name'))) ?>.</p>
