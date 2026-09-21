<!-- application/views/seller/v_chat.php -->
<?php
$menunggu = ($order['acc_status'] === 'menunggu');
$revisi   = ($order['acc_status'] === 'revisi');
$selesai  = in_array($order['acc_status'], array('disetujui', 'otomatis', 'ditutup'), TRUE);
$lunas    = ($order['payment_status'] === 'paid');

$label_acc = array(
    'belum'     => 'Belum kirim foto',
    'menunggu'  => 'Menunggu persetujuan customer',
    'revisi'    => 'Customer minta perbaikan',
    'disetujui' => 'Disetujui customer',
    'otomatis'  => 'Disetujui otomatis (tenggat lewat)',
    'ditutup'   => 'Revisi ditutup',
);
?>

<div class="panel-judul">
    <div>
        <h1><?= html_escape($order['order_number']) ?></h1>
        <p class="hint">
            <?= html_escape($order['recipient_name']) ?> &middot;
            <?= tgl_id($order['delivery_date']) ?> pukul <?= html_escape($order['delivery_slot']) ?>
        </p>
    </div>
    <a href="<?= site_url('seller/orders') ?>" class="btn btn-black-hover-outline">Kembali</a>
</div>

<div class="row">

    <!-- ==================== PERCAKAPAN ==================== -->
    <div class="col-lg-7 mb-4">

        <div class="chat-kotak" id="chatKotak">
            <?php foreach ($pesan as $m): ?>
                <?php $this->load->view('v_chat_bubble', array('m' => $m, 'sisi' => 'seller')); ?>
            <?php endforeach; ?>
        </div>

        <form class="chat-form" id="chatForm">
            <input type="text" id="chatIsi" class="form-control" maxlength="1000"
                placeholder="Tulis pesan untuk customer..." autocomplete="off">
            <button type="submit" class="btn btn-primary">Kirim</button>
        </form>

        <p class="chat-info" id="chatInfo"></p>
    </div>

    <!-- ==================== PANEL AKSI ==================== -->
    <div class="col-lg-5">

        <div class="form-card">
            <h3 class="form-card-title">Status persetujuan</h3>
            <p class="mb-2">
                <span class="badge-status <?= $selesai ? 'is-ok' : 'is-wait' ?>">
                    <?= html_escape($label_acc[$order['acc_status']]) ?>
                </span>
            </p>

            <?php if ($menunggu && $order['acc_deadline']): ?>
                <p class="hint">
                    Tenggat <?= date('d/m/Y H:i', strtotime($order['acc_deadline'])) ?>.
                    Lewat itu dianggap disetujui otomatis dan bunga langsung dikirim.
                </p>
            <?php endif; ?>

            <p class="hint mb-0">
                Revisi terpakai <?= (int) $order['revisi_terpakai'] ?> dari
                <?= (int) $toko['maks_revisi'] ?>.
            </p>
        </div>

        <?php if (! $lunas): ?>
            <div class="alert-box alert-warn mb-4">
                Pesanan ini belum dibayar. Tunggu pembayaran masuk sebelum mulai merangkai.
            </div>

        <?php elseif (! $selesai): ?>
            <div class="form-card">
                <h3 class="form-card-title">
                    <?= $revisi ? 'Kirim foto perbaikan' : 'Kirim foto untuk disetujui' ?>
                </h3>
                <p class="hint mb-3">
                    Foto rangkaian yang sudah jadi. Customer punya waktu
                    <?= (int) $toko['acc_tunggu_jam'] ?> jam untuk menjawab &mdash; lewat itu
                    dianggap setuju dan bunga langsung dikirim.
                </p>

                <!-- form_open_MULTIPART - tanpa enctype, berkasnya tidak ikut
             terkirim sama sekali dan $_FILES kosong tanpa pesan error. -->
                <?= form_open_multipart('seller/kirim_foto_acc/' . $order['id']) ?>
                <div class="mb-2">
                    <input type="file" name="foto_acc" class="form-control" required
                        accept="image/jpeg,image/png,image/webp">
                    <p class="hint">JPG, PNG, atau WEBP. Maksimal 4 MB.</p>
                </div>
                <div class="mb-3">
                    <input type="text" name="catatan" class="form-control" maxlength="500"
                        placeholder="Catatan untuk customer (opsional)">
                </div>
                <button type="submit" class="btn btn-primary w-100">Kirim foto</button>
                <?= form_close() ?>
            </div>

            <?php if ($menunggu || $revisi): ?>
                <div class="form-card">
                    <h3 class="form-card-title">Tutup revisi</h3>
                    <p class="hint mb-3">
                        Dipakai kalau permintaan customer di luar kesanggupan, atau jadwal
                        kirim sudah terlalu dekat. Kata-kata papan langsung dikunci.
                    </p>
                    <?= form_open('seller/tutup_revisi/' . $order['id']) ?>
                    <input type="text" name="alasan" class="form-control mb-2" required minlength="5"
                        placeholder="Alasan (dilihat customer)">
                    <button type="submit" class="btn btn-black-hover-outline w-100">Tutup revisi</button>
                    <?= form_close() ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Sudah disetujui: yang tersisa cuma mengirim foto di lokasi. -->
            <div class="form-card">
                <h3 class="form-card-title">Foto di lokasi</h3>
                <p class="hint mb-3">
                    Kirim foto saat bunga diserahkan. Pemesan biasanya tidak ada di tempat,
                    jadi foto ini pengganti kehadiran mereka.
                </p>
                <?= form_open_multipart('seller/kirim_foto_lokasi/' . $order['id']) ?>
                <div class="mb-2">
                    <input type="file" name="foto_lokasi" class="form-control" required
                        accept="image/jpeg,image/png,image/webp">
                </div>
                <div class="mb-3">
                    <input type="text" name="catatan" class="form-control" maxlength="500"
                        placeholder="Catatan (opsional)">
                </div>
                <button type="submit" class="btn btn-primary w-100">Kirim foto</button>
                <?= form_close() ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <h3 class="form-card-title">Kata-kata papan</h3>

            <?php if ($terkunci): ?>
                <p class="papan-terkunci"><?= html_escape($order['card_message'] ?: '(kosong)') ?></p>
                <p class="hint mb-0">
                    Dikunci <?= date('d/m/Y H:i', strtotime($order['card_locked_at'])) ?>.
                    Tidak bisa diubah lagi.
                </p>
            <?php else: ?>
                <?= form_open('seller/ubah_kartu/' . $order['id']) ?>
                <textarea name="card_message" class="form-control mb-2" rows="3"
                    maxlength="500"><?= html_escape($order['card_message']) ?></textarea>
                <p class="hint mb-2">
                    Masih bisa diubah sampai customer menyetujui foto, atau sampai
                    revisi ditutup.
                </p>
                <button type="submit" class="btn btn-primary w-100">Simpan</button>
                <?= form_close() ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    window.CHAT_SELLER = {
        base: <?= json_encode(rtrim(site_url(), '/')) ?>,
        orderId: <?= (int) $order['id'] ?>,
        gambar: <?= json_encode(base_url('upload/produk/')) ?>,
        sejak: <?= $pesan ? (int) end($pesan)['id'] : 0 ?>,
        terkunci: <?= $terkunci ? 'true' : 'false' ?>
    };
    window.CSRF = {
        name: '<?= $this->security->get_csrf_token_name() ?>',
        hash: '<?= $this->security->get_csrf_hash() ?>'
    };
</script>
<script src="<?= base_url('assets/js/chat-seller.js') ?>"></script>