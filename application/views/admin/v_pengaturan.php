<!-- application/views/admin/v_pengaturan.php -->
<div class="panel-judul">
  <div>
    <h1>Pengaturan sistem</h1>
    <p class="hint">Berlaku untuk seluruh marketplace. Kolom rahasia tidak ditampilkan ulang &mdash; kosongkan kalau tidak ingin mengubahnya.</p>
  </div>
</div>

<?php $this->load->view('admin/parts_pengaturan_tab', array('tab' => $tab)); ?>

<?= form_open('admin/pengaturan/simpan') ?>
<?php foreach ($daftar as $kelompok => $kolom): ?>
  <div class="panel-bagian">
    <h2 class="panel-sub"><?= html_escape($kelompok) ?></h2>
    <div class="form-card">
      <div class="row">
        <?php foreach ($kolom as $k => $d):
          $rahasia = ! empty($d['rahasia']);
          $nilai   = $rahasia ? '' : $p->get($k);
          $terisi  = $rahasia && (string) $p->get($k) !== '';
        ?>
          <div class="col-md-6 mb-3">
            <label class="form-label" for="set_<?= $k ?>"><?= html_escape($d['label']) ?></label>
            <?php if ($d['jenis'] === 'ya_tidak'): ?>
              <select id="set_<?= $k ?>" name="<?= $k ?>" class="form-select">
                <option value="0" <?= (string) $nilai !== '1' ? 'selected' : '' ?>>Tidak</option>
                <option value="1" <?= (string) $nilai === '1' ? 'selected' : '' ?>>Ya</option>
              </select>
            <?php else: ?>
              <input type="<?= $rahasia ? 'password' : 'text' ?>" id="set_<?= $k ?>" name="<?= $k ?>"
                     class="form-control" autocomplete="off"
                     <?= $d['jenis'] === 'angka' ? 'inputmode="decimal"' : '' ?>
                     placeholder="<?= $rahasia ? ($terisi ? '•••••••• (tersimpan)' : 'belum diisi') : '' ?>"
                     value="<?= html_escape((string) $nilai) ?>">
            <?php endif; ?>
            <?php if ( ! empty($d['catatan'])): ?>
              <p class="hint mt-1 mb-0"><?= html_escape($d['catatan']) ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
<div class="mb-5">
  <button type="submit" class="btn btn-primary">Simpan pengaturan</button>
</div>
<?= form_close() ?>

<div class="panel-bagian">
  <h2 class="panel-sub">Uji gerbang WhatsApp</h2>
  <?= form_open('admin/pengaturan/tes_wa', array('class' => 'fitur-baris-form')) ?>
    <input type="text" name="nomor" class="form-control" placeholder="Nomor tujuan (kosong = WA admin)" inputmode="numeric">
    <button type="submit" class="btn btn-outline-secondary">Kirim pesan uji</button>
  <?= form_close() ?>
</div>
