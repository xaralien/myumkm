<!-- application/views/akun/v_favorit.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Favorit</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container">

    <nav class="fitur-tab mb-4">
      <a href="<?= site_url('favorit') ?>" class="<?= $tab === 'produk' ? 'aktif' : '' ?>">Produk disimpan (<?= count($favorit) ?>)</a>
      <a href="<?= site_url('favorit?tab=toko') ?>" class="<?= $tab === 'toko' ? 'aktif' : '' ?>">Toko diikuti (<?= count($toko) ?>)</a>
    </nav>

    <?php if ($tab === 'produk'): ?>
      <?php if ( ! $favorit): ?>
        <div class="fitur-kosong">
          <p class="mb-3">Belum ada produk favorit. Tekan tombol ♥ di halaman produk untuk menyimpannya di sini.</p>
          <a href="<?= site_url('shop') ?>" class="btn btn-primary">Jelajahi produk</a>
        </div>
      <?php else: ?>
        <div class="fitur-grid">
          <?php foreach ($favorit as $p): ?>
            <div class="fav-sel">
              <?php $this->load->view('parts/kartu_produk', array('p' => $p, 'redup' => ! $p['tersedia'])); ?>
              <div class="fav-sel-bawah">
                <?php if ( ! $p['tersedia']): ?><span class="lencana lencana-merah">Tidak tersedia</span><?php endif; ?>
                <?php $this->load->view('parts/tombol_favorit', array('produk_id' => $p['id'], 'aktif' => TRUE, 'kecil' => FALSE)); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <?php if ( ! $toko): ?>
        <div class="fitur-kosong">
          <p class="mb-0">Belum mengikuti toko mana pun. Tekan <strong>+ Ikuti toko</strong> di halaman toko langgananmu.</p>
        </div>
      <?php else: ?>
        <div class="fitur-grid mb-5">
          <?php foreach ($toko as $t): ?>
            <div class="fitur-kartu">
              <h3><a href="<?= site_url('toko/' . $t['slug']) ?>"><?= html_escape($t['name']) ?></a></h3>
              <p class="hint mb-2"><?= html_escape($t['district_name']) ?>, <?= html_escape($t['regency_name']) ?> &middot; <?= (int) $t['jml_produk'] ?> produk</p>
              <?php $this->load->view('parts/tombol_ikuti', array('toko_id' => $t['id'], 'aktif' => TRUE, 'pengikut' => $this->pembeli_model->jumlah_pengikut($t['id']))); ?>
            </div>
          <?php endforeach; ?>
        </div>
        <?php $this->load->view('parts/deret_produk', array('judul' => 'Baru dari toko langgananmu', 'produk' => $baru, 'sub' => NULL, 'tautan' => NULL)); ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
