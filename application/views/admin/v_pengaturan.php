<!-- application/views/admin/v_pengaturan.php -->
<div class="panel-judul">
  <div>
    <h1>Pengaturan sistem</h1>
    <p class="hint">Berlaku untuk seluruh marketplace. Kolom rahasia tidak ditampilkan ulang &mdash; kosongkan kalau tidak ingin mengubahnya.</p>
  </div>
</div>

<?php $this->load->view('admin/parts_pengaturan_tab', array('tab' => $tab)); ?>

<?= form_open('admin/pengaturan/simpan') ?>
<?php foreach ($daftar as $kelompok => $kolom):
  $kolom = array_filter($kolom, function ($d) { return empty($d['tersembunyi']); });
  if ( ! $kolom) { continue; }
?>
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

<div class="panel-bagian" id="whatsapp">
  <h2 class="panel-sub">WhatsApp</h2>
  <?php
    $bisa  = $wa['ada_lib'] && $wa['ada_tabel'] && $wa['server'] !== NULL;
    $pilih = array_merge(array('mati' => array('Mati', 'Tidak mengirim WhatsApp')), $wa['pilihan']);
    $kini  = ($p->aktif('wa_aktif') && $bisa) ? $wa['server'] : 'mati';
    $urut  = array_search($kini, array_keys($pilih), TRUE);
  ?>
  <div class="form-card">
    <p class="hint">Geser untuk memilih layanan yang mengirim semua notifikasi WhatsApp (OTP, reset sandi, pesanan, pengumuman).</p>
    <?php if ( ! $wa['ada_lib']): ?>
      <div class="alert-box alert-warn mb-3">Library <code>application/libraries/Api_Whatsapp.php</code> belum terpasang di server ini.</div>
    <?php elseif ( ! $bisa): ?>
      <div class="alert-box alert-warn mb-3">Tabel <code>utility</code> (kolom <code>server_wa</code>) belum ada atau masih kosong.</div>
    <?php endif; ?>
    <?= form_open('admin/pengaturan/server_wa', array('id' => 'formServerWa')) ?>
      <div class="wa-geser" role="radiogroup" aria-label="Server WhatsApp" style="--jumlah: <?= count($pilih) ?>; --posisi: <?= (int) $urut ?>">
        <span class="wa-geser-jempol<?= $kini === 'mati' ? ' is-mati' : '' ?>" aria-hidden="true"></span>
        <?php $i = 0; foreach ($pilih as $kode => $x): $mati_opsi = $kode !== 'mati' && ! $bisa; ?>
          <label class="wa-geser-opsi<?= $mati_opsi ? ' is-nonaktif' : '' ?>" title="<?= html_escape($x[1]) ?>">
            <input type="radio" name="server_wa" value="<?= $kode ?>" data-i="<?= $i++ ?>"
                   <?= $kode === $kini ? 'checked' : '' ?> <?= $mati_opsi ? 'disabled' : '' ?>>
            <span><?= html_escape($x[0]) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="hint mt-2 mb-0" id="waGeserKet"><?= html_escape($pilih[$kini][1]) ?></p>
      <noscript><button type="submit" class="btn btn-primary mt-2">Simpan</button></noscript>
    <?= form_close() ?>
  </div>
</div>
<script>
  /* Geseran langsung tersimpan begitu dipilih - tidak ada tombol Simpan
     yang bisa terlupa. Jempolnya digeser dulu supaya terasa responsif. */
  (function () {
    var f = document.getElementById('formServerWa');
    if (!f) { return; }
    var g = f.querySelector('.wa-geser'), j = f.querySelector('.wa-geser-jempol');
    f.addEventListener('change', function (e) {
      if (!e.target.matches('input[name="server_wa"]')) { return; }
      g.style.setProperty('--posisi', e.target.dataset.i);
      j.classList.toggle('is-mati', e.target.value === 'mati');
      document.getElementById('waGeserKet').textContent = 'Menyimpan...';
      f.submit();
    });
  })();
</script>

<div class="panel-bagian">
  <h2 class="panel-sub">Uji gerbang WhatsApp</h2>
  <?= form_open('admin/pengaturan/tes_wa', array('class' => 'fitur-baris-form')) ?>
    <input type="text" name="nomor" class="form-control" placeholder="Nomor tujuan (kosong = WA admin)" inputmode="numeric">
    <button type="submit" class="btn btn-outline-secondary">Kirim pesan uji</button>
  <?= form_close() ?>
</div>
