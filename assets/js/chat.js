/* =============================================================================
   chat.js - percakapan pesanan
   Simpan di: assets/js/chat.js

   Memakai polling, bukan WebSocket. Alasannya sama seperti pemantauan
   pembayaran: WebSocket butuh server tersendiri yang tidak ada di XAMPP
   maupun hosting bersama. Untuk percakapan dua orang yang berlangsung
   beberapa menit, jeda 4 detik tidak terasa.
   ========================================================================== */
(function () {
  'use strict';

  var C = window.CHAT;
  if (!C) { return; }

  var kotak = document.getElementById('chatKotak');
  var form  = document.getElementById('chatForm');
  var isi   = document.getElementById('chatIsi');
  var info  = document.getElementById('chatInfo');
  if (!kotak) { return; }

  var JEDA_MIN = 4000;
  var JEDA_MAX = 20000;
  var jeda     = JEDA_MIN;
  var timer    = null;

  function pesan(teks, jenis) {
    if (!info) { return; }
    info.textContent = teks || '';
    info.className = 'chat-info' + (jenis ? ' is-' + jenis : '');
  }

  function keBawah() { kotak.scrollTop = kotak.scrollHeight; }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }

  /* Gelembung disusun di sini juga, bukan hanya di PHP. Bentuknya harus
     sama persis dengan v_chat_bubble.php - kalau berbeda, pesan yang baru
     masuk akan terlihat lain dari pesan yang sudah ada sampai halaman
     dimuat ulang. */
  function gambarBubble(m) {
    if (m.pengirim === 'sistem') {
      return '<div class="chat-sistem">' + esc(m.isi) + '</div>';
    }

    var milik = (m.pengirim === C.sisi);
    var h = '<div class="chat-baris' + (milik ? ' is-saya' : '') + '">'
          + '<div class="chat-gelembung">';

    if (m.image) {
      var url = C.gambar + m.image;
      h += '<a href="' + url + '" target="_blank" rel="noopener">'
         + '<img src="' + url + '" alt="" class="chat-gambar" loading="lazy"></a>';
      if (m.tipe === 'foto_acc') {
        h += '<span class="chat-tag">Foto untuk disetujui</span>';
      } else if (m.tipe === 'foto_lokasi') {
        h += '<span class="chat-tag">Foto di lokasi</span>';
      }
    }
    if (m.isi) { h += '<p>' + esc(m.isi).replace(/\n/g, '<br>') + '</p>'; }

    var t = new Date(m.created_at.replace(' ', 'T'));
    h += '<time>' + (isNaN(t) ? '' : t.toLocaleString('id-ID',
          { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }))
       + '</time></div></div>';
    return h;
  }

  function body(data) {
    var b = new FormData();
    Object.keys(data || {}).forEach(function (k) { b.append(k, data[k]); });
    if (window.CSRF) { b.append(window.CSRF.name, window.CSRF.hash); }
    return b;
  }

  function segarkanCsrf(j) {
    // Token berganti tiap permintaan; tanpa diperbarui, kiriman kedua
    // ditolak 403.
    if (window.CSRF && j.csrf_hash) {
      window.CSRF.name = j.csrf_name;
      window.CSRF.hash = j.csrf_hash;
    }
  }

  /* ------------------------------------------------------------ polling */

  function ambil() {
    fetch(C.base + '/baru?sejak=' + C.sejak, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { return jadwalkan(); }

        if (j.pesan && j.pesan.length) {
          j.pesan.forEach(function (m) {
            kotak.insertAdjacentHTML('beforeend', gambarBubble(m));
            C.sejak = Math.max(C.sejak, parseInt(m.id, 10));
          });
          keBawah();

          // Ada yang membalas - percepat lagi.
          jeda = JEDA_MIN;
        } else {
          /* Jeda melebar bertahap saat percakapan sepi, supaya tab yang
             ditinggal terbuka tidak terus-menerus menanyai server. */
          jeda = Math.min(Math.round(jeda * 1.3), JEDA_MAX);
        }

        // Tombol ACC menghilang begitu statusnya bukan lagi menunggu -
        // termasuk saat disetujui otomatis karena tenggat lewat.
        var aksi = document.getElementById('accAksi');
        if (aksi && j.acc_status !== 'menunggu') { aksi.hidden = true; }

        jadwalkan();
      })
      .catch(function () { jadwalkan(); });
  }

  function jadwalkan() {
    clearTimeout(timer);
    timer = setTimeout(ambil, jeda);
  }

  jadwalkan();
  keBawah();

  // Hemat permintaan saat tab tidak dilihat, periksa segera saat kembali.
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
      var teks = isi.value.trim();
      if (!teks) { return; }

      isi.value = '';
      pesan('');

      fetch(C.base + '/kirim', {
        method: 'POST', body: body({ isi: teks }), credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          segarkanCsrf(j);
          if (!j.ok) {
            isi.value = teks;   // kembalikan supaya tidak hilang
            return pesan(j.pesan || 'Gagal mengirim.', 'error');
          }
          jeda = JEDA_MIN;
          clearTimeout(timer);
          timer = setTimeout(ambil, 300);
        })
        .catch(function () {
          isi.value = teks;
          pesan('Koneksi bermasalah. Coba lagi.', 'error');
        });
    });
  }

  /* ---------------------------------------------------------- ACC & revisi */

  var btnSetuju = document.getElementById('btnSetuju');
  var btnRevisi = document.getElementById('btnRevisi');

  if (btnSetuju) {
    btnSetuju.addEventListener('click', function () {
      /* Konfirmasi diberi peringatan tegas: setelah disetujui, kata-kata
         papan dikunci dan tidak ada jalan mundur. */
      if (!confirm('Setujui rangkaian ini?\n\n'
                 + 'Setelah disetujui, kata-kata papan dikunci dan tidak bisa '
                 + 'diubah lagi. Bunga akan langsung disiapkan untuk dikirim.')) {
        return;
      }
      btnSetuju.disabled = true;

      fetch(C.base + '/setuju', {
        method: 'POST', body: body({}), credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          segarkanCsrf(j);
          btnSetuju.disabled = false;
          if (!j.ok) { return pesan(j.pesan || 'Gagal menyetujui.', 'error'); }
          window.location.reload();
        })
        .catch(function () {
          btnSetuju.disabled = false;
          pesan('Koneksi bermasalah.', 'error');
        });
    });
  }

  if (btnRevisi) {
    btnRevisi.addEventListener('click', function () {
      var catatan = prompt('Apa yang perlu diperbaiki?\n'
                         + '(tulis sejelas mungkin supaya toko tidak menebak)');
      if (catatan === null) { return; }

      catatan = catatan.trim();
      if (catatan.length < 5) {
        return pesan('Tulis minimal 5 karakter supaya toko tahu apa yang diminta.', 'error');
      }

      btnRevisi.disabled = true;

      fetch(C.base + '/revisi', {
        method: 'POST', body: body({ catatan: catatan }), credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          segarkanCsrf(j);
          btnRevisi.disabled = false;
          if (!j.ok) { return pesan(j.pesan || 'Gagal mengirim permintaan.', 'error'); }
          window.location.reload();
        })
        .catch(function () {
          btnRevisi.disabled = false;
          pesan('Koneksi bermasalah.', 'error');
        });
    });
  }
})();