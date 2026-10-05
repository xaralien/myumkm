<!-- application/views/mobile/v_chat_conv.php - percakapan dengan toko, layar penuh -->
<div class="mb-chat">

  <header class="mb-chat-atas">
    <a href="<?= site_url('akun/pesan') ?>" class="mb-chat-balik" aria-label="Kembali">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"></path></svg>
    </a>

    <span class="mb-chat-avatar" aria-hidden="true">
      <?php if (! empty($conv['store_avatar'])): ?>
        <img src="<?= base_url('upload/avatar/' . $conv['store_avatar']) ?>" alt="">
      <?php else: ?>
        <?= html_escape(strtoupper(mb_substr($conv['store_name'], 0, 1))) ?>
      <?php endif; ?>
    </span>

    <div class="mb-chat-judul">
      <strong><?= html_escape($conv['store_name']) ?></strong>
      <em><?= $conv['order_number'] ? html_escape($conv['order_number']) : 'Tanya sebelum beli' ?></em>
    </div>

    <a href="<?= site_url('toko/' . $conv['store_slug']) ?>" class="mb-chat-toko" aria-label="Lihat toko">
      <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16l-1.5 13h-13L4 7z"></path><path d="M9 7V5a3 3 0 0 1 6 0v2"></path></svg>
    </a>
  </header>

  <div class="mb-chat-isi" id="chatKotak">
    <?php foreach ($pesan as $m): ?>
      <?php $this->load->view('v_chat_bubble', array('m' => $m, 'sisi' => 'customer')); ?>
    <?php endforeach; ?>

    <?php if (! $pesan): ?>
      <p class="mb-chat-kosong" id="chatKosong">
        Tanyakan apa saja tentang produk toko ini &mdash; stok, ukuran, atau lama pengiriman.
      </p>
    <?php endif; ?>
  </div>

  <?php $this->load->view('parts/chat_lampiran'); ?>

  <form class="mb-chat-form" id="chatForm">
    <input type="text" id="chatIsi" maxlength="1000" placeholder="Tulis pesan..."
           autocomplete="off" aria-label="Pesan">
    <button type="submit" aria-label="Kirim">
      <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
    </button>
  </form>
  <p class="mb-chat-info" id="chatInfo" aria-live="polite"></p>
</div>

<script>
  window.CHAT = {
    baseUrl: <?= json_encode(rtrim(site_url(), '/')) ?>,
    conv:    <?= (int) $conv['id'] ?>,
    sisi:    'customer',
    sejak:   <?= $pesan ? (int) end($pesan)['id'] : 0 ?>,
    gambar:  <?= json_encode(base_url('upload/produk/')) ?>
  };
  window.SHOP_URLS = {
    options:  '<?= site_url('cart/options') ?>',
    add:      '<?= site_url('cart/add') ?>',
    clear:    '<?= site_url('cart/clear') ?>',
    checkout: '<?= site_url('checkout') ?>'
  };
  window.CSRF = {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= aset('assets/js/chat.js') ?>"></script>
<!-- shop.js dipakai tombol "+ Keranjang" pada kartu produk di dalam
     percakapan: panel pilihan ukuran & tambahan sudah ditangani di sana,
     jadi tidak perlu ditulis ulang khusus untuk halaman ini. -->
<script src="<?= aset('assets/js/shop.js') ?>"></script>
