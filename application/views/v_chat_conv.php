<!-- application/views/v_chat_conv.php - percakapan dengan toko (tanpa pesanan) -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1><?= html_escape($conv['store_name']) ?></h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-lg-8">

    <div class="form-card">
      <div class="chat-kepala">
        <div>
          <p class="hint mb-1">Percakapan dengan toko</p>
          <p class="order-number mb-0"><?= html_escape($conv['store_name']) ?></p>
        </div>
        <a href="<?= site_url('shop') ?>?store=<?= (int) $conv['store_id'] ?>"
           class="btn btn-black-hover-outline btn-sm">Lihat toko</a>
      </div>
    </div>

    <div class="chat-kotak" id="chatKotak">
      <?php foreach ($pesan as $m): ?>
        <?php $this->load->view('v_chat_bubble', array('m' => $m, 'sisi' => 'customer')); ?>
      <?php endforeach; ?>
      <?php if (! $pesan): ?>
        <p class="hint text-center" id="chatKosong">Tanyakan apa saja tentang produk toko ini.</p>
      <?php endif; ?>
    </div>

    <form class="chat-form" id="chatForm">
      <input type="text" class="form-control" id="chatIsi" maxlength="1000"
             placeholder="Tulis pesan untuk toko..." autocomplete="off" aria-label="Pesan">
      <button type="submit" class="btn btn-primary">Kirim</button>
    </form>
    <p class="chat-info" id="chatInfo" aria-live="polite"></p>

  </div></div></div>
</div>

<script>
  window.CHAT = {
    baseUrl: <?= json_encode(rtrim(site_url(), '/')) ?>,
    conv:    <?= (int) $conv['id'] ?>,
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
