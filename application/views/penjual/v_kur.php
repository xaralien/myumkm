<?php
/* Laporan keuangan usaha mikro untuk pengajuan KUR / pinjaman koperasi.
   Halaman cetak mandiri - simpan sebagai PDF lewat dialog cetak browser. */
$bln = array('', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
$nama_bulan = function ($t) use ($bln) { return $bln[(int) date('n', strtotime($t))] . ' ' . date('Y', strtotime($t)); };
$jml = count($bulan);
$rata_pend = $jml ? round(array_sum(array_column($bulan, 'pendapatan')) / $jml) : 0;
$rata_laba = $jml ? round(array_sum(array_column($bulan, 'laba')) / $jml) : 0;
$jenis_dok = array('nib' => 'NIB', 'pirt' => 'P-IRT', 'halal' => 'Sertifikat Halal', 'ktp' => 'KTP pemilik', 'npwp' => 'NPWP', 'lainnya' => 'Dokumen lain');
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Laporan Keuangan <?= html_escape($toko['name']) ?></title>
<style>
  body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; background: #e9eef2; font-size: 13px; }
  .alat { background: #22466C; color: #fff; padding: 10px 16px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; position: sticky; top: 0; }
  .alat a, .alat button { background: #fff; color: #22466C; border: 0; border-radius: 8px; padding: 7px 12px; font-weight: 700; text-decoration: none; cursor: pointer; }
  .alat a.aktif { background: #FDD977; }
  .kertas { background: #fff; width: 210mm; min-height: 297mm; margin: 16px auto; padding: 16mm 15mm; box-sizing: border-box; }
  h1 { font-size: 20px; margin: 0 0 2px; } h2 { font-size: 14px; margin: 18px 0 6px; border-bottom: 2px solid #22466C; padding-bottom: 3px; }
  table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #bbb; padding: 5px 6px; text-align: left; }
  th { background: #F2F7FA; } td.k, th.k { text-align: right; } .muted { color: #555; }
  .profil td { border: 0; padding: 2px 0; } .profil td:first-child { width: 38%; color: #555; }
  .ttd { margin-top: 36px; display: flex; justify-content: flex-end; } .ttd div { text-align: center; width: 60mm; }
  @media print { body { background: #fff; } .alat { display: none; } .kertas { margin: 0; width: auto; min-height: 0; padding: 12mm; } @page { size: A4; margin: 0; } }
</style></head><body>
<div class="alat">
  <strong>Laporan untuk KUR</strong> <span>Periode:</span>
  <?php foreach (array(3, 6, 12) as $b): ?><a href="?bulan=<?= $b ?>" class="<?= $n === $b ? 'aktif' : '' ?>"><?= $b ?> bulan</a><?php endforeach; ?>
  <button type="button" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
  <a href="<?= site_url('seller/laporan') ?>">Kembali</a>
</div>
<div class="kertas">
  <h1>Laporan Keuangan Usaha</h1>
  <div class="muted">Periode <?= $nama_bulan($bulan[0]['bulan']) ?> &ndash; <?= $nama_bulan(end($bulan)['bulan']) ?> &middot; dibuat <?= tgl_id(date('Y-m-d')) ?> dari data <?= html_escape($brand) ?></div>

  <h2>1. Profil usaha</h2>
  <table class="profil">
    <tr><td>Nama usaha</td><td><strong><?= html_escape($toko['name']) ?></strong></td></tr>
    <tr><td>Pemilik</td><td><?= html_escape($toko['pemilik']) ?></td></tr>
    <tr><td>Alamat</td><td><?= html_escape($toko['address']) ?>, Kec. <?= html_escape($toko['district_name']) ?>, <?= html_escape($toko['regency_name']) ?>, <?= html_escape($toko['province_name']) ?></td></tr>
    <tr><td>Telepon / WhatsApp</td><td><?= html_escape($toko['phone']) ?></td></tr>
    <tr><td>Mulai berjualan online</td><td><?= tgl_id(substr($toko['created_at'], 0, 10)) ?></td></tr>
    <tr><td>Produk aktif</td><td><?= (int) $produk_aktif ?> produk</td></tr>
    <tr><td>Rekening</td><td><?= $toko['bank_nama'] ? html_escape($toko['bank_nama'] . ' ' . $toko['bank_nomor'] . ' a.n. ' . $toko['bank_atas_nama']) : '-' ?></td></tr>
    <?php foreach ($dok as $d): ?>
      <tr><td><?= html_escape($jenis_dok[$d['jenis']] ?? $d['jenis']) ?></td><td><?= html_escape($d['nomor']) ?><?= $d['status'] === 'terverifikasi' ? ' (terverifikasi platform)' : '' ?></td></tr>
    <?php endforeach; ?>
  </table>

  <h2>2. Laba-rugi per bulan</h2>
  <table>
    <tr><th>Bulan</th><th class="k">Pesanan</th><th class="k">Penjualan online</th><th class="k">Penjualan lain</th><th class="k">Pengeluaran</th><th class="k">Laba bersih</th></tr>
    <?php foreach ($bulan as $b): ?>
      <tr><td><?= $nama_bulan($b['bulan']) ?></td><td class="k"><?= (int) $b['pesanan'] ?></td>
        <td class="k"><?= rupiah($b['marketplace']) ?></td><td class="k"><?= rupiah($b['catatan']) ?></td>
        <td class="k"><?= rupiah($b['pengeluaran']) ?></td><td class="k"><strong><?= $b['laba'] < 0 ? '-' : '' ?><?= rupiah(abs($b['laba'])) ?></strong></td></tr>
    <?php endforeach; ?>
    <tr><th>Total</th><th class="k"><?= array_sum(array_column($bulan, 'pesanan')) ?></th>
      <th class="k"><?= rupiah($total['marketplace']) ?></th><th class="k"><?= rupiah($total['catatan']) ?></th>
      <th class="k"><?= rupiah($total['pengeluaran']) ?></th><th class="k"><?= $total['laba'] < 0 ? '-' : '' ?><?= rupiah(abs($total['laba'])) ?></th></tr>
  </table>
  <p>Rata-rata pendapatan per bulan: <strong><?= rupiah($rata_pend) ?></strong> &middot; rata-rata laba per bulan: <strong><?= $rata_laba < 0 ? '-' : '' ?><?= rupiah(abs($rata_laba)) ?></strong></p>

  <h2>3. Rincian pengeluaran</h2>
  <?php if ($total['per_kategori']['keluar']): ?>
    <table>
      <tr><th>Kategori</th><th class="k">Jumlah</th><th class="k">Porsi</th></tr>
      <?php foreach ($total['per_kategori']['keluar'] as $k => $v): ?>
        <tr><td><?= html_escape($k) ?></td><td class="k"><?= rupiah($v) ?></td><td class="k"><?= $total['pengeluaran'] ? round($v / $total['pengeluaran'] * 100) : 0 ?>%</td></tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?><p class="muted">Belum ada pengeluaran yang dicatat di Pembukuan.</p><?php endif; ?>

  <h2>4. Indikator penjualan</h2>
  <table class="profil">
    <tr><td>Jumlah pembeli</td><td><?= (int) $jual['total']['pembeli'] ?> orang (<?= (int) $jual['total']['pembeli_ulang'] ?> membeli lebih dari sekali)</td></tr>
    <tr><td>Produk terlaris</td><td><?= html_escape(implode(', ', array_slice(array_column($jual['produk'], 'product_name'), 0, 3)) ?: '-') ?></td></tr>
    <tr><td>Wilayah pembeli utama</td><td><?= html_escape(implode(', ', array_column($jual['kota'], 'kota')) ?: '-') ?></td></tr>
    <?php if ($total['modal']): ?><tr><td>Modal tambahan disetor</td><td><?= rupiah($total['modal']) ?> (tidak dihitung sebagai pendapatan)</td></tr><?php endif; ?>
  </table>

  <p class="muted" style="margin-top:14px">Penjualan online berasal dari transaksi tercatat di <?= html_escape($brand) ?> (sudah dipotong komisi platform).
    Penjualan lain dan pengeluaran dicatat sendiri oleh pemilik usaha di menu Pembukuan.</p>

  <div class="ttd"><div><?= html_escape($toko['regency_name']) ?>, <?= tgl_id(date('Y-m-d')) ?><br>Pemilik usaha,<br><br><br><br><strong><?= html_escape($toko['pemilik']) ?></strong></div></div>
</div>
</body></html>
