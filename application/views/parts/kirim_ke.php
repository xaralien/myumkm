<?php
/* =============================================================================
   Pemilih tujuan pengiriman di katalog.

   Katalog diurutkan dari toko terdekat ke TUJUAN, bukan ke posisi pembeli.
   Keduanya sering sama, tapi tidak selalu: kado ke kota lain dikirim ke
   sana, dan toko dekat tujuan yang lebih masuk akal.

   Diperlukan: $tujuan (array|NULL), $alamat_saya (array).
   ========================================================================== */
?>
<div class="kirim-ke">
  <div class="kirim-ke-kini">
    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"></path><circle cx="12" cy="10" r="2.5"></circle>
    </svg>

    <div>
      <span class="kirim-ke-label">Kirim ke</span>
      <?php if ($tujuan): ?>
        <strong><?= html_escape($tujuan['label'] ?? 'Lokasi saya') ?></strong>
        <?php if (! empty($tujuan['wilayah'])): ?>
          <em><?= html_escape($tujuan['wilayah']) ?></em>
        <?php endif; ?>
      <?php else: ?>
        <strong class="is-kosong">Belum dipilih</strong>
        <em>Produk ditampilkan tanpa urutan jarak</em>
      <?php endif; ?>
    </div>

    <button type="button" class="kirim-ke-ganti" data-kirim-ke-buka>
      <?= $tujuan ? 'Ganti' : 'Pilih tujuan' ?>
    </button>
  </div>

  <!-- Daftar pilihan disembunyikan sampai ditekan: sebagian besar kunjungan
       memakai tujuan yang sudah benar, dan daftar yang selalu terbuka
       mendorong produk turun satu layar penuh. -->
  <div class="kirim-ke-panel" id="kirimKePanel" hidden>

    <button type="button" class="kirim-ke-opsi" data-gps>
      <strong>Gunakan lokasi saya</strong>
      <em>Deteksi lewat GPS perangkat</em>
    </button>

    <?php if ($alamat_saya): ?>
      <?php foreach ($alamat_saya as $a): ?>
        <?php $aktif = ! empty($tujuan['alamat_id']) && (int) $tujuan['alamat_id'] === (int) $a['id']; ?>
        <?= form_open('location/tujuan/' . (int) $a['id'], array('class' => 'kirim-ke-form')) ?>
          <input type="hidden" name="balik" value="<?= html_escape(uri_string() ?: 'shop') ?>">
          <button type="submit" class="kirim-ke-opsi <?= $aktif ? 'is-aktif' : '' ?>">
            <strong>
              <?= html_escape($a['label']) ?>
              <?php if ($a['is_primary']): ?><span>Utama</span><?php endif; ?>
            </strong>
            <em>
              <?= html_escape($a['recipient_name']) ?> &middot;
              <?= html_escape($a['district_name']) ?>, <?= html_escape($a['regency_name']) ?>
            </em>
          </button>
        <?= form_close() ?>
      <?php endforeach; ?>

      <a class="kirim-ke-opsi is-tambah" href="<?= site_url('akun/alamat_form') ?>">
        + Tambah alamat
      </a>
    <?php else: ?>
      <p class="kirim-ke-kosong">
        <?php if ($this->auth_lib->id()): ?>
          Simpan alamat tujuan supaya bisa dipilih langsung di sini.
          <a href="<?= site_url('akun/alamat_form') ?>">Tambah alamat</a>
        <?php else: ?>
          <!-- Tamu tidak dipaksa mendaftar: GPS di atas sudah cukup untuk
               mengurutkan katalog, dan alamat tujuan tetap bisa diketik
               saat checkout. -->
          <a href="<?= site_url('auth/login') ?>">Masuk</a> untuk menyimpan
          beberapa alamat tujuan.
        <?php endif; ?>
      </p>
    <?php endif; ?>

    <?php if ($tujuan): ?>
      <a class="kirim-ke-hapus" href="<?= site_url('location/lupakan_titik') ?>">Hapus tujuan</a>
    <?php endif; ?>

    <p class="gps-info" id="gpsInfo" aria-live="polite"></p>
  </div>
</div>

<script>
  (function () {
    var b = document.querySelector('[data-kirim-ke-buka]');
    var p = document.getElementById('kirimKePanel');
    if (!b || !p) { return; }

    b.addEventListener('click', function () {
      p.hidden = !p.hidden;
      b.textContent = p.hidden ? b.dataset.tutup || 'Ganti' : 'Tutup';
    });
    b.dataset.tutup = b.textContent;
  })();

  window.GPS = { simpan: '<?= site_url('location/titik') ?>' };
  window.CSRF = window.CSRF || {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= aset('assets/js/gps.js') ?>"></script>
