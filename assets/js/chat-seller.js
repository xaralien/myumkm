/* =============================================================================
   chat-seller.js - percakapan sisi penjual
   Simpan di: assets/js/chat-seller.js
   ========================================================================== */
(function () {
  'use strict';

  var C = window.CHAT_SELLER;
  if (!C) { return; }

  function url(aksi) {
    return C.base + '/seller/' + aksi + '/' + C.orderId;
  }

  var kotak = document.getElementById('chatKotak');
  var form  = document.getElementById('chatForm');
  var isi   = document.getElementById('chatIsi');
  var info  = document.getElementById('chatInfo');
  if (!kotak) { return; }

  var JEDA_MIN = 4000, JEDA_MAX = 20000;
  var jeda = JEDA_MIN, timer = null;
  var sejak = parseInt(C.sejak, 10) || 0;

  function pesan(t, j) {
    if (!info) { return; }
    info.textContent = t || '';
    info.className = 'chat-info' + (j ? ' is-' + j : '');
  }

  function keBawah() { kotak.scrollTop = kotak.scrollHeight; }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }

  /* Bentuk gelembung harus sama dengan v_chat_bubble.php - kalau berbeda,
     pesan yang baru masuk terlihat lain dari yang sudah ada. */
  function bubble(m) {
    if (m.pengirim === 'sistem') {
      return '<div class="chat-sistem">' + esc(m.isi) + '</div>';
    }

    var milik = (m.pengirim === 'seller');
    var h = '<div class="chat-baris' + (milik ? ' is-saya' : '') + '">'
          + '<div class="chat-gelembung">';

    if (m.image) {
      var g = C.gambar + m.image;
      h += '<a href="' + g + '" target="_blank" rel="noopener">'
         + '<img src="' + g + '" alt="Foto" class="chat-gambar" loading="lazy"></a>';
    }
    if (m.isi) { h += '<p>' + esc(m.isi).replace(/\n/g, '<br>') + '</p>'; }

    var t = new Date(String(m.created_at).replace(' ', 'T'));
    h += '<time>' + (isNaN(t) ? '' : t.toLocaleString('id-ID',
          { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }))
       + '</time></div></div>';
    return h;
  }

  /* ------------------------------------------------------------ polling */

  function ambil() {
    /* dibaca=0 saat tab di latar. Halaman ini selalu "terbuka", tapi
       penjual bisa sedang melihat tab lain - menandainya dibaca saat itu
       membuat lencana di daftar pesanan hilang tanpa pernah dilihat. */
    var dibaca = document.hidden ? '0' : '1';

    fetch(url('chat_baru') + '?sejak=' + sejak + '&dibaca=' + dibaca, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { return jadwal(); }

        if (j.pesan && j.pesan.length) {
          j.pesan.forEach(function (m) {
            kotak.insertAdjacentHTML('beforeend', bubble(m));
            sejak = Math.max(sejak, parseInt(m.id, 10));
          });
          keBawah();
          jeda = JEDA_MIN;

          /* Pembeli baru saja menandai pesanan diterima - panel status di
             samping perlu ikut berubah. Memuat ulang lebih andal daripada
             menyusun ulang panelnya di browser. */
          if (j.order_status && C.status && j.order_status !== C.status) {
            pesan('Status pesanan berubah. Memuat ulang...', 'ok');
            setTimeout(function () { window.location.reload(); }, 900);
            return;
          }
        } else {
          // Jeda melebar saat percakapan sepi.
          jeda = Math.min(Math.round(jeda * 1.3), JEDA_MAX);
        }
        jadwal();
      })
      .catch(function () { jadwal(); });
  }

  function jadwal() {
    clearTimeout(timer);
    timer = setTimeout(ambil, jeda);
  }

  jadwal();
  keBawah();

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      clearTimeout(timer);
    } else {
      jeda = JEDA_MIN;
      clearTimeout(timer);
      timer = setTimeout(ambil, 300);
    }
  });

  /* -------------------------------------------------------- kirim pesan */

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var t = isi.value.trim();
      if (!t) { return; }

      isi.value = '';
      pesan('');

      var b = new FormData();
      b.append('isi', t);
      if (window.CSRF) { b.append(window.CSRF.name, window.CSRF.hash); }

      fetch(url('chat_kirim'), {
        method: 'POST', body: b, credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          // Token berganti tiap permintaan; tanpa diperbarui, kiriman
          // kedua ditolak 403.
          if (window.CSRF && j.csrf_hash) {
            window.CSRF.name = j.csrf_name;
            window.CSRF.hash = j.csrf_hash;
          }
          if (!j.ok) {
            isi.value = t;   // kembalikan supaya tidak hilang
            return pesan(j.pesan || 'Gagal mengirim.', 'error');
          }
          jeda = JEDA_MIN;
          clearTimeout(timer);
          timer = setTimeout(ambil, 300);
        })
        .catch(function () {
          isi.value = t;
          pesan('Koneksi bermasalah.', 'error');
        });
    });
  }
})();