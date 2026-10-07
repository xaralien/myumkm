<!-- application/views/v_alamat.php - daftar alamat tersimpan -->
<div class="untree_co-section">
  <div class="container">

    <div class="alm-kepala">
      <div>
        <h1 class="nt-judul">Alamat saya</h1>
        <p class="hint">
          Alamat tersimpan dipilih saat checkout, jadi tidak perlu mengetik ulang.
          <?= count($daftar) ?> dari <?= (int) $maks ?> alamat terpakai.
        </p>
      </div>
      <?php if (count($daftar) < $maks): ?>
        <a href="<?= site_url('akun/alamat_form') ?>" class="btn btn-primary">Tambah alamat</a>
      <?php endif; ?>
    </div>

    <?php if (! $daftar): ?>
      <div class="empty-state">
        <p class="empty-title">Belum ada alamat tersimpan</p>
        <p class="hint">Simpan alamat sekali, lalu tinggal pilih setiap kali belanja.</p>
        <a href="<?= site_url('akun/alamat_form') ?>" class="btn btn-primary mt-3">Tambah alamat pertama</a>
      </div>
    <?php else: ?>

      <div class="alm-daftar">
        <?php foreach ($daftar as $a): ?>
          <article class="alm-kartu <?= $a['is_primary'] ? 'is-utama' : '' ?>">
            <div class="alm-kartu-atas">
              <h2><?= html_escape($a['label']) ?></h2>
              <?php if ($a['is_primary']): ?>
                <span class="alm-tanda">Alamat utama</span>
              <?php endif; ?>
            </div>

            <p class="alm-penerima">
              <strong><?= html_escape($a['recipient_name']) ?></strong>
              <span><?= html_escape($a['recipient_phone']) ?></span>
            </p>

            <p class="alm-isi">
              <?= html_escape($a['address']) ?><br>
              <?= html_escape($a['district_name']) ?>, <?= html_escape($a['regency_name']) ?>,
              <?= html_escape($a['province_name']) ?>
              <?php if ($a['postcode']): ?> <?= html_escape($a['postcode']) ?><?php endif; ?>
            </p>

            <?php if ($a['landmark']): ?>
              <!-- Patokan ditampilkan terpisah: ini yang dibaca kurir saat
                   alamat formalnya tidak cukup menolong. -->
              <p class="alm-patokan">Patokan: <?= html_escape($a['landmark']) ?></p>
            <?php endif; ?>

            <div class="alm-aksi">
              <a href="<?= site_url('akun/alamat_form/' . (int) $a['id']) ?>">Ubah</a>

              <?php if (! $a['is_primary']): ?>
                <?= form_open('akun/alamat_utama/' . (int) $a['id'], array('class' => 'inline-form')) ?>
                  <button type="submit" class="link-btn">Jadikan utama</button>
                <?= form_close() ?>
              <?php endif; ?>

              <?= form_open('akun/alamat_hapus/' . (int) $a['id'], array('class' => 'inline-form')) ?>
                <button type="submit" class="link-btn is-hapus"
                        onclick="return confirm('Hapus alamat ini? Pesanan lama tidak terpengaruh.');">
                  Hapus
                </button>
              <?= form_close() ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
