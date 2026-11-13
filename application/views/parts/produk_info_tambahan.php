<?php
/* Bagian tambahan di halaman produk (desktop & HP): lencana, favorit,
   bagikan. Satu berkas untuk dua tampilan supaya keduanya tidak berbeda.
   Data dari Produk::tampil() -> $ekstra. */
$e = isset($ekstra) ? $ekstra : array();
?>
<?php $this->load->view('parts/produk_lencana', array('p' => $p, 'verif' => isset($e['verif']) ? $e['verif'] : array())); ?>

<div class="produk-ekstra">
  <?php $this->load->view('parts/tombol_favorit', array('produk_id' => $p['id'], 'aktif' => ! empty($e['favorit']), 'kecil' => TRUE)); ?>
  <?php $this->load->view('parts/bagikan', array(
      'teks' => $p['name'] . ' - ' . rupiah($p['price']) . ' dari ' . nama_toko($p['store_name'], $p['store_district']) . ' (' . $p['store_regency'] . ')',
      'url'  => site_url('produk/' . $p['store_slug'] . '/' . $p['slug']),
  )); ?>
  <?php $this->load->view('parts/tombol_laporkan', array('jenis' => 'produk', 'objek_id' => $p['id'])); ?>
</div>

<?php if ( ! empty($e['luar_jangkauan'])): ?>
  <div class="alert-box alert-warn mb-3 jangkauan-luar">
    <strong>Tidak mengirim ke <?= html_escape($this->location_lib->label()) ?>.</strong>
    <?= html_escape($e['luar_jangkauan']) ?>
    <button type="button" class="link-btn" data-gps>Ganti lokasi</button>
  </div>
<?php endif; ?>

<?php if ( ! empty($e['libur'])): ?>
  <div class="alert-box alert-warn mb-3">
    Toko sedang libur sampai <strong><?= tgl_id($e['libur']) ?></strong>.
    <?= ! empty($p['is_preorder']) ? 'Pre-order tetap bisa, dikirim setelah toko buka.' : 'Pesanan bisa dibuat lagi setelah toko buka.' ?>
  </div>
<?php endif; ?>

<?php if (empty($e['libur']) && ! empty($e['kuota_penuh'])): ?>
  <div class="alert-box alert-warn mb-3">
    Kuota pesanan toko ini untuk hari ini sudah penuh. Pesanan bisa dibuat lagi besok.
  </div>
<?php endif; ?>

<?php if ( ! empty($p['is_preorder'])): ?>
  <div class="po-info mb-3">
    <strong>Pre-order</strong> &mdash; dibuat setelah dipesan, siap dikirim
    <?= (int) $p['po_hari'] ?> hari setelah pemesanan. Pilih tanggal kirimnya saat checkout.
    <?php if ((int) $p['po_dp_persen'] > 0): ?>
      Cukup bayar uang muka <strong><?= (int) $p['po_dp_persen'] ?>%</strong> dulu, sisanya sebelum dikirim.
    <?php endif; ?>
    <?php if ($p['po_kuota_harian'] !== NULL): ?>
      <br><small>Kuota <?= (int) $p['po_kuota_harian'] ?> per hari.</small>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ( ! empty($e['grosir'])): ?>
  <div class="grosir-tabel mb-3">
    <strong>Harga grosir</strong>
    <table>
      <tr><td>1 &ndash; <?= (int) $e['grosir'][0]['min_qty'] - 1 ?></td><td><?= rupiah($p['price']) ?> / pcs</td></tr>
      <?php foreach ($e['grosir'] as $t): ?>
        <tr><td>&ge; <?= (int) $t['min_qty'] ?></td><td><strong><?= rupiah($t['harga']) ?></strong> / pcs</td></tr>
      <?php endforeach; ?>
    </table>
    <small class="hint">Harga otomatis turun di keranjang sesuai jumlah.</small>
  </div>
<?php endif; ?>
