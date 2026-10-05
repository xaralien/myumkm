/* =============================================================================
   chat.js - halaman percakapan penuh (pembeli)
   Simpan di: assets/js/chat.js
   ========================================================================== */
(function () {
  'use strict';

  var C = window.CHAT;
  if (!C) { return; }

  /* Dua bentuk percakapan memakai berkas ini:
       pesanan     -> chat/{aksi}/{nomor}/{token}
       tanya toko  -> chat/conv_{aksi}/{id}
     Yang membedakan hanya alamatnya; isi dan perilakunya sama. */
  function url(aksi) {
    if (C.conv) {
      return C.baseUrl + '/chat/conv_' + aksi + '/' + C.conv;
    }
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
    /* Markup HARUS sama persis dengan v_chat_bubble.php. Kalau berbeda,
       pesan yang baru dikirim tampil dengan bentuk lain daripada pesan yang
       sama setelah halaman dimuat ulang - dan itu terlihat seperti kerusakan
       walau datanya benar. */
    if (m.produk_nama) {
      h += '<div class="chat-produk">'
         + '<a class="chat-produk-isi" href="' + (m.produk_url || '#') + '">'
         +   '<img src="' + C.gambar + esc(m.produk_gambar) + '" alt="" loading="lazy">'
         +   '<span><strong>' + esc(m.produk_nama) + '</strong>'
         +   '<em>' + esc(m.produk_harga_teks || '') + '</em></span>'
         + '</a>';

      // Tombol hanya di sisi pembeli - sama seperti aturan di view.
      if (C.sisi === 'customer' && m.product_id) {
        h += '<div class="chat-produk-aksi">'
           +   '<button type="button" class="chat-produk-btn btn-add" data-id="' + m.product_id + '">+ Keranjang</button>'
           +   '<a class="chat-produk-btn is-utama" href="' + (m.produk_url || '#') + '">Beli</a>'
           + '</div>';
      }
      h += '</div>';
    }
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

      /* Produk lampiran ikut sekali, menyatu dengan pesan ini. Setelah
         terkirim lampirannya dilepas - pertanyaan berikutnya di percakapan
         yang sama tidak perlu mengulang kartu yang sama. */
      var lampiran = document.getElementById('chatLampiran');
      if (lampiran) { b.append('product_id', lampiran.dataset.produk); }

      if (window.CSRF) { b.append(window.CSRF.name, window.CSRF.hash); }

      fetch(url('kirim'), { method: 'POST', body: b, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (window.CSRF && j.csrf_hash) { window.CSRF.name = j.csrf_name; window.CSRF.hash = j.csrf_hash; }
          if (!j.ok) { isi.value = t; return pesan(j.pesan || 'Gagal mengirim.', 'error'); }

          var l = document.getElementById('chatLampiran');
          if (l) { l.remove(); }

          /* '?produk=' dibuang dari alamat. Tanpa ini, memuat ulang halaman
             memunculkan lampiran yang sama lagi - padahal produknya sudah
             terkirim, dan pembeli akan mengirimnya dua kali tanpa sadar. */
          if (window.history && history.replaceState && location.search.indexOf('produk=') !== -1) {
            history.replaceState(null, '', location.pathname);
          }

          jeda = JEDA_MIN; clearTimeout(timer); timer = setTimeout(ambil, 300);
        })
        .catch(function () { isi.value = t; pesan('Koneksi bermasalah.', 'error'); });
    });
  }

  /* Batalkan lampiran. Pembeli yang hanya ingin bertanya hal lain tidak
     terpaksa mengirim kartu produknya. */
  var batal = document.getElementById('chatLampiranBatal');
  if (batal) {
    batal.addEventListener('click', function () {
      var l = document.getElementById('chatLampiran');
      if (l) { l.remove(); }
      if (isi) { isi.focus(); }
    });
  }

  keBawah();
  jadwal();
})();
