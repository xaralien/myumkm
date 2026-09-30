<!-- application/views/seller/v_pesan.php -->
<?php
  $label = array(
    'pending' => 'Pesanan baru', 'confirmed' => 'Diproses', 'preparing' => 'Diproses',
    'delivering' => 'Dikirim', 'delivered' => 'Selesai', 'cancelled' => 'Dibatalkan',
  );
  $url = function ($d) { return site_url('seller/pesan/' . (int) $d['id']); };

  // Percakapan tanya-sebelum-beli tidak punya pesanan, jadi label statusnya
  // dibuat sendiri - tanpa ini barisnya terlihat kosong.
  $label['_tanya'] = 'Belum memesan';
?>

<div class="panel-judul">
  <div>
    <h1>Pesan</h1>
    <p class="hint">Percakapan dengan pembeli, dikelompokkan per pesanan.</p>
  </div>
  <a href="<?= site_url('seller/orders') ?>" class="btn btn-black-hover-outline">Daftar pesanan</a>
</div>

<!-- Dua panel di layar lebar, satu panel di HP. Pemilihan panel mana yang
     tampil dilakukan CSS; di HP, kelas is-buka membuat ruang chat menutupi
     daftarnya. -->
<div class="inbox <?= $aktif ? 'is-buka' : '' ?>">

  <div class="inbox-kiri">
    <?php $this->load->view('parts/inbox_daftar', array(
        'daftar' => $daftar, 'aktif' => $aktif, 'url' => $url, 'label' => $label
    )); ?>
  </div>

  <div class="inbox-kanan">
    <?php if (! $aktif): ?>
      <div class="inbox-kosong-kanan">
        <p>Pilih satu percakapan di sebelah kiri.</p>
      </div>
    <?php else: ?>

      <div class="inbox-kepala">
        <a href="<?= site_url('seller/pesan') ?>" class="inbox-balik" aria-label="Kembali ke daftar">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"></path></svg>
        </a>
        <div>
          <strong><?= html_escape($aktif['order_number'] ?: 'Tanya sebelum beli') ?></strong>
          <em>
            <?= $aktif['order_id']
                  ? html_escape($label[$aktif['order_status']] ?? $aktif['order_status'])
                  : 'Pembeli menanyakan produk' ?>
          </em>
        </div>
        <?php if ($aktif['order_id']): ?>
          <a href="<?= site_url('seller/chat/' . (int) $aktif['order_id']) ?>" class="btn btn-black-hover-outline btn-sm">
            Kelola pesanan
          </a>
        <?php endif; ?>
      </div>

      <div class="chat-kotak" id="chatKotak">
        <?php foreach ($pesan as $m): ?>
          <?php $this->load->view('v_chat_bubble', array('m' => $m, 'sisi' => 'seller')); ?>
        <?php endforeach; ?>
      </div>

      <form class="chat-form" id="chatForm">
        <input type="text" id="chatIsi" class="form-control" maxlength="1000"
               placeholder="Tulis pesan untuk pembeli..." autocomplete="off" aria-label="Pesan">
        <button type="submit" class="btn btn-primary">Kirim</button>
      </form>
      <p class="chat-info" id="chatInfo" aria-live="polite"></p>
    <?php endif; ?>
  </div>
</div>

<?php if ($aktif): ?>
  <script>
    window.CHAT_SELLER = {
      base:    <?= json_encode(rtrim(site_url(), '/')) ?>,
      conv:    <?= (int) $aktif['id'] ?>,
      gambar:  <?= json_encode(base_url('upload/produk/')) ?>,
      sejak:   <?= $pesan ? (int) end($pesan)['id'] : 0 ?>,
      status:  <?= json_encode($aktif['order_status'] ?: '') ?>
    };
    window.CSRF = {
      name: '<?= $this->security->get_csrf_token_name() ?>',
      hash: '<?= $this->security->get_csrf_hash() ?>'
    };
  </script>
  <script src="<?= aset('assets/js/chat-seller.js') ?>"></script>
<?php endif; ?>
