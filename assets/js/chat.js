/* =============================================================================
   chat.js - halaman percakapan penuh (pembeli)
   Simpan di: assets/js/chat.js
   ========================================================================== */
(function () {
  'use strict';

  var C = window.CHAT;
  if (!C) { return; }

  function url(aksi) {
    return C.baseUrl + '/chat/' + aksi + '/' +
      encodeURIComponent(C.nomor) + '/' + encodeURIComponent(C.token);
  }

  var kotak = document.getElementById('chatKotak');
  var form  = document.getElementById('chatForm');
  var isi   = document.getElementById('chatIsi');
  var info  = document.getElementById('chatInfo');
  if (!kotak) { return; }

  var sejak = parseInt(C.sejak, 10) || 0;
  var JEDA_MIN = 4000, JEDA_MAX = 20000, jeda = JEDA_MIN, timer = null;

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
  function pesan(t, j) { if (info) { info.textContent = t || ''; info.className = 'chat-info' + (j ? ' is-' + j : ''); } }
  function keBawah() { kotak.scrollTop = kotak.scrollHeight; }

  // Sama persis dengan v_chat_bubble.php.
  function bubble(m) {
    if (m.pengirim === 'sistem') { return '<div class="chat-sistem">' + esc(m.isi) + '</div>'; }
    var h = '<div class="chat-baris' + (m.pengirim === C.sisi ? ' is-saya' : '') + '"><div class="chat-gelembung">';
    if (m.image) {
      var g = C.gambar + m.image;
      h += '<a href="' + g + '" target="_blank" rel="noopener"><img src="' + g + '" alt="Foto" class="chat-gambar" loading="lazy"></a>';
    }
    if (m.isi) { h += '<p>' + esc(m.isi).replace(/\n/g, '<br>') + '</p>'; }
    var t = new Date(String(m.created_at).replace(' ', 'T'));
    h += '<time>' + (isNaN(t) ? '' : t.toLocaleString('id-ID', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })) + '</time></div></div>';
    return h;
  }

  function ambil() {
    fetch(url('baru') + '?sejak=' + sejak + '&dibaca=' + (document.hidden ? '0' : '1'), {
      credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j.ok && j.pesan && j.pesan.length) {
          var kosong = document.getElementById('chatKosong');
          if (kosong) { kosong.remove(); }
          j.pesan.forEach(function (m) {
            kotak.insertAdjacentHTML('beforeend', bubble(m));
            sejak = Math.max(sejak, parseInt(m.id, 10));
          });
          keBawah();
          jeda = JEDA_MIN;
        } else {
          jeda = Math.min(Math.round(jeda * 1.3), JEDA_MAX);   // melebar saat sepi
        }
        jadwal();
      })
      .catch(jadwal);
  }

  function jadwal() { clearTimeout(timer); timer = setTimeout(ambil, jeda); }

  document.addEventListener('visibilitychange', function () {
    clearTimeout(timer);
    if (!document.hidden) { jeda = JEDA_MIN; timer = setTimeout(ambil, 300); }
  });

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

      fetch(url('kirim'), { method: 'POST', body: b, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (window.CSRF && j.csrf_hash) { window.CSRF.name = j.csrf_name; window.CSRF.hash = j.csrf_hash; }
          if (!j.ok) { isi.value = t; return pesan(j.pesan || 'Gagal mengirim.', 'error'); }
          jeda = JEDA_MIN; clearTimeout(timer); timer = setTimeout(ambil, 300);
        })
        .catch(function () { isi.value = t; pesan('Koneksi bermasalah.', 'error'); });
    });
  }

  keBawah();
  jadwal();
})();
