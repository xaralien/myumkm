<!-- application/views/admin/v_dashboard.php -->
<div class="panel-judul">
  <div>
    <h1>Ringkasan</h1>
    <p class="hint">Keadaan marketplace hari ini.</p>
  </div>
  <a href="<?= site_url('admin/categories/form') ?>" class="btn btn-primary">Tambah kategori</a>
</div>

<div class="adm-angka">
  <div class="adm-kartu">
    <span class="adm-nilai"><?= (int) $angka['perlu_diproses'] ?></span>
    <span class="adm-label">Pesanan lunas belum diproses</span>
    <?php if ($angka['perlu_diproses'] > 0): ?>
      <!-- Pembelinya sudah membayar dan sedang menunggu - ini yang paling
           perlu ditindaklanjuti admin ke penjualnya. -->
      <span class="adm-catatan is-warn">Pembeli sudah membayar</span>
    <?php endif; ?>
  </div>
  <div class="adm-kartu">
    <span class="adm-nilai"><?= (int) $angka['toko_menunggu'] ?></span>
    <span class="adm-label">Toko menunggu persetujuan</span>
  </div>
  <div class="adm-kartu">
    <span class="adm-nilai"><?= (int) $angka['toko_aktif'] ?></span>
    <span class="adm-label">Toko aktif</span>
  </div>
  <div class="adm-kartu">
    <span class="adm-nilai"><?= (int) $angka['produk_aktif'] ?></span>
    <span class="adm-label">Produk aktif</span>
  </div>
  <div class="adm-kartu">
    <span class="adm-nilai"><?= (int) $angka['pesanan_hari_ini'] ?></span>
    <span class="adm-label">Pesanan hari ini</span>
  </div>
  <div class="adm-kartu">
    <span class="adm-nilai"><?= rupiah($omzet_bulan) ?></span>
    <span class="adm-label">Omzet bulan ini</span>
    <span class="adm-catatan">Hanya pesanan lunas</span>
  </div>
</div>

<?php if ($menunggu): ?>
  <div class="panel-bagian">
    <h2 class="panel-sub">Toko menunggu persetujuan</h2>
    <div class="adm-tabel">
      <?php foreach ($menunggu as $m): ?>
        <div class="adm-baris">
          <div>
            <strong><?= html_escape($m['name']) ?></strong>
            <em>
              <?= html_escape($m['pemilik'] ?: '-') ?> &middot; <?= html_escape($m['email'] ?: '-') ?>
              <?php if ($m['district_name']): ?>
                &middot; <?= html_escape($m['district_name']) ?>, <?= html_escape($m['regency_name']) ?>
              <?php endif; ?>
            </em>
          </div>
          <div class="adm-aksi">
            <span class="hint"><?= tgl_id(substr($m['created_at'], 0, 10)) ?></span>
            <a href="<?= site_url('admin/stores/form/' . $m['id']) ?>" class="btn btn-black-hover-outline btn-sm">Periksa</a>
            <?= form_open('admin/stores/toggle/' . $m['id'], array('class' => 'inline-form')) ?>
              <button type="submit" class="btn btn-primary btn-sm">Setujui</button>
            <?= form_close() ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div class="panel-bagian">
  <h2 class="panel-sub">Pesanan terbaru</h2>
  <?php if (! $pesanan): ?>
    <p class="hint">Belum ada pesanan.</p>
  <?php else: ?>
    <div class="adm-tabel">
      <?php foreach ($pesanan as $o): ?>
        <div class="adm-baris">
          <div>
            <strong><?= html_escape($o['order_number']) ?></strong>
            <em><?= html_escape($o['store_name'] ?: 'Toko dihapus') ?> &middot;
              <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></em>
          </div>
          <div class="adm-aksi">
            <span class="badge-status <?= $o['payment_status'] === 'paid' ? 'is-ok' : 'is-wait' ?>">
              <?= label_bayar($o['payment_status']) ?>
            </span>
            <span class="badge-status"><?= label_status($o['order_status']) ?></span>
            <strong><?= rupiah($o['total']) ?></strong>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
