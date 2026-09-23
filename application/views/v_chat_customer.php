<!-- application/views/v_chat_customer.php - halaman percakapan penuh untuk pembeli.
     Dipakai untuk tautan langsung (misalnya dari notifikasi); di halaman
     pesanan, pembeli memakai widget melayang. -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Percakapan pesanan</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-lg-8">

    <div class="form-card">
      <div class="chat-kepala">
        <div>
          <p class="hint mb-1">Nomor pesanan</p>
          <p class="order-number mb-0"><?= html_escape($order['order_number']) ?></p>
        </div>
        <?php if ($toko): ?><p class="hint mb-0"><?= html_escape($toko['name']) ?></p><?php endif; ?>
      </div>
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
      <input type="text" class="form-control" id="chatIsi" maxlength="1000"
             placeholder="Tulis pesan untuk toko..." autocomplete="off" aria-label="Pesan">
      <button type="submit" class="btn btn-primary">Kirim</button>
    </form>
    <p class="chat-info" id="chatInfo" aria-live="polite"></p>

    <div class="text-center mt-4">
      <a href="<?= site_url('checkout/done/' . $order['order_number'] . '/' . $order['access_token']) ?>"
         class="btn btn-black-hover-outline">Lihat detail pesanan</a>
    </div>

  </div></div></div>
</div>

<script>
  window.CHAT = {
    baseUrl: <?= json_encode(rtrim(site_url(), '/')) ?>,
    nomor:   <?= json_encode($order['order_number']) ?>,
    token:   <?= json_encode($order['access_token']) ?>,
    sisi:    'customer',
    sejak:   <?= $pesan ? (int) end($pesan)['id'] : 0 ?>,
    gambar:  <?= json_encode(base_url('upload/produk/')) ?>
  };
  window.CSRF = {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= base_url('assets/js/chat.js') ?>"></script>
