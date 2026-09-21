<!-- application/views/seller/v_products.php -->
<div class="panel-judul">
  <div>
    <h1>Produk</h1>
    <p class="hint"><?= html_escape($store['name']) ?></p>
  </div>
  <a href="<?= site_url('seller/form') ?>" class="btn btn-primary">Tambah produk</a>
</div>

<?php if (!$products): ?>
  <div class="empty-state">
    <p class="empty-title">Belum ada produk</p>
    <p class="hint mb-4">Tambahkan produk pertama supaya toko kamu muncul di katalog.</p>
    <a href="<?= site_url('seller/form') ?>" class="btn btn-primary">Tambah produk</a>
  </div>
<?php else: ?>
  <?= form_open('seller/') ?>

  <div class="row d-flex justify-content-between mb-3">
    <div class="col-md-12 col-lg-11 mb-2">
      <div class="form-group">
        <input type="text" class="form-control" name="search" value="<?= set_value('search') ?>" placeholder="Search...">
      </div>
    </div>
    <div class="col-md-12 col-lg-1 mb-2">
      <button type="submit" class="btn">Search</button>
    </div>
  </div>
  <?= form_close() ?>
  <div class="panel-tabel-bungkus">
    <table class="panel-tabel">
      <thead>
        <tr>
          <th>Image</th>
          <th>Produk</th>
          <th>Kategori</th>
          <th>Harga</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <img src="<?= base_url('upload/produk/' . $p['image']) ?>" alt="<?= html_escape($p['name']) ?>" class="img-thumbnail" width="100">
            </td>
            <td>
              <strong><?= html_escape($p['name']) ?></strong>
              <!-- <em><?= html_escape($p['image']) ?></em> -->
              <?php
              // Ambil data varian dan addons
              $variant_data = $this->db->get_where('product_variants', ['product_id' => $p['id']])->result_array();
              $addons_data  = $this->db->get_where('product_addons', ['product_id' => $p['id']])->result_array();

              // Ambil kolom nama saja, lalu gabungkan dengan koma
              $variant_list = !empty($variant_data) ? implode(', ', array_column($variant_data, 'name')) : '-';
              $addons_list  = !empty($addons_data)  ? implode(', ', array_column($addons_data, 'name'))  : '-';
              ?>

              <em>Varian : <?= html_escape($variant_list) ?></em>
              <em>Addons : <?= html_escape($addons_list) ?></em>
            </td>
            <td><?= html_escape($p['category_name'] ?: '-') ?></td>
            <td><?= rupiah($p['price']) ?></td>
            <td>
              <span class="badge-status <?= $p['is_active'] ? 'is-ok' : '' ?>">
                <?= $p['is_active'] ? 'Tayang' : 'Disembunyikan' ?>
              </span>
            </td>
            <td class="panel-aksi">
              <a href="<?= site_url('seller/form/' . $p['id']) ?>">Ubah</a>
              <?= form_open('seller/toggle/' . $p['id'], array('class' => 'inline-form')) ?>
              <button type="submit" class="link-btn">
                <?= $p['is_active'] ? 'Sembunyikan' : 'Tayangkan' ?>
              </button>
              <?= form_close() ?>
              <?= form_open(
                'seller/delete/' . $p['id'],
                array('class' => 'inline-form form-delete')
              ) ?>
              <button type="submit" class="link-btn">
                Hapus
              </button>
              <?= form_close() ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<!-- Library SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  document.querySelectorAll('.form-delete').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault(); // Hentikan submit langsung

      Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Data yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmColor: '#d33',
        cancelColor: '#3085d6',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          this.submit(); // Kirim form jika dikonfirmasi
        }
      });
    });
  });
</script>