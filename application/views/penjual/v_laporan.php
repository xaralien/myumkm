<!-- application/views/penjual/v_laporan.php -->
<?php
  $t = $jual['total'];
  $bln = array('', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des');
  $data = array();
  foreach ($jual['harian'] as $h) {
    $ts = strtotime($h['tgl']);
    $data[] = array('label' => date('j', $ts) . ' ' . $bln[(int) date('n', $ts)], 'nilai' => $h['omzet'],
                    'teks' => tgl_id($h['tgl']) . ': ' . rupiah($h['omzet']) . ' (' . $h['pesanan'] . ' pesanan)');
  }
  $jam = array();
  foreach ($jual['jam'] as $j => $n) { $jam[] = array('label' => sprintf('%02d', $j), 'nilai' => $n, 'teks' => sprintf('Pukul %02d.00-%02d.59', $j, $j) . ': ' . $n . ' pesanan'); }
?>
<div class="panel-judul">
  <div>
    <h1>Laporan penjualan</h1>
    <p class="hint">Mana yang laku, kapan ramai, dan dari mana pembelimu &mdash; bahan untuk menentukan stok dan promosi.</p>
  </div>
  <div class="pintas-baris">
    <a href="<?= site_url('seller/laporan/ekspor?periode=' . $periode['kode']) ?>" class="btn btn-black-hover-outline">⬇ Excel</a>
    <a href="<?= site_url('seller/laporan/kur') ?>" target="_blank" class="btn btn-primary">🏦 Laporan untuk KUR</a>
  </div>
</div>

<nav class="fitur-saring mb-3">
  <?php foreach ($pilihan as $k => $l): ?>
    <a href="<?= site_url('seller/laporan?periode=' . $k) ?>" class="<?= $periode['kode'] === (string) $k ? 'aktif' : '' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</nav>

<div class="adm-angka">
  <div class="adm-kartu"><span class="adm-nilai"><?= rupiah($t['omzet']) ?></span><span class="adm-label">Omzet barang</span>
    <span class="adm-catatan">Diterima toko <?= rupiah($t['bersih']) ?></span></div>
  <div class="adm-kartu"><span class="adm-nilai"><?= (int) $t['pesanan'] ?></span><span class="adm-label">Pesanan lunas</span>
    <span class="adm-catatan">Rata-rata <?= $t['pesanan'] ? rupiah(round($t['omzet'] / $t['pesanan'])) : rupiah(0) ?></span></div>
  <div class="adm-kartu"><span class="adm-nilai"><?= (int) $t['pembeli'] ?></span><span class="adm-label">Pembeli</span>
    <span class="adm-catatan"><?= (int) $t['pembeli_ulang'] ?> membeli lebih dari sekali</span></div>
</div>

<?php $this->load->view('parts/grafik_batang', array('judul' => 'Omzet per hari', 'data' => $data, 'format' => 'rupiah')); ?>

<div class="row">
  <div class="col-lg-6 mb-3">
    <div class="fitur-kartu h-100">
      <h3>Produk terlaris</h3>
      <?php if ( ! $jual['produk']): ?><p class="hint mb-0">Belum ada penjualan.</p><?php endif; ?>
      <?php $maks = $jual['produk'] ? max(array_column($jual['produk'], 'qty')) : 0; ?>
      <?php foreach ($jual['produk'] as $x): ?>
        <div class="batang-baris">
          <span><?= html_escape($x['product_name']) ?></span>
          <span class="batang"><i style="width: <?= $maks ? round($x['qty'] / $maks * 100) : 0 ?>%"></i></span>
          <strong><?= (int) $x['qty'] ?> pcs</strong>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="col-lg-6 mb-3">
    <div class="fitur-kartu h-100">
      <h3>Kota pembeli terbanyak</h3>
      <?php if ( ! $jual['kota']): ?><p class="hint mb-0">Belum ada penjualan.</p><?php endif; ?>
      <?php $maks = $jual['kota'] ? max(array_column($jual['kota'], 'n')) : 0; ?>
      <?php foreach ($jual['kota'] as $k): ?>
        <div class="batang-baris">
          <span><?= html_escape($k['kota']) ?></span>
          <span class="batang"><i style="width: <?= $maks ? round($k['n'] / $maks * 100) : 0 ?>%"></i></span>
          <strong><?= (int) $k['n'] ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php $this->load->view('parts/grafik_batang', array('judul' => 'Jam pesanan masuk', 'data' => $jam, 'format' => 'angka')); ?>
<p class="hint">Tips: unggah produk baru dan balas chat di jam ramai supaya pembeli langsung melihatnya.</p>
