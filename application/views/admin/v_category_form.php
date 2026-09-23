<!-- application/views/admin/v_category_form.php -->
<?php $c = $category; ?>

<div class="panel-judul">
  <h1><?= $c ? 'Ubah kategori' : 'Tambah kategori' ?></h1>
  <a href="<?= site_url('admin/categories') ?>" class="btn btn-black-hover-outline">Kembali</a>
</div>

<div class="row">
  <div class="col-lg-7">
    <?= form_open('admin/categories/save') ?>
    <input type="hidden" name="id" value="<?= $c ? (int) $c['id'] : '' ?>">

    <div class="form-card">
      <div class="mb-3">
        <label class="form-label" for="name">Nama kategori *</label>
        <input type="text" id="name" name="name" class="form-control" maxlength="80" required autofocus
               value="<?= set_value('name', $c ? $c['name'] : '') ?>">
        <?= form_error('name') ?>
      </div>

      <div class="mb-3">
        <label class="form-label" for="keterangan">Keterangan singkat</label>
        <input type="text" id="keterangan" name="keterangan" class="form-control" maxlength="120"
               placeholder="Camilan, lauk, kopi"
               value="<?= set_value('keterangan', $c ? $c['keterangan'] : '') ?>">
        <p class="hint">Satu baris di bawah nama kategori pada kartu di beranda.</p>
      </div>

      <div class="mb-3">
        <label class="form-label" for="icon">Ikon</label>
        <select id="icon" name="icon" class="form-control">
          <option value="">(ikon umum)</option>
          <?php foreach ($ikon as $kunci => $label): ?>
            <option value="<?= $kunci ?>" <?= set_select('icon', $kunci, $c && $c['icon'] === $kunci) ?>>
              <?= html_escape($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="hint">Gambar ikonnya sudah tersedia di beranda; ini hanya memilih yang mana.</p>
      </div>

      <div class="mb-3">
        <label class="form-label" for="sort_order">Urutan tampil</label>
        <input type="number" id="sort_order" name="sort_order" class="form-control" min="0" max="999"
               value="<?= set_value('sort_order', $c ? (int) $c['sort_order'] : 0) ?>">
        <p class="hint">Angka kecil tampil lebih dulu. Nilai sama diurutkan menurut abjad.</p>
        <?= form_error('sort_order') ?>
      </div>

      <label class="akun-setuju">
        <input type="checkbox" name="is_active" value="1"
               <?= set_checkbox('is_active', '1', $c ? (bool) $c['is_active'] : TRUE) ?>>
        <span>Aktif &mdash; tampil di katalog, beranda, dan pilihan kategori penjual.</span>
      </label>
    </div>

    <?php if ($c): ?>
      <p class="hint mb-3">
        Alamat kategori ini tetap <code><?= html_escape($c['slug']) ?></code> walau namanya diubah,
        supaya tautan yang sudah dibagikan tidak putus.
      </p>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary">Simpan</button>
    <?= form_close() ?>
  </div>
</div>
