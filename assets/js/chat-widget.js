/* =============================================================================
   chat-widget.js - percakapan pembeli-penjual, melayang di kanan bawah
   Simpan di: assets/js/chat-widget.js

   Polling, bukan WebSocket - WebSocket butuh server tersendiri yang tidak
   ada di XAMPP maupun hosting bersama.
   ========================================================================== */
(function () {
  'use strict';

  var C = window.CHAT_WIDGET;
  if (!C) { return; }

  /** {baseUrl}/chat/{aksi}/{nomor}/{token} */
  function url(aksi) {
    return (C.baseUrl || '') + '/chat/' + aksi + '/' +
      encodeURIComponent(C.nomor || '') + '/' + encodeURIComponent(C.token || '');
  }

  var tombol   = document.getElementById('cwTombol');
  var panel    = document.getElementById('cwPanel');
  var isi      = document.getElementById('cwIsi');
  var form     = document.getElementById('cwForm');
  var teks     = document.getElementById('cwTeks');
  var info     = document.getElementById('cwInfo');
  var lencana  = document.getElementById('cwLencana');
  var lightbox = document.getElementById('cwLightbox');
  if (!tombol || !panel || !isi) { return; }

  var sejak = 0;
  var terbuka = false;
  var pertama = true;

  /* Panel terbuka: pembeli sedang menunggu balasan, 4 detik wajar.
     Tertutup: cukup tahu ada pesan baru untuk lencana, 30 detik. */
  var JEDA_BUKA = 4000, JEDA_TUTUP = 30000;
  var timer = null;

  /* ------------------------------------------------------------- bantu */

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }

  function pesan(t, jenis) {
    if (!info) { return; }
    info.textContent = t || '';
    info.className = 'cw-info' + (jenis ? ' is-' + jenis : '');
  }

  function keBawah() { isi.scrollTop = isi.scrollHeight; }

  function setLencana(n) {
    if (!lencana) { return; }
    lencana.textContent = n;
    if (n > 0) { lencana.removeAttribute('hidden'); }
    else { lencana.setAttribute('hidden', ''); }
  }

  /* Bentuknya harus sama dengan v_chat_bubble.php - kalau berbeda, pesan
     yang baru masuk terlihat lain dari yang sudah ada. */
  function bubble(m) {
    if (m.pengirim === 'sistem') {
      return '<div class="cw-sistem">' + esc(m.isi) + '</div>';
    }
    var milik = (m.pengirim === 'customer');
    var h = '<div class="cw-baris' + (milik ? ' is-saya' : '') + '"><div class="cw-gelembung">';

    if (m.image) {
      var g = C.gambar + m.image;
      h += '<img src="' + g + '" alt="Foto dari toko" class="cw-gambar" data-besar="' + g + '" loading="lazy">';
    }
    if (m.isi) { h += '<p>' + esc(m.isi).replace(/\n/g, '<br>') + '</p>'; }

    var t = new Date(String(m.created_at).replace(' ', 'T'));
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

  function csrf(j) {
    if (window.CSRF && j && j.csrf_hash) {
      window.CSRF.name = j.csrf_name;
      window.CSRF.hash = j.csrf_hash;
    }
  }

  /* -------------------------------------------------------- notifikasi */

  var judulAsli = document.title;
  var kedipTimer = null;

  // Judul tab berkedip: jalan di semua browser, tanpa izin, terlihat
  // walau tab-nya di latar.
  function kedipJudul(t) {
    clearInterval(kedipTimer);
    var nyala = false;
    kedipTimer = setInterval(function () {
      document.title = nyala ? judulAsli : t;
      nyala = !nyala;
    }, 1200);
  }

  function berhentiKedip() {
    clearInterval(kedipTimer);
    document.title = judulAsli;
  }

  // Nada pendek lewat Web Audio - tidak ada berkas suara yang diunduh.
  function bunyi() {
    try {
      var AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) { return; }
      var ctx = new AC();
      // Browser memblokir suara sebelum pengguna menyentuh halaman.
      if (ctx.state === 'suspended') { return; }
      var osc = ctx.createOscillator(), gain = ctx.createGain();
      osc.connect(gain); gain.connect(ctx.destination);
      osc.frequency.value = 880;
      gain.gain.setValueAtTime(0.0001, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.35);
      osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.36);
      setTimeout(function () { ctx.close(); }, 600);
    } catch (e) { /* suara gagal bukan alasan menghentikan apa pun */ }
  }

  /* Toast sendiri, bukan Notification API - itu meminta izin lewat dialog
     yang kebanyakan orang tolak refleks, lalu hilang selamanya. */
  function toast(t) {
    if (terbuka) { return; }
    var lama = document.getElementById('cwToast');
    if (lama) { lama.remove(); }

    var el = document.createElement('button');
    el.type = 'button';
    el.id = 'cwToast';
    el.className = 'cw-toast';
    el.innerHTML = '<strong>Pesan baru dari toko</strong><span></span>';
    el.querySelector('span').textContent = t;
    el.addEventListener('click', function () { el.remove(); buka(); });

    document.body.appendChild(el);
    setTimeout(function () { if (el.parentNode) { el.remove(); } }, 8000);
  }

  function beritahu(m) {
    var t = m.image && !m.isi ? 'Toko mengirim foto' : String(m.isi || 'Pesan baru').slice(0, 80);
    kedipJudul('(baru) ' + t.slice(0, 30));
    bunyi();
    if (navigator.vibrate) { navigator.vibrate(180); }
    toast(t);
  }

  /* ----------------------------------------------------------- polling */

  function ambil() {
    /* dibaca=1 HANYA saat panel terbuka. Kalau selalu dikirim, pesan toko
       ditandai sudah dibaca padahal pembeli belum melihatnya. */
    fetch(url('baru') + '?sejak=' + sejak + '&dibaca=' + (terbuka ? '1' : '0'), {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { return jadwal(); }

        if (j.pesan && j.pesan.length) {
          var memuat = isi.querySelector('.cw-memuat');
          if (memuat) { memuat.remove(); }

          j.pesan.forEach(function (m) {
            isi.insertAdjacentHTML('beforeend', bubble(m));
            sejak = Math.max(sejak, parseInt(m.id, 10));
          });

          if (terbuka) {
            keBawah();
          } else if (!pertama) {
            // Pemuatan pertama berisi riwayat lama - bukan "pesan baru".
            var dariToko = j.pesan.filter(function (m) { return m.pengirim !== 'customer'; });
            if (dariToko.length) { beritahu(dariToko[dariToko.length - 1]); }
          }
        } else if (pertama) {
          var kosong = isi.querySelector('.cw-memuat');
          if (kosong) { kosong.textContent = 'Belum ada pesan. Tanyakan apa saja soal pesananmu.'; }
        }

        // Angka lencana dari SERVER - dua tab tetap menunjukkan angka sama.
        if (typeof j.belum !== 'undefined') {
          setLencana(terbuka ? 0 : parseInt(j.belum, 10) || 0);
        }

        pertama = false;
        jadwal();
      })
      .catch(function () { jadwal(); });
  }

  function jadwal() {
    clearTimeout(timer);
    timer = setTimeout(ambil, terbuka ? JEDA_BUKA : JEDA_TUTUP);
  }

  /* -------------------------------------------------------- buka/tutup */

  function buka() {
    terbuka = true;
    panel.hidden = false;
    tombol.setAttribute('aria-expanded', 'true');
    tombol.classList.add('is-aktif');
    berhentiKedip();
    var t = document.getElementById('cwToast');
    if (t) { t.remove(); }
    setLencana(0);
    keBawah();
    if (teks) { teks.focus(); }
    clearTimeout(timer);
    timer = setTimeout(ambil, 300);
  }

  function tutup() {
    terbuka = false;
    panel.hidden = true;
    tombol.setAttribute('aria-expanded', 'false');
    tombol.classList.remove('is-aktif');
    tombol.focus();
    jadwal();
  }

  tombol.addEventListener('click', function () { terbuka ? tutup() : buka(); });
  document.getElementById('cwTutup').addEventListener('click', tutup);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (lightbox && !lightbox.hidden) { lightbox.hidden = true; return; }
      if (terbuka) { tutup(); }
    }
  });

  // Muat riwayat sekali di awal (tanpa menandai dibaca), lalu berkala.
  ambil();

  /* ------------------------------------------------------ kirim pesan */

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var t = teks.value.trim();
    if (!t) { return; }

    teks.value = '';
    pesan('');

    fetch(url('kirim'), {
      method: 'POST', body: body({ isi: t }), credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        csrf(j);
        if (!j.ok) {
          teks.value = t;   // kembalikan supaya tidak hilang
          return pesan(j.pesan || 'Gagal mengirim.', 'error');
        }
        clearTimeout(timer);
        timer = setTimeout(ambil, 300);
      })
      .catch(function () {
        teks.value = t;
        pesan('Koneksi bermasalah.', 'error');
      });
  });

  /* ------------------------------------------------------- foto besar */

  isi.addEventListener('click', function (e) {
    var img = e.target.closest('[data-besar]');
    if (!img || !lightbox) { return; }
    lightbox.querySelector('img').src = img.dataset.besar;
    lightbox.hidden = false;
  });
  if (lightbox) {
    lightbox.addEventListener('click', function () { lightbox.hidden = true; });
  }
})();
