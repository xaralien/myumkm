<!-- application/views/admin/v_banner.php -->
<div class="panel-judul">
  <div><h1>Banner &amp; promo</h1>
    <p class="hint">Banner tampil di beranda sesuai jadwal. Halaman promo (/promo/...) berisi teks kampanye dan daftar produk pilihan.</p></div>
  <div class="pintas-baris">
    <a href="<?= site_url('admin/banner/form') ?>" class="btn btn-primary">+ Banner</a>
    <a href="<?= site_url('admin/banner/promo') ?>" class="btn btn-black-hover-outline">+ Halaman promo</a>
  </div>
</div>
<h2 class="panel-sub">Banner beranda</h2>
<?php if ( ! $banner): ?><div class="fitur-kosong mb-4">Belum ada banner.</div><?php endif; ?>
<div class="adm-tabel mb-4">
  <?php foreach ($banner as $b): $kini = date('Y-m-d'); $jalan = $b['is_active'] && ( ! $b['mulai'] || $b['mulai'] <= $kini) && ( ! $b['selesai'] || $b['selesai'] >= $kini); ?>
    <div class="adm-baris">
      <div><strong><?= html_escape($b['judul']) ?></strong>
        <em><?= html_escape($b['sub']) ?> &middot; <?= $b['mulai'] ? tgl_id($b['mulai']) : 'sekarang' ?> &ndash; <?= $b['selesai'] ? tgl_id($b['selesai']) : 'seterusnya' ?>
          <?= $b['tautan'] ? '&middot; ' . html_escape($b['tautan']) : '' ?></em></div>
      <div class="adm-aksi">
        <span class="badge-status <?= $jalan ? 'is-ok' : 'is-wait' ?>"><?= $jalan ? 'Tayang' : 'Tidak tayang' ?></span>
        <a href="<?= site_url('admin/banner/form/' . (int) $b['id']) ?>" class="btn btn-sm btn-black-hover-outline">Ubah</a>
        <?= form_open('admin/banner/hapus/' . (int) $b['id'], array('class' => 'inline-form')) ?>
          <button class="btn btn-sm btn-black-hover-outline" onclick="return confirm('Hapus banner?')">Hapus</button>
        <?= form_close() ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<h2 class="panel-sub">Halaman promo</h2>
<?php if ( ! $promo): ?><div class="fitur-kosong">Belum ada halaman promo.</div><?php endif; ?>
<div class="adm-tabel">
  <?php foreach ($promo as $p): ?>
    <div class="adm-baris">
      <div><strong><?= html_escape($p['judul']) ?></strong><em><a href="<?= site_url('promo/' . $p['slug']) ?>" target="_blank">/promo/<?= html_escape($p['slug']) ?></a>
        <?= $p['voucher'] ? '&middot; voucher ' . html_escape($p['voucher']) : '' ?></em></div>
      <div class="adm-aksi">
        <span class="badge-status <?= $p['is_active'] ? 'is-ok' : '' ?>"><?= $p['is_active'] ? 'Aktif' : 'Nonaktif' ?></span>
        <a href="<?= site_url('admin/banner/promo/' . (int) $p['id']) ?>" class="btn btn-sm btn-black-hover-outline">Ubah</a>
      </div>
    </div>
  <?php endforeach; ?>
</div>
