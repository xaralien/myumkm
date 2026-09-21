<?php
$menunggu  = ($order['acc_status'] === 'menunggu');
$selesai   = in_array($order['acc_status'], array('disetujui', 'otomatis', 'ditutup'), TRUE);
$label_acc = array(
    'belum'     => 'Menunggu toko merangkai',
    'menunggu'  => 'Menunggu persetujuan kamu',
    'revisi'    => 'Toko sedang memperbaiki',
    'disetujui' => 'Sudah kamu setujui',
    'otomatis'  => 'Disetujui otomatis',
    'ditutup'   => 'Revisi ditutup toko',
);
?>

<div class="hero hero-page">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="intro-excerpt text-center">
                    <h1>Percakapan Pesanan</h1>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="untree_co-section before-footer-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="form-card">
                    <div class="chat-kepala">
                        <div>
                            <p class="hint mb-1">Nomor pesanan</p>
                            <p class="order-number mb-1"><?= html_escape($order['order_number']) ?></p>
                            <span class="badge-status <?= $selesai ? 'is-ok' : 'is-wait' ?>">
                                <?= html_escape($label_acc[$order['acc_status']]) ?>
                            </span>
                        </div>
                        <?php if ($toko): ?>
                            <div class="text-end">
                                <p class="hint mb-0"><?= html_escape($toko['name']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($menunggu && $order['acc_deadline']): ?>
                        <!-- Tenggat ditampilkan MENCOLOK. Aturannya: lewat tenggat
                 tanpa jawaban berarti dianggap setuju - customer harus
                 tahu itu sebelum tenggatnya lewat, bukan sesudahnya. -->
                        <div class="acc-tenggat">
                            <strong>Batas persetujuan:
                                <?= tgl_id($order['acc_deadline']) ?>,
                                <?= date('H:i', strtotime($order['acc_deadline'])) ?>
                            </strong>
                            <span>Lewat batas ini tanpa jawaban, rangkaian dianggap disetujui
                                dan langsung dikirim.</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($terkunci): ?>
                        <p class="hint mt-2">
                            Kata-kata papan dikunci sejak
                            <?= date('d/m/Y H:i', strtotime($order['card_locked_at'])) ?>
                            dan tidak bisa diubah lagi.
                        </p>
                    <?php elseif ($order['card_message']): ?>
                        <p class="hint mt-2">
                            Kata-kata papan sekarang: &ldquo;<?= html_escape($order['card_message']) ?>&rdquo;
                            &mdash; masih bisa diubah, tulis permintaannya di percakapan ini.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- ============ PERCAKAPAN ============ -->
                <div class="chat-kotak" id="chatKotak">
                    <?php foreach ($pesan as $m): ?>
                        <?php $this->load->view('v_chat_bubble', array('m' => $m, 'sisi' => 'customer')); ?>
                    <?php endforeach; ?>
                </div>

                <!-- ============ TOMBOL ACC ============ -->
                <div class="acc-aksi" id="accAksi" <?= $menunggu ? '' : 'hidden' ?>>
                    <button type="button" class="btn btn-primary" id="btnSetuju">
                        Setujui rangkaian
                    </button>
                    <button type="button" class="btn btn-black-hover-outline" id="btnRevisi"
                        <?= $sisa_revisi < 1 ? 'disabled' : '' ?>>
                        Minta perbaikan
                        <?= $sisa_revisi > 0 ? '(' . (int) $sisa_revisi . 'x lagi)' : '(habis)' ?>
                    </button>
                </div>

                <!-- ============ KIRIM PESAN ============ -->
                <form class="chat-form" id="chatForm">
                    <input type="text" class="form-control" id="chatIsi"
                        placeholder="Tulis pesan untuk toko..." autocomplete="off" maxlength="1000">
                    <button type="submit" class="btn btn-primary">Kirim</button>
                </form>

                <p class="chat-info" id="chatInfo"></p>

                <div class="text-center mt-4">
                    <a href="<?= site_url('checkout/done/' . $order['order_number'] . '/' . $order['access_token']) ?>"
                        class="btn btn-black-hover-outline">Lihat detail pesanan</a>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    window.CHAT = {
        base: <?= json_encode($base) ?>,
        sisi: 'customer',
        sejak: <?= $pesan ? (int) end($pesan)['id'] : 0 ?>,
        gambar: <?= json_encode(base_url('upload/produk/')) ?>
    };
    window.CSRF = {
        name: '<?= $this->security->get_csrf_token_name() ?>',
        hash: '<?= $this->security->get_csrf_hash() ?>'
    };
</script>
<script src="<?= base_url('assets/js/chat.js') ?>"></script>