<?php
/* =============================================================================
   application/views/v_chat_widget.php  -  chat melayang untuk pembeli

   Panggil dari view mana pun yang punya $order:
       <?php $this->load->view('v_chat_widget', array('order' => $order)); ?>

   Harus berkas terpisah: 'return' di bawah menghentikan seluruh sisa berkas
   tempatnya berada. Di dalam partial, yang berhenti hanya partial ini.
   ========================================================================== */

// Belum dibayar = belum ada yang perlu dibicarakan dengan toko.
if (empty($order) || $order['payment_status'] !== 'paid') {
    return;
}

// Lewat get_instance(): model yang dimuat di tengah view tidak ikut
// tersalin ke $this.
$CI =& get_instance();
$CI->load->model('chat_model');
$belum = (int) $CI->chat_model->belum_dibaca($order['id'], 'customer');
?>

<button type="button" class="cw-tombol" id="cwTombol"
    aria-label="Buka percakapan dengan toko" aria-expanded="false">
    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
    <span class="cw-lencana" id="cwLencana" <?= $belum > 0 ? '' : 'hidden' ?>><?= $belum ?></span>
</button>

<div class="cw-panel" id="cwPanel" role="dialog" aria-label="Percakapan dengan toko" hidden>
    <div class="cw-kepala">
        <div>
            <strong>Tanya toko</strong>
            <em><?= html_escape($order['order_number']) ?></em>
        </div>
        <button type="button" class="cw-tutup" id="cwTutup" aria-label="Tutup">&times;</button>
    </div>

    <div class="cw-isi" id="cwIsi">
        <p class="cw-memuat">Memuat percakapan...</p>
    </div>

    <form class="cw-form" id="cwForm">
        <input type="text" id="cwTeks" class="form-control" maxlength="1000"
            placeholder="Tulis pesan untuk toko..." autocomplete="off" aria-label="Pesan">
        <button type="submit" class="cw-kirim" aria-label="Kirim">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
        </button>
    </form>
    <p class="cw-info" id="cwInfo" aria-live="polite"></p>
</div>

<div class="cw-lightbox" id="cwLightbox" hidden>
    <button type="button" class="cw-lightbox-tutup" aria-label="Tutup">&times;</button>
    <img src="" alt="">
</div>

<script>
    window.CHAT_WIDGET = {
        baseUrl: <?= json_encode(rtrim(site_url(), '/')) ?>,
        nomor:   <?= json_encode($order['order_number']) ?>,
        token:   <?= json_encode($order['access_token']) ?>,
        gambar:  <?= json_encode(base_url('upload/produk/')) ?>
    };
    window.CSRF = window.CSRF || {
        name: '<?= $this->security->get_csrf_token_name() ?>',
        hash: '<?= $this->security->get_csrf_hash() ?>'
    };
</script>
<script src="<?= base_url('assets/js/chat-widget.js') ?>"></script>
