<!-- application/views/v_pay.php -->

<div class="hero hero-page">
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="intro-excerpt text-center">
          <h1>Pembayaran</h1>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-6">

        <div class="form-card text-center">
          <p class="hint mb-1">Nomor pesanan</p>
          <p class="order-number"><?= html_escape($order['order_number']) ?></p>

          <div class="summary-row summary-total mb-4">
            <span>Total tagihan</span>
            <strong><?= rupiah($order['total']) ?></strong>
          </div>

          <p class="hint mb-4">
            Pilih metode pembayaran di jendela yang muncul &mdash; transfer bank,
            e-wallet, QRIS, atau kartu kredit.
          </p>

          <button type="button" class="btn btn-primary w-100" id="btnPay">
            Bayar sekarang
          </button>

          <p class="pay-note" id="payNote"></p>
          <p class="pantau" id="statusPantau"></p>

          <p class="hint mt-3">
            Belum mau bayar sekarang?
            <a href="<?= html_escape($done_url) ?>">Lihat pesanan</a>
            &mdash; kamu bisa kembali ke halaman ini kapan saja lewat link pesananmu.
          </p>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Pemantau status. Menggantikan callback saat berjalan di localhost:
     callback butuh URL yang bisa diakses dari internet, halaman ini tidak. -->
<script>
  window.PAY_STATUS = {
    url: '<?= site_url('payment/status/' . $order['order_number'] . '/' . $order['access_token']) ?>',
    awal: <?= (int) $poll_awal ?>,
    akhir: <?= (int) $poll_akhir ?>,
    maks: <?= (int) $poll_maks ?>
  };
</script>
<script src="<?= base_url('assets/js/payment-status.js') ?>"></script>

<!-- Script Duitku. URL sandbox dan production berbeda, diambil dari config. -->
<script src="<?= html_escape($js_url) ?>"></script>
<script>
  (function() {
    var REFERENCE = <?= json_encode($order['duitku_reference']) ?>;
    var DONE_URL = <?= json_encode($done_url) ?>;
    var FALLBACK = <?= json_encode($fallback) ?>;
    var LANG = <?= json_encode($language) ?>;

    var btn = document.getElementById('btnPay');
    var note = document.getElementById('payNote');

    function pesan(teks, jenis) {
      note.textContent = teks;
      note.className = 'pay-note' + (jenis ? ' is-' + jenis : '');
    }

    function bebaskan() {
      btn.disabled = false;
      btn.textContent = 'Bayar sekarang';
    }

    function bayar() {
      /* Kalau duitku.js gagal dimuat (adblocker, jaringan kantor, atau
         URL sandbox/production tertukar), jangan biarkan tombol mati tanpa
         penjelasan. Alihkan ke halaman pembayaran Duitku sebagai cadangan. */
      if (typeof checkout === 'undefined' || !checkout.process) {
        if (FALLBACK) {
          window.location = FALLBACK;
          return;
        }
        pesan('Modul pembayaran gagal dimuat. Muat ulang halaman ini, atau hubungi kami lewat WhatsApp.', 'error');
        bebaskan();
        return;
      }

      btn.disabled = true;
      btn.textContent = 'Membuka...';
      pesan('');

      checkout.process(REFERENCE, {
        defaultLanguage: LANG,

        /* Status pembayaran yang sah HANYA ditentukan callback dari server
           Duitku. Peristiwa di bawah ini datang dari browser dan bisa
           dipalsukan, jadi tidak dipakai untuk menandai lunas - hanya untuk
           mengarahkan pembeli. Halaman tujuan menanyakan status sebenarnya
           ke server. */
        successEvent: function() {
          pesan('Pembayaran diterima. Mengalihkan...', 'ok');
          window.location = DONE_URL;
        },
        pendingEvent: function() {
          pesan('Menunggu pembayaran. Mengalihkan...', 'ok');
          window.location = DONE_URL;
        },
        errorEvent: function() {
          pesan('Pembayaran gagal diproses. Silakan coba lagi.', 'error');
          bebaskan();
        },
        closeEvent: function() {
          pesan('Jendela pembayaran ditutup. Klik lagi kalau mau melanjutkan.', '');
          bebaskan();
        }
      });
    }

    btn.addEventListener('click', bayar);

    // Buka otomatis begitu halaman siap - pembeli baru saja menekan
    // "Buat pesanan", jadi mereka memang sedang menunggu ini.
    window.addEventListener('load', function() {
      setTimeout(bayar, 400);
    });
  })();
</script>