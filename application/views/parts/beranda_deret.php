<?php
/* CATATAN: CodeIgniter MENYIMPAN variabel view antar-pemanggilan
   load->view(), jadi setiap kunci opsional ('sub', 'tautan') harus selalu
   dikirim - kalau tidak, nilai dari deret sebelumnya ikut terbawa.

   Deret tambahan di beranda: rekomendasi, toko langganan, terlaris.
   Dipakai tampilan desktop dan HP. Data dari Home::index() -> $deret. */
if (empty($deret)) { return; }
?>
<div class="deret-wrap nt-wrap">
  <?php if ( ! empty($deret['rekomendasi']['produk'])): ?>
    <?php $this->load->view('parts/deret_produk', array(
        'judul'  => 'Rekomendasi untukmu',
        'sub'    => $deret['rekomendasi']['alasan'] ? 'Karena kamu suka ' . $deret['rekomendasi']['alasan'] : NULL,
        'produk' => $deret['rekomendasi']['produk'],
        'tautan' => NULL,
    )); ?>
  <?php endif; ?>

  <?php $this->load->view('parts/deret_produk', array(
      'judul'  => 'Baru dari toko langgananmu',
      'sub'    => NULL,
      'produk' => isset($deret['langganan']) ? $deret['langganan'] : array(),
      'tautan' => site_url('favorit?tab=toko'),
  )); ?>

  <?php $this->load->view('parts/deret_produk', array(
      'judul'  => $lokasi ? 'Terlaris di sekitarmu' : 'Terlaris',
      'sub'    => NULL,
      'produk' => isset($deret['terlaris']) ? $deret['terlaris'] : array(),
      'tautan' => site_url('shop?sort=terlaris'),
  )); ?>
</div>
