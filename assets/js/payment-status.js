/* =============================================================================
   payment-status.js - memantau status pembayaran tanpa callback
   Simpan di: assets/js/payment-status.js

   Callback Duitku butuh URL yang bisa diakses dari internet, jadi tidak
   mungkin di localhost. Berkas ini menanyakan status ke server kita, dan
   server yang menghubungi Duitku.

   BATASNYA: ini hanya jalan selama halaman terbuka. Pembeli yang menutup
   tab lalu membayar lewat VA beberapa jam kemudian tidak akan tertangkap
   di sini - itu tugas penyapu berkala (payment/sweep lewat cron).
   ========================================================================== */
(function () {
  'use strict';

  var C = window.PAY_STATUS;
  if (!C || !C.url) { return; }

  var jeda    = (C.awal || 3) * 1000;
  var jedaMax = (C.akhir || 12) * 1000;
  var batas   = Date.now() + ((C.maks || 600) * 1000);
  var timer   = null;

  var kotak = document.getElementById('statusPantau');

  function tulis(teks, jenis) {
    if (!kotak) { return; }
    kotak.textContent = teks;
    kotak.className = 'pantau' + (jenis ? ' is-' + jenis : '');
  }

  function jadwalkan() {
    if (Date.now() > batas) {
      tulis('Pemantauan otomatis berhenti. Muat ulang halaman untuk memeriksa lagi.', '');
      return;
    }
    /* Jeda melebar bertahap: cepat di awal saat pembayaran memang sedang
       diproses, lalu melambat supaya tidak membanjiri server kalau
       pembeli meninggalkan halaman terbuka. */
    jeda = Math.min(Math.round(jeda * 1.4), jedaMax);
    timer = setTimeout(cek, jeda);
  }

  function cek() {
    fetch(C.url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { return jadwalkan(); }

        /* Kondisi akhir - berhenti memantau. Tanpa cabang 'gagal' di sini,
           pesanan yang transaksinya tidak terbentuk akan terus ditanyakan
           sampai batas waktu, lalu berhenti tanpa penjelasan apa pun. */
        if (j.gagal) {
          clearTimeout(timer);
          if (j.bisa_ulang && j.url_ulang) {
            tulis('Pembayaran belum terbentuk. Mengarahkan untuk mencoba lagi...', 'error');
            setTimeout(function () { window.location = j.url_ulang; }, 1500);
          } else {
            tulis('Pesanan ini sudah tidak bisa dibayar. Muat ulang untuk melihat statusnya.', 'error');
          }
          return;
        }

        if (j.selesai) {
          clearTimeout(timer);
          tulis('Pembayaran diterima. Memuat ulang halaman...', 'ok');

          /* Halaman dimuat ulang, bukan diperbarui lewat JavaScript.
             Status pembayaran menentukan banyak bagian halaman sekaligus -
             lencana, tombol, rincian - dan menyusunnya ulang di browser
             beresiko tidak sinkron dengan yang tersimpan di server. */
          setTimeout(function () { window.location.reload(); }, 900);
          return;
        }
        jadwalkan();
      })
      .catch(function () {
        // Koneksi putus sesaat bukan alasan berhenti - coba lagi nanti.
        jadwalkan();
      });
  }

  tulis('Memantau status pembayaran...', '');
  timer = setTimeout(cek, jeda);

  /* Hemat permintaan saat tab tidak dilihat, dan periksa segera begitu
     pembeli kembali - biasanya persis setelah selesai membayar di
     aplikasi bank atau e-wallet. */
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      clearTimeout(timer);
    } else if (Date.now() < batas) {
      jeda = (C.awal || 3) * 1000;
      clearTimeout(timer);
      timer = setTimeout(cek, 500);
    }
  });

})();