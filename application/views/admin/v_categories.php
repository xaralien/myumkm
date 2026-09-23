<!-- application/views/admin/v_categories.php -->
<div class="panel-judul">
  <div>
    <h1>Kategori</h1>
    <p class="hint">Dipakai di menu katalog, beranda, dan saat penjual menambah produk.</p>
  </div>
  <a href="<?= site_url('admin/categories/form') ?>" class="btn btn-primary">Tambah kategori</a>
</div>

<?php if (! $categories): ?>
  <div class="empty-state">
    <p class="empty-title">Belum ada kategori</p>
    <a href="<?= site_url('admin/categories/form') ?>" class="btn btn-primary">Tambah kategori</a>
  </div>
<?php else: ?>

  <div class="adm-tabel">
    <?php foreach ($categories as $c): ?>
      <div class="adm-baris <?= $c['is_active'] ? '' : 'is-mati' ?>">
        <div>
          <strong><?= html_escape($c['name']) ?></strong>
          <em>
            <?= html_escape($c['slug']) ?>
            <?php if ($c['keterangan']): ?> &middot; <?= html_escape($c['keterangan']) ?><?php endif; ?>
            &middot; urutan <?= (int) $c['sort_order'] ?>
          </em>
        </div>

        <div class="adm-aksi">
          <span class="hint"><?= (int) $c['jml_produk'] ?> produk</span>

          <span class="badge-status <?= $c['is_active'] ? 'is-ok' : '' ?>">
            <?= $c['is_active'] ? 'Aktif' : 'Nonaktif' ?>
          </span>

          <a href="<?= site_url('admin/categories/form/' . $c['id']) ?>" class="btn btn-black-hover-outline btn-sm">Ubah</a>

          <?= form_open('admin/categories/toggle/' . $c['id'], array('class' => 'inline-form')) ?>
            <button type="submit" class="btn btn-black-hover-outline btn-sm">
              <?= $c['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
            </button>
          <?= form_close() ?>

          <?php if ((int) $c['jml_produk'] === 0): ?>
            <!-- Tombol hapus hanya muncul kalau memang bisa dihapus. Server
                 tetap memeriksa ulang - tombol yang disembunyikan CSS masih
                 bisa dikirim lewat DevTools. -->
            <?= form_open('admin/categories/hapus/' . $c['id'], array('class' => 'inline-form')) ?>
              <button type="submit" class="link-btn"
                      onclick="return confirm('Hapus kategori <?= html_escape($c['name']) ?>?');">Hapus</button>
            <?= form_close() ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <p class="hint mt-3">
    Kategori yang sudah dipakai produk tidak bisa dihapus &mdash; nonaktifkan saja.
    Kategori nonaktif hilang dari katalog dan beranda, tapi produknya tetap aman.
  </p>
<?php endif; ?>
