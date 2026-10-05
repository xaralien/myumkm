/* =============================================================================
   akun-notif.js - titik merah di tab Akun & hitungan di menu akun (HP)
   Simpan di: assets/js/akun-notif.js
   ========================================================================== */
(function () {
  'use strict';

  var C = window.AKUN_NOTIF;
  if (!C) { return; }

  var JEDA = 30000;

  function isi(nama, nilai) {
    var el = document.querySelector('[data-akun-notif="' + nama + '"]');
    if (!el) { return; }

    if (nama === 'titik') {
      el.hidden = nilai < 1;
      return;
    }

    el.textContent = nilai > 99 ? '99+' : nilai;
    el.hidden = nilai < 1;
  }

  function ambil() {
    fetch(C.url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { return; }

        isi('titik', parseInt(j.penting, 10) || 0);
        isi('pesanan_saya', parseInt(j.pesanan_saya, 10) || 0);
        isi('pesan_saya', parseInt(j.pesan_saya, 10) || 0);
        isi('toko_pesanan', parseInt(j.toko_pesanan, 10) || 0);
        isi('toko_pesan', parseInt(j.toko_pesan, 10) || 0);
      })
      .catch(function () { /* jaringan putus sesaat - coba lagi nanti */ })
      .then(function () {
        /* Dijadwalkan ulang setelah permintaan SELESAI, bukan dengan
           setInterval: kalau jaringan lambat, interval akan menumpuk
           permintaan yang saling menyusul. */
        setTimeout(ambil, JEDA);
      });
  }

  ambil();

  /* Disegarkan saat tab dibuka kembali - penjual yang baru kembali dari
     aplikasi lain langsung melihat angka terbaru, tanpa menunggu giliran
     polling berikutnya. */
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) { ambil(); }
  });
})();
