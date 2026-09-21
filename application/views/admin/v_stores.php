<!-- application/views/admin/v_stores.php -->
<div class="panel-judul">
  <h1>Daftar Toko</h1>
  <a href="<?= site_url('admin/stores/form') ?>" class="btn btn-primary">Tambah toko</a>
</div>

<?php if ( ! $stores): ?>
  <div class="empty-state">
    <p class="empty-title">Belum ada toko</p>
    <p class="hint mb-4">Buat toko pertama supaya katalog ada isinya.</p>
    <a href="<?= site_url('admin/stores/form') ?>" class="btn btn-primary">Tambah toko</a>
  </div>
<?php else: ?>
  <div class="panel-tabel-bungkus">
    <table class="panel-tabel">
      <thead>
        <tr>
          <th>Toko</th><th>Pemilik</th><th>Wilayah</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($stores as $s): ?>
        <tr>
          <td>
            <strong><?= html_escape($s['name']) ?></strong>
            <em><?= html_escape($s['phone']) ?></em>
          </td>
          <td><?= html_escape($s['email']) ?></td>
          <td>
            <?= html_escape($s['district_name']) ?>
            <em><?= html_escape($s['regency_name']) ?>, <?= html_escape($s['province_name']) ?></em>
          </td>
          <td>
            <span class="badge-status <?= $s['is_active'] ? 'is-ok' : '' ?>">
              <?= $s['is_active'] ? 'Aktif' : 'Nonaktif' ?>
            </span>
          </td>
          <td class="panel-aksi">
            <a href="<?= site_url('admin/stores/form/' . $s['id']) ?>">Ubah</a>
            <?= form_open('admin/stores/toggle/' . $s['id'], array('class' => 'inline-form')) ?>
              <button type="submit" class="link-btn">
                <?= $s['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
              </button>
            <?= form_close() ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
