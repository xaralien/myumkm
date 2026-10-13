<?php
/* Bagian tambahan di halaman produk (desktop & HP): lencana, favorit,
   bagikan. Satu berkas untuk dua tampilan supaya keduanya tidak berbeda.
   Data dari Produk::tampil() -> $ekstra. */
$e = isset($ekstra) ? $ekstra : array();
?>
<?php $this->load->view('parts/produk_lencana', array('p' => $p, 'verif' => isset($e['verif']) ? $e['verif'] : array())); ?>

<div class="produk-ekstra">
  <?php $this->load->view('parts/tombol_favorit', array('produk_id' => $p['id'], 'aktif' => ! empty($e['favorit']), 'kecil' => FALSE)); ?>
  <?php $this->load->view('parts/bagikan', array(
      'teks' => $p['name'] . ' - ' . rupiah($p['price']) . ' dari ' . $p['store_name'] . ' (' . $p['store_regency'] . ')',
      'url'  => site_url('produk/' . $p['store_slug'] . '/' . $p['slug']),
  )); ?>
</div>
