/* =============================================================================
   orders-notif.js - pemantau pesan baru di daftar pesanan penjual
   Simpan di: assets/js/orders-notif.js
   Muat di v_orders.php.

   Memperbarui angka lencana tanpa memuat ulang halaman. TIDAK menandai
   pesan sebagai dibaca - itu hanya terjadi saat penjual membuka
   percakapannya.
   ========================================================================== */
(function () {
  'use strict';

  var C = window.ORDERS_NOTIF;
  if (!C || !C.url) { return; }

  /* 15 detik. Lebih lambat dari halaman chat (4 detik) karena di sini
     penjual tidak sedang menunggu balasan - cukup tahu ada yang masuk.
     Halaman ini juga sering dibiarkan terbuka berjam-jam. */
  var JEDA = 15000;
  var timer = null;

  var judulAsli = document.title;
  var kedipTimer = null;
  var totalLama = parseInt(C.total, 10) || 0;

  /* ------------------------------------------------------------- bantu */

  function kedipJudul(n) {
    clearInterval(kedipTimer);
    if (n < 1) { return berhentiKedip(); }

    var nyala = false;
    kedipTimer = setInterval(function () {
      document.title = nyala ? judulAsli : '(' + n + ') Pesan baru';
      nyala = !nyala;
    }, 1200);
  }

  function berhentiKedip() {
    clearInterval(kedipTimer);
    document.title = judulAsli;
  }

  function bunyi() {
    try {
      var AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) { return; }

      var ctx = new AC();

      /* Browser memblokir suara sebelum pengguna pernah menyentuh halaman.
         Diperiksa dulu supaya konsol tidak penuh galat. */
      if (ctx.state === 'suspended') { return; }

      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);

      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0.0001, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);

      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.36);

      setTimeout(function () { ctx.close(); }, 600);
    } catch (e) {
      // Suara gagal bukan alasan menghentikan apa pun.
    }
  }

  function toast(teks) {
    var lama = document.getElementById('soToast');
    if (lama) { lama.remove(); }

    var el = document.createElement('div');
    el.id = 'soToast';
    el.className = 'so-toast';
    el.textContent = teks;

    document.body.appendChild(el);
    setTimeout(function () {
      if (el.parentNode) { el.remove(); }
    }, 6000);
  }

  /* --------------------------------------------------- perbarui lencana */

  function setLencana(orderId, jumlah) {
    var tautan = document.querySelector('.aksi-chat[data-order="' + orderId + '"]');
    if (!tautan) { return; }

    var lencana = tautan.querySelector('.aksi-lencana');

    if (jumlah > 0) {
      if (!lencana) {
        lencana = document.createElement('span');
        lencana.className = 'aksi-lencana';
        tautan.appendChild(lencana);
      }
      lencana.textContent = jumlah;
    } else if (lencana) {
      lencana.remove();
    }
  }

  function setSpanduk(total) {
    var el = document.getElementById('soSpanduk');
    if (!el) { return; }

    if (total > 0) {
      el.hidden = false;
      el.querySelector('strong').textContent = total;
    } else {
      el.hidden = true;
    }
  }

  /* ------------------------------------------------------------ polling */

  function ambil() {
    fetch(C.url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { return jadwal(); }

        var belum = j.belum || {};

        /* Semua tautan disisir, bukan cuma yang ada di balasan - lencana
           pesanan yang BARU SAJA dibaca di tab lain harus ikut hilang. */
        document.querySelectorAll('.aksi-chat[data-order]').forEach(function (a) {
          var id = a.dataset.order;
          setLencana(id, parseInt(belum[id], 10) || 0);
        });

        var total = parseInt(j.total, 10) || 0;
        setSpanduk(total);

        // Hanya berbunyi kalau jumlahnya BERTAMBAH. Tanpa perbandingan
        // ini, bunyinya berulang tiap 15 detik selama masih ada yang
        // belum dibaca - dan itu cepat membuat orang mematikan suara.
        if (total > totalLama) {
          bunyi();
          toast('Ada pesan baru dari customer');
          kedipJudul(total);
        } else if (total === 0) {
          berhentiKedip();
        }
        totalLama = total;

        // Tanda pesanan yang menunggu persetujuan.
        (j.menunggu || []).forEach(function (id) {
          var baris = document.querySelector('[data-order-row="' + id + '"]');
          if (baris) { baris.classList.add('is-menunggu-acc'); }
        });

        jadwal();
      })
      .catch(function () { jadwal(); });
  }

  function jadwal() {
    clearTimeout(timer);
    timer = setTimeout(ambil, JEDA);
  }

  jadwal();

  /* Hemat permintaan saat tab tidak dilihat, periksa segera saat kembali -
     penjual yang baru kembali ke tab ini ingin melihat keadaan terbaru,
     bukan menunggu 15 detik lagi. */
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      clearTimeout(timer);
    } else {
      berhentiKedip();
      clearTimeout(timer);
      timer = setTimeout(ambil, 500);
    }
  });
})();