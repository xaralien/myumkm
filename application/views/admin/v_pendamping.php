<!-- application/views/admin/v_pendamping.php -->
<div class="panel-judul">
  <div>
    <h1>Pendamping UMKM</h1>
    <p class="hint">Pendamping (dari dinas, kampus, atau komunitas) memantau skor Naik Kelas toko binaannya dan mencatat setiap kunjungan.</p>
  </div>
</div>

<div class="row">
  <div class="col-lg-5 mb-4">
    <h2 class="panel-sub">Pendamping</h2>
    <?= form_open('admin/pendamping/angkat', array('class' => 'fitur-baris-form mb-3')) ?>
      <input type="email" name="email" class="form-control" placeholder="Email akun pendamping" required>
      <button type="submit" class="btn btn-primary">Jadikan pendamping</button>
    <?= form_close() ?>
    <?php if ( ! $pendamping): ?><p class="hint">Belum ada pendamping.</p><?php endif; ?>
    <div class="adm-tabel">
      <?php foreach ($pendamping as $p): ?>
        <div class="adm-baris">
          <div><strong><?= html_escape($p['name']) ?></strong>
            <em><?= html_escape($p['email']) ?> &middot; <?= (int) $p['jml_toko'] ?> toko &middot; <?= (int) $p['catatan_30'] ?> catatan 30 hari</em></div>
          <div class="adm-aksi">
            <?= form_open('admin/pendamping/cabut/' . (int) $p['id'], array('class' => 'inline-form')) ?>
              <button type="submit" class="btn btn-sm btn-black-hover-outline" onclick="return confirm('Cabut peran pendamping?')">Cabut</button>
            <?= form_close() ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-7">
    <h2 class="panel-sub">Penugasan toko</h2>
    <form method="get" class="fitur-baris-form mb-3">
      <input type="search" name="q" class="form-control" placeholder="Cari toko, kota, atau kecamatan" value="<?= html_escape($cari) ?>">
      <button class="btn btn-black-hover-outline">Cari</button>
    </form>
    <div class="adm-tabel">
      <?php foreach ($toko as $t): ?>
        <div class="adm-baris">
          <div><strong><?= html_escape($t['name']) ?></strong><em><?= html_escape($t['district_name']) ?>, <?= html_escape($t['regency_name']) ?></em></div>
          <div class="adm-aksi">
            <?= form_open('admin/pendamping/tetapkan', array('class' => 'fitur-baris-form')) ?>
              <input type="hidden" name="store_id" value="<?= (int) $t['id'] ?>">
              <input type="hidden" name="q" value="<?= html_escape($cari) ?>">
              <select name="pendamping_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="0">&mdash; tanpa pendamping &mdash;</option>
                <?php foreach ($pendamping as $p): ?>
                  <option value="<?= (int) $p['id'] ?>" <?= (int) $t['pendamping_id'] === (int) $p['id'] ? 'selected' : '' ?>><?= html_escape($p['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <noscript><button class="btn btn-sm btn-black-hover-outline">Simpan</button></noscript>
            <?= form_close() ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
