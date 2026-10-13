<!-- application/views/admin/v_analitik.php -->
<?php
  $data = array();
  foreach ($harian as $h) { $data[] = array('label' => date('j/n', strtotime($h['tgl'])), 'nilai' => $h['gmv'], 'teks' => tgl_id($h['tgl']) . ': ' . rupiah($h['gmv']) . ' (' . $h['n'] . ' pesanan)'); }
  $batang = function ($baris, $kunci_nama, $kunci_nilai, $fmt = 'rupiah') {
    $maks = $baris ? max(array_column($baris, $kunci_nilai)) : 0;
    foreach ($baris as $b) {
      echo '<div class="batang-baris"><span>' . html_escape($b[$kunci_nama] ?: '-') . '</span><span class="batang"><i style="width:'
        . ($maks ? round($b[$kunci_nilai] / $maks * 100) : 0) . '%"></i></span><strong>'
        . ($fmt === 'rupiah' ? rupiah($b[$kunci_nilai]) : (int) $b[$kunci_nilai]) . '</strong></div>';
    }
    if ( ! $baris) { echo '<p class="hint mb-0">Belum ada data.</p>'; }
  };
?>
<div class="panel-judul"><div><h1>Analitik</h1><p class="hint">Pertumbuhan marketplace dan sebaran UMKM. Unduh Excel untuk laporan ke mitra.</p></div>
  <div class="pintas-baris"><a href="<?= site_url('admin/analitik/ekspor?periode=' . $periode['kode']) ?>" class="btn btn-black-hover-outline">⬇ Excel</a>
    <a href="<?= site_url('admin/peta') ?>" class="btn btn-primary">🗺 Peta sebaran</a></div></div>
<nav class="fitur-saring mb-3">
  <?php foreach ($pilihan as $k => $l): ?><a href="<?= site_url('admin/analitik?periode=' . $k) ?>" class="<?= $periode['kode'] === (string) $k ? 'aktif' : '' ?>"><?= $l ?></a><?php endforeach; ?>
</nav>
<div class="adm-angka">
  <div class="adm-kartu"><span class="adm-nilai"><?= rupiah($r['gmv']) ?></span><span class="adm-label">GMV</span><span class="adm-catatan"><?= (int) $r['pesanan'] ?> pesanan &middot; rata-rata <?= rupiah($r['aov']) ?></span></div>
  <div class="adm-kartu"><span class="adm-nilai"><?= (int) $r['toko_jual'] ?> / <?= (int) $r['toko_aktif'] ?></span><span class="adm-label">Toko menjual / aktif</span><span class="adm-catatan"><?= (int) $r['toko_baru'] ?> toko baru &middot; <?= (int) $r['akun_baru'] ?> akun baru</span></div>
  <div class="adm-kartu"><span class="adm-nilai"><?= rupiah($r['bersih_platform']) ?></span><span class="adm-label">Pendapatan bersih platform</span><span class="adm-catatan">Komisi <?= rupiah($r['komisi']) ?> &minus; promo <?= rupiah($r['biaya_promo']) ?></span></div>
</div>
<?php $this->load->view('parts/grafik_batang', array('judul' => 'GMV per hari', 'data' => $data, 'format' => 'rupiah')); ?>
<div class="row">
  <div class="col-lg-4 mb-3"><div class="fitur-kartu h-100"><h3>Per kategori</h3><?php $batang($kategori, 'nama', 'nilai'); ?></div></div>
  <div class="col-lg-4 mb-3"><div class="fitur-kartu h-100"><h3>Per kota/kabupaten toko</h3><?php $batang($wilayah, 'nama', 'nilai'); ?></div></div>
  <div class="col-lg-4 mb-3"><div class="fitur-kartu h-100"><h3>Toko teratas</h3><?php $batang($toko, 'name', 'nilai'); ?></div></div>
</div>
