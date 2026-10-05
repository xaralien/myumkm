/* =============================================================================
   panel-notif.js - lencana pesanan & pesan baru di bilah menu panel
   Simpan di: assets/js/panel-notif.js

   Berjalan di SEMUA halaman panel, bukan hanya daftar pesanan: penjual yang
   sedang menambah produk atau mengatur tokonya tetap tahu ada pesanan masuk
   tanpa perlu berpindah halaman.

   Polling, bukan WebSocket - WebSocket butuh server tersendiri yang tidak
   ada di XAMPP maupun hosting bersama.
   ========================================================================== */
(function () {
  'use strict';

  var C = window.PANEL_NOTIF;
  if (!C) { return; }

  var JEDA = 15000;

  var judulAsli = document.title;
  var kedip = null;
  var terbaruTerakhir = null;   // nomor pesanan terakhir yang sudah diketahui
  var pertama = true;

  function elemen(nama) {
    return document.querySelector('[data-notif="' + nama + '"]');
  }

  function setLencana(nama, jumlah) {
    var el = elemen(nama);
    if (!el) { return; }

    el.textContent = jumlah > 99 ? '99+' : jumlah;
    el.hidden = jumlah < 1;
  }

  /* --------------------------------------------------------------- nada */

  function bunyi() {
    /* Nada dibangkitkan Web Audio, bukan memuat berkas suara: satu berkas
       tambahan untuk bunyi sependek ini tidak sepadan, dan browser sering
       memblokir pemutaran berkas sebelum ada interaksi. */
    try {
      var AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) { return; }

      var ctx = new AC();
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();

      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      osc.frequency.setValueAtTime(1175, ctx.currentTime + 0.12);

      gain.gain.setValueAtTime(0.0001, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.18, ctx.currentTime + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.4);

      osc.connect(gain).connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + 0.42);

      osc.onended = function () { ctx.close(); };
    } catch (e) {
      // Nada gagal bukan alasan menghentikan lencana.
    }
  }

  /* -------------------------------------------------------- judul tab */

  function mulaiKedip(teks) {
    if (kedip) { return; }

    var nyala = false;
    kedip = setInterval(function () {
      document.title = nyala ? judulAsli : teks;
      nyala = !nyala;
    }, 1200);
  }

  function hentikanKedip() {
    clearInterval(kedip);
    kedip = null;
    document.title = judulAsli;
  }

  /* Judul berhenti berkedip begitu penjual kembali ke tab ini - dia sudah
     melihat pemberitahuannya, dan judul yang terus berkedip jadi gangguan. */
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) { hentikanKedip(); }
  });

  /* ------------------------------------------------------------ ambil */

  function ambil() {
    fetch(C.url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { return; }

        setLencana('pesan', parseInt(j.total, 10) || 0);
        setLencana('pesanan', parseInt(j.perlu, 10) || 0);

        /* Pada pemuatan pertama hanya dicatat, tidak dibunyikan: kalau
           tidak, setiap kali halaman panel dibuka akan terdengar nada
           untuk pesanan yang sudah lama dilihat. */
        if (pertama) {
          terbaruTerakhir = j.terbaru;
          pertama = false;
          return;
        }

        if (j.terbaru && j.terbaru !== terbaruTerakhir) {
          terbaruTerakhir = j.terbaru;
          bunyi();

          if (document.hidden) {
            mulaiKedip('Pesanan baru masuk');
          }
        }
      })
      .catch(function () { /* jaringan putus sesaat - coba lagi nanti */ })
      .then(function () {
        setTimeout(ambil, JEDA);
      });
  }

  ambil();
})();
