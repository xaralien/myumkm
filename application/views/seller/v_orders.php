<!-- application/views/seller/v_orders.php -->
<?php
/* ---------------------------------------------------------------------------
   Semua tulisan yang dilihat penjual ada di sini. Mengubah kata-katanya
   tidak perlu menyentuh controller.

   Yang TIDAK ada di sini: urutan alur status. Itu di controller, karena
   kalau status tujuan ditentukan view lalu dikirim lewat form, penjual
   bisa menggantinya lewat DevTools dan meloncat langsung ke tahap
   terakhir tanpa pernah merangkai bunganya.
   --------------------------------------------------------------------------- */

$label_status = array(
  'pending'    => 'Pesanan baru',
  'confirmed'  => 'Diproses',
  'preparing'  => 'Diproses',
  'delivering' => 'Dikirim',
  'delivered'  => 'Selesai',
  'cancelled'  => 'Dibatalkan',
);

// Tulisan pada tombol - kalimat perintah, beda dari label status di atas.
$label_tombol = array(
  'confirmed'  => 'Proses',
  'delivering' => 'Tandai dikirim',
  'delivered'  => 'Tandai selesai',
);

$label_alasan = array(
  'dibatalkan'  => 'Pesanan dibatalkan',
  'selesai'     => 'Sudah selesai',
  'belum_bayar' => 'Menunggu pembayaran',
);

// Kode yang tidak dikenal ditampilkan apa adanya, bukan string kosong -
// status kosong di layar jauh lebih membingungkan daripada kode mentah.
$tampil = function ($peta, $kode) {
  return isset($peta[$kode]) ? $peta[$kode] : $kode;
};
?>

<div class="panel-judul">
  <div>
    <h1>Pesanan Masuk</h1>
    <p class="hint"><?= html_escape($store['name']) ?></p>
  </div>
</div>

<!-- Pencarian lewat GET: hasilnya bisa di-bookmark dan tidak memunculkan
     dialog "kirim ulang formulir" saat halaman di-refresh. -->
<form method="get" action="<?= site_url('seller/orders') ?>" class="seller-cari">
  <input type="search" name="q" class="form-control" value="<?= html_escape($cari) ?>"
    placeholder="Cari nomor pesanan, nama pemesan, penerima, atau kota">
  <button type="submit" class="btn btn-primary">Cari</button>
  <?php if ($cari !== ''): ?>
    <a href="<?= site_url('seller/orders') ?>" class="btn btn-black-hover-outline">Hapus</a>
  <?php endif; ?>
</form>

<?php if (! empty($total_belum)): ?>
  <div class="alert-box alert-warn mb-3" id="soSpanduk"
    <?= empty($total_belum) ? 'hidden' : '' ?>>
    Ada <strong><?= (int) ($total_belum ?? 0) ?></strong> pesan belum dibaca dari customer.
  </div>
<?php endif; ?>

<?php if (! $orders): ?>

  <div class="empty-state">
    <?php if ($cari !== ''): ?>
      <p class="empty-title">Tidak ada yang cocok</p>
      <p class="hint mb-4">Coba kata kunci lain.</p>
      <a href="<?= site_url('seller/orders') ?>" class="btn btn-primary">Lihat semua pesanan</a>
    <?php else: ?>
      <p class="empty-title">Belum ada pesanan</p>
      <p class="hint">Pesanan yang masuk akan muncul di sini.</p>
    <?php endif; ?>
  </div>

<?php else: ?>

  <!-- CSS Grid, bukan <table>. Di layar kecil tiap baris berubah jadi
       kartu - itu yang tidak bisa dilakukan tabel biasa tanpa akal-akalan. -->
  <div class="gt gt--orders" role="table" aria-label="Daftar pesanan">

    <div class="gt__head" role="row">
      <span role="columnheader">Nomor</span>
      <span role="columnheader">Pemesan</span>
      <span role="columnheader">Penerima</span>
      <span role="columnheader">Dipesan</span>
      <span role="columnheader">Total</span>
      <span role="columnheader">Bayar</span>
      <span role="columnheader">Status</span>
      <span role="columnheader">Aksi</span>
    </div>

    <?php foreach ($orders as $o): ?>
      <div class="gt__row" role="row" data-order-row="<?= (int) $o['id'] ?>">

        <span class="gt__cell gt__cell--nomor" role="cell">
          <strong><?= html_escape($o['order_number']) ?></strong>
          <em><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></em>
        </span>

        <span class="gt__cell gt__cell--pemesan" role="cell" data-label="Pemesan">
          <?= html_escape($o['customer_name']) ?>
          <!-- Nomor sudah dinormalkan ke 62... saat checkout, jadi bisa
               langsung dipakai wa.me. -->
          <em><a href="https://wa.me/<?= html_escape($o['customer_phone']) ?>"
              target="_blank" rel="noopener">+<?= html_escape($o['customer_phone']) ?></a></em>
        </span>

        <span class="gt__cell gt__cell--penerima" role="cell" data-label="Penerima">
          <?= html_escape($o['recipient_name']) ?>
          <em><?= html_escape($o['recipient_city']) ?></em>
        </span>

        <span class="gt__cell gt__cell--jadwal" role="cell" data-label="Dipesan">
          <?= tgl_id(substr($o['created_at'], 0, 10)) ?>
          <em>pukul <?= date('H:i', strtotime($o['created_at'])) ?></em>
        </span>

        <span class="gt__cell gt__cell--total" role="cell" data-label="Total">
          <?= rupiah($o['total']) ?>
        </span>

        <span class="gt__cell gt__cell--bayar" role="cell" data-label="Bayar">
          <span class="badge-status <?= $o['payment_status'] === 'paid' ? 'is-ok' : 'is-wait' ?>">
            <?= label_bayar($o['payment_status']) ?>
          </span>
        </span>

        <span class="gt__cell gt__cell--status" role="cell" data-label="Status">
          <?= html_escape($tampil($label_status, $o['order_status'])) ?>
        </span>

        <span class="gt__cell gt__cell--aksi" role="cell">
          <?php if ($o['bisa_chat']): ?>
            <!-- Chat ditaruh sebelum tombol status. Kalau ada pesan yang belum
       dibaca, kemungkinan besar isinya menyangkut apa yang harus
       dikerjakan - jadi harus terbaca dulu sebelum status dimajukan. -->
            <a href="<?= site_url('seller/chat/' . $o['id']) ?>" class="btn btn-sm btn-secondary aksi-chat" data-order="<?= (int) $o['id'] ?>">
              Chat
              <?php if ($o['belum_chat'] > 0): ?>
                <span class="aksi-lencana"><?= (int) $o['belum_chat'] ?></span>
              <?php endif; ?>
            </a>
          <?php endif; ?>

          <?php if ($o['boleh_maju']): ?>
            <!-- POST, bukan tautan GET. Tautan yang mengubah data bisa
                 terpicu sendiri oleh prefetch browser, pemindai antivirus,
                 atau preview tautan WhatsApp. -->
            <?= form_open('seller/ubah_status/' . $o['id'], array('class' => 'inline-form')) ?>
            <button type="submit" class="btn btn-sm btn-primary btn-status"
              data-nomor="<?= html_escape($o['order_number']) ?>"
              data-status-label="<?= html_escape($tampil($label_status, $o['status_baru'])) ?>">
              <?= html_escape($tampil($label_tombol, $o['status_baru'])) ?>
            </button>
            <?= form_close() ?>
          <?php endif; ?>

          <?php if ($o['boleh_undo']): ?>
            <!-- Jendela pembatalan. Konfirmasi saja tidak cukup: orang
                 terbiasa menekan "Ya" tanpa membaca, jadi kesalahan tetap
                 perlu bisa diperbaiki setelah terjadi. -->
            <?= form_open('seller/undo_status/' . $o['id'], array('class' => 'inline-form')) ?>
            <button type="submit" class="link-btn btn-undo"
              data-nomor="<?= html_escape($o['order_number']) ?>">
              Batalkan (<?= (int) $o['sisa_undo'] ?> mnt)
            </button>
            <?= form_close() ?>
          <?php endif; ?>

          <?php if (! $o['boleh_maju'] && ! $o['boleh_undo']): ?>
            <span class="hint"><?= html_escape($tampil($label_alasan, $o['alasan'])) ?></span>
          <?php endif; ?>
        </span>

      </div>
    <?php endforeach; ?>
  </div>

  <p class="hint mt-3">
    Status hanya bisa maju satu langkah, dan hanya untuk pesanan yang sudah
    dibayar. Salah pencet masih bisa dibatalkan dalam <?= (int) $undo_menit ?> menit.
  </p>

<?php endif; ?>

<script>
  window.ORDERS_NOTIF = {
    url: '<?= site_url('seller/notif') ?>',
    total: <?= (int) ($total_belum ?? 0) ?>
  };
</script>
<script src="<?= base_url('assets/js/orders-notif.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {

    var UNDO_MENIT = <?= (int) $undo_menit ?>;

    /* Konfirmasi menahan submit form, lalu mengirimkannya sendiri kalau
       penjual setuju. Form-nya POST, jadi tetap aman walau JavaScript gagal
       dimuat - paling banter konfirmasinya yang hilang, bukan tombolnya
       yang berhenti bekerja. */
    function konfirmasi(selector, judul, teks, tombol) {
      document.querySelectorAll(selector).forEach(function(btn) {
        btn.addEventListener('click', function(e) {
          if (btn.dataset.lanjut === '1') {
            return;
          } // sudah disetujui
          e.preventDefault();

          Swal.fire({
            title: judul,
            html: teks(btn),
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b5d50',
            cancelButtonColor: '#6a6a6a',
            confirmButtonText: tombol,
            cancelButtonText: 'Batal',
            reverseButtons: true
          }).then(function(r) {
            if (!r.isConfirmed) {
              return;
            }

            btn.dataset.lanjut = '1';
            btn.disabled = true; // cegah klik ganda
            btn.closest('form').submit();
          });
        });
      });
    }

    konfirmasi('.btn-status', 'Ubah status pesanan?', function(b) {
      return 'Pesanan <strong>' + b.dataset.nomor + '</strong> akan diubah menjadi ' +
        '<strong>' + b.dataset.statusLabel + '</strong>.' +
        '<br><br><small>Masih bisa dibatalkan dalam ' + UNDO_MENIT + ' menit.</small>';
    }, 'Ya, ubah');

    konfirmasi('.btn-undo', 'Batalkan perubahan?', function(b) {
      return 'Status pesanan <strong>' + b.dataset.nomor +
        '</strong> akan dikembalikan ke tahap sebelumnya.';
    }, 'Ya, batalkan');

  });
</script>