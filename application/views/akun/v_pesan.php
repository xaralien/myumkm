<!-- application/views/akun/v_pesan.php -->
<?php
  $label = array(
    'pending' => 'Menunggu diproses', 'confirmed' => 'Diproses penjual',
    'preparing' => 'Diproses penjual', 'delivering' => 'Dikirim',
    'delivered' => 'Selesai', 'cancelled' => 'Dibatalkan',
  );
  $url = function ($d) { return site_url('akun/pesan/' . rawurlencode($d['order_number'])); };
?>

<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Pesan</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container">

    <div class="inbox <?= $aktif ? 'is-buka' : '' ?>">

      <div class="inbox-kiri">
        <?php $this->load->view('parts/inbox_daftar', array(
            'daftar' => $daftar, 'aktif' => $aktif, 'url' => $url, 'label' => $label
        )); ?>
      </div>

      <div class="inbox-kanan">
        <?php if (! $aktif): ?>
          <div class="inbox-kosong-kanan">
            <p>Pilih satu percakapan untuk membacanya.</p>
          </div>
        <?php else: ?>

          <div class="inbox-kepala">
            <a href="<?= site_url('akun/pesan') ?>" class="inbox-balik" aria-label="Kembali ke daftar">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"></path></svg>
            </a>
            <div>
              <strong><?= html_escape($aktif['order_number']) ?></strong>
              <em><?= html_escape($label[$aktif['order_status']] ?? $aktif['order_status']) ?></em>
            </div>
            <a href="<?= site_url('checkout/done/' . $aktif['order_number'] . '/' . $aktif['access_token']) ?>"
               class="btn btn-black-hover-outline btn-sm">Detail pesanan</a>
          </div>

          <div class="chat-kotak" id="chatKotak">
            <?php foreach ($pesan as $m): ?>
              <?php $this->load->view('v_chat_bubble', array('m' => $m, 'sisi' => 'customer')); ?>
            <?php endforeach; ?>
            <?php if (! $pesan): ?>
              <p class="hint text-center" id="chatKosong">Belum ada pesan. Tanyakan apa saja soal pesananmu.</p>
            <?php endif; ?>
          </div>

          <form class="chat-form" id="chatForm">
            <input type="text" id="chatIsi" class="form-control" maxlength="1000"
                   placeholder="Tulis pesan untuk toko..." autocomplete="off" aria-label="Pesan">
            <button type="submit" class="btn btn-primary">Kirim</button>
          </form>
          <p class="chat-info" id="chatInfo" aria-live="polite"></p>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php if ($aktif): ?>
  <script>
    window.CHAT = {
      baseUrl: <?= json_encode(rtrim(site_url(), '/')) ?>,
      nomor:   <?= json_encode($aktif['order_number']) ?>,
      token:   <?= json_encode($aktif['access_token']) ?>,
      sisi:    'customer',
      sejak:   <?= $pesan ? (int) end($pesan)['id'] : 0 ?>,
      gambar:  <?= json_encode(base_url('upload/produk/')) ?>
    };
    window.CSRF = {
      name: '<?= $this->security->get_csrf_token_name() ?>',
      hash: '<?= $this->security->get_csrf_hash() ?>'
    };
  </script>
  <script src="<?= aset('assets/js/chat.js') ?>"></script>
<?php endif; ?>
