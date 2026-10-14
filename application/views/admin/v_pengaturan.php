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

<div class="panel-bagian" id="server-wa">
  <h2 class="panel-sub">Server WhatsApp</h2>
  <?php if ( ! $wa['ada_lib']): ?>
    <div class="alert-box alert-warn">Library <code>application/libraries/Api_Whatsapp.php</code> belum terpasang di server ini,
      jadi WA dikirim lewat gerbang di bagian WhatsApp di atas.</div>
  <?php elseif ( ! $wa['ada_tabel'] || $wa['server'] === NULL): ?>
    <div class="alert-box alert-warn">Tabel <code>utility</code> (kolom <code>server_wa</code>) belum ada atau masih kosong,
      jadi server WhatsApp belum bisa dipilih di sini.</div>
  <?php else: ?>
    <p class="hint">Pilih layanan yang dipakai untuk semua notifikasi WhatsApp (OTP, reset sandi, pesanan, pengumuman).
      <?php if ( ! $p->aktif('wa_aktif')): ?><strong>Catatan: "Kirim notifikasi WhatsApp" di atas sedang Tidak - pesan tidak dikirim dari server mana pun.</strong><?php endif; ?></p>
    <div class="wa-server">
      <?php foreach ($wa['pilihan'] as $kode => $x): $aktif = $kode === $wa['server']; ?>
        <div class="wa-server-kartu<?= $aktif ? ' is-aktif' : '' ?>">
          <div>
            <strong><?= html_escape($x[0]) ?></strong>
            <?php if ($aktif): ?><span class="wa-server-lencana">Aktif</span><?php endif; ?>
            <p class="hint mb-0"><?= html_escape($x[1]) ?></p>
          </div>
          <?php if ($aktif): ?>
            <button type="button" class="btn btn-primary" disabled>Sedang dipakai</button>
          <?php else: ?>
            <?= form_open('admin/pengaturan/server_wa') ?>
              <input type="hidden" name="server_wa" value="<?= $kode ?>">
              <button type="submit" class="btn btn-outline-secondary"
                      onclick="return confirm('Pindahkan semua notifikasi WhatsApp ke <?= html_escape($x[0]) ?>?');">Aktifkan</button>
            <?= form_close() ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="panel-bagian">
  <h2 class="panel-sub">Uji gerbang WhatsApp</h2>
  <?= form_open('admin/pengaturan/tes_wa', array('class' => 'fitur-baris-form')) ?>
    <input type="text" name="nomor" class="form-control" placeholder="Nomor tujuan (kosong = WA admin)" inputmode="numeric">
    <button type="submit" class="btn btn-outline-secondary">Kirim pesan uji</button>
  <?= form_close() ?>
</div>
