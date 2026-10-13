<!-- application/views/admin/v_akademi.php -->
<div class="panel-judul">
  <div>
    <h1>Akademi UMKM</h1>
    <p class="hint"><?= (int) $toko_belajar ?> toko sudah menyelesaikan minimal satu modul.</p>
  </div>
  <div class="pintas-baris">
    <a href="<?= site_url('admin/akademi/modul') ?>" class="btn btn-primary">+ Modul</a>
    <a href="<?= site_url('admin/akademi/jadwal') ?>" class="btn btn-black-hover-outline">+ Jadwal kelas</a>
  </div>
</div>

<h2 class="panel-sub">Modul</h2>
<div class="adm-tabel mb-4">
  <?php foreach ($modul as $m): ?>
    <div class="adm-baris">
      <div><strong><?= html_escape($m['judul']) ?></strong>
        <em><?= html_escape($m['kategori']) ?> &middot; urutan <?= (int) $m['urutan'] ?> &middot; <?= (int) $m['jml_soal'] ?> soal &middot; <?= (int) $m['lulus'] ?> toko lulus<?= $m['video_url'] ? ' &middot; ▶ video' : '' ?></em></div>
      <div class="adm-aksi">
        <span class="badge-status <?= $m['is_active'] ? 'is-ok' : '' ?>"><?= $m['is_active'] ? 'Tampil' : 'Disembunyikan' ?></span>
        <a href="<?= site_url('admin/akademi/modul/' . (int) $m['id']) ?>" class="btn btn-sm btn-black-hover-outline">Ubah</a>
        <?= form_open('admin/akademi/modul_tukar/' . (int) $m['id'], array('class' => 'inline-form')) ?>
          <button type="submit" class="btn btn-sm btn-black-hover-outline"><?= $m['is_active'] ? 'Sembunyikan' : 'Tampilkan' ?></button>
        <?= form_close() ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<h2 class="panel-sub">Jadwal kelas &amp; konsultasi</h2>
<?php if ( ! $jadwal): ?><div class="fitur-kosong">Belum ada jadwal.</div><?php endif; ?>
<div class="adm-tabel">
  <?php foreach ($jadwal as $j): ?>
    <div class="adm-baris">
      <div><strong><?= html_escape($j['judul']) ?></strong>
        <em><?= $j['jenis'] === 'kelas' ? 'Kelas' : 'Konsultasi' ?> &middot; <?= tgl_id(substr($j['waktu'], 0, 10)) ?> <?= substr($j['waktu'], 11, 5) ?>
          &middot; <?= (int) $j['peserta'] ?><?= $j['kuota'] !== NULL ? '/' . (int) $j['kuota'] : '' ?> peserta</em></div>
      <div class="adm-aksi">
        <a href="<?= site_url('admin/akademi/jadwal/' . (int) $j['id']) ?>" class="btn btn-sm btn-black-hover-outline">Ubah &amp; peserta</a>
        <?= form_open('admin/akademi/jadwal_hapus/' . (int) $j['id'], array('class' => 'inline-form')) ?>
          <button type="submit" class="btn btn-sm btn-black-hover-outline" onclick="return confirm('Hapus jadwal ini?')">Hapus</button>
        <?= form_close() ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
