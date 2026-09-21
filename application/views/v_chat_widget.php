<?php
/* =============================================================================
   application/views/v_chat_widget.php

   Widget chat melayang. CARA MEMANGGILNYA - satu baris, taruh sebelum
   </div> penutup atau di bagian bawah view mana pun yang punya $order:

       <?php $this->load->view('v_chat_widget', array('order' => $order)); ?>

   Boleh juga mengirim dua nilai tambahan (opsional):

       <?php $this->load->view('v_chat_widget', array(
           'order'        => $order,
           'sisa_revisi'  => $sisa_revisi,
           'belum_dibaca' => $belum_dibaca,
       )); ?>

   KENAPA HARUS FILE TERPISAH, bukan disalin ke tiap view:
   'return' di bawah menghentikan SELURUH sisa berkas tempat ia berada.
   Kalau kode ini ditempel langsung di v_order_done.php, pesanan yang
   belum lunas akan membuat script tombol "Salin link" di bagian bawah
   ikut tidak dirender - dan tombolnya mati tanpa pesan error.
   Di dalam partial, 'return' hanya menghentikan partial ini saja.
   ========================================================================== */

// Widget hanya berguna kalau pesanannya sudah dibayar - sebelum itu belum
// ada yang perlu dibicarakan, dan tokonya pun belum tentu melihat.
if (empty($order) || $order['payment_status'] !== 'paid') {
    return;
}

$menunggu = ($order['acc_status'] === 'menunggu');

/* Jumlah pesan belum dibaca dihitung SENDIRI di sini kalau pemanggil tidak
   mengirimkannya. Dengan begitu partial ini cukup dipanggil dengan satu
   nilai saja - tidak ada lagi controller yang harus diingat untuk
   menyiapkan angkanya. */
if (isset($belum_dibaca)) {
    $belum = (int) $belum_dibaca;
} else {
    $CI = &get_instance();
    $CI->load->model('chat_model');
    $belum = (int) $CI->chat_model->belum_dibaca($order['id'], 'customer');
}
$sisa     = isset($sisa_revisi)  ? (int) $sisa_revisi  : NULL;

$label_acc = array(
    'belum'     => 'Toko sedang merangkai',
    'menunggu'  => 'Butuh persetujuan kamu',
    'revisi'    => 'Toko sedang memperbaiki',
    'disetujui' => 'Sudah disetujui',
    'otomatis'  => 'Disetujui otomatis',
    'ditutup'   => 'Revisi ditutup',
);
?>

<button type="button" class="cw-tombol" id="cwTombol"
    aria-label="Buka percakapan dengan toko" aria-expanded="false">
    <svg viewBox="0 0 24 24" width="24" height="24" fill="none"
        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
    </svg>
    <span class="cw-lencana" id="cwLencana" <?= $belum > 0 ? '' : 'hidden' ?>><?= $belum ?></span>

    <?php if ($menunggu): ?>
        <!-- Denyut HANYA saat ada yang menunggu jawaban. Kalau selalu
             menyala, orang berhenti memperhatikannya. -->
        <span class="cw-denyut" aria-hidden="true"></span>
    <?php endif; ?>
</button>

<div class="cw-panel" id="cwPanel" role="dialog" aria-label="Percakapan pesanan" hidden>

    <div class="cw-kepala">
        <div>
            <strong><?= html_escape($order['order_number']) ?></strong>
            <em id="cwStatus"><?= html_escape($label_acc[$order['acc_status']]) ?></em>
        </div>
        <button type="button" class="cw-tutup" id="cwTutup" aria-label="Tutup">&times;</button>
    </div>

    <?php if ($menunggu && $order['acc_deadline']): ?>
        <div class="cw-tenggat" id="cwTenggat">
            Batas persetujuan <strong><?= date('d/m H:i', strtotime($order['acc_deadline'])) ?></strong>
            &mdash; lewat itu dianggap setuju dan langsung dikirim.
        </div>
    <?php endif; ?>

    <div class="cw-isi" id="cwIsi">
        <p class="cw-memuat">Memuat percakapan...</p>
    </div>

    <!-- Tombol ACC. Atribut hidden dikendalikan JS supaya ikut berubah
         tanpa memuat ulang halaman saat tenggatnya lewat. -->
    <div class="cw-acc" id="cwAcc" <?= $menunggu ? '' : 'hidden' ?>>
        <button type="button" class="btn btn-primary btn-sm" id="cwSetuju">Setujui</button>
        <button type="button" class="btn btn-black-hover-outline btn-sm" id="cwRevisi"
            <?= ($sisa !== NULL && $sisa < 1) ? 'disabled' : '' ?>>
            Minta perbaikan<?= $sisa !== NULL ? ' (' . $sisa . 'x)' : '' ?>
        </button>
    </div>

    <form class="cw-form" id="cwForm">
        <input type="text" id="cwTeks" class="form-control" maxlength="1000"
            placeholder="Tulis pesan..." autocomplete="off">
        <button type="submit" class="cw-kirim" aria-label="Kirim">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="22" y1="2" x2="11" y2="13" />
                <polygon points="22 2 15 22 11 13 2 9 22 2" />
            </svg>
        </button>
    </form>

    <p class="cw-info" id="cwInfo"></p>
</div>

<!-- Foto ukuran penuh. Foto ACC di panel kecil sulit dinilai, padahal
     keputusan menyetujuinya mengunci kata-kata papan permanen. -->
<div class="cw-lightbox" id="cwLightbox" hidden>
    <button type="button" class="cw-lightbox-tutup" aria-label="Tutup">&times;</button>
    <img src="" alt="">
</div>

<script>
    window.CHAT_WIDGET = {
        // Alamat dirangkai di JavaScript dari tiga bagian ini. Nomor dan
        // token dipisah supaya urutan segmennya bisa berbeda antara
        // halaman (chat/{nomor}/{token}) dan aksi (chat/kirim/{nomor}/{token}).
        baseUrl: <?= json_encode(rtrim(site_url(), '/')) ?>,
        nomor: <?= json_encode($order['order_number']) ?>,
        token: <?= json_encode($order['access_token']) ?>,
        gambar: <?= json_encode(base_url('upload/produk/')) ?>,
        acc: <?= json_encode($order['acc_status']) ?>,
        buka: <?= $menunggu ? 'true' : 'false' ?>,

        // Dipakai mengembalikan judul tab setelah berkedip.
        judul: document.title
    };

    // window.CSRF mungkin sudah dibuat script lain di halaman yang sama -
    // jangan ditimpa, karena nilainya bisa sudah diperbarui.
    window.CSRF = window.CSRF || {
        name: '<?= $this->security->get_csrf_token_name() ?>',
        hash: '<?= $this->security->get_csrf_hash() ?>'
    };
</script>
<script src="<?= base_url('assets/js/chat-widget.js') ?>"></script>