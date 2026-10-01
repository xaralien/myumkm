/* =============================================================================
   chat-inbox.js - gelembung kotak masuk yang ada di semua halaman
   Simpan di: assets/js/chat-inbox.js

   Satu panel, dua lapis: daftar percakapan, lalu ruang percakapan. Alamat
   endpoint tiap percakapan datang dari server (url_baru, url_kirim), jadi
   berkas ini tidak perlu tahu bedanya sisi penjual dan sisi pembeli.

   Polling, bukan WebSocket - WebSocket butuh server tersendiri yang tidak
   ada di XAMPP maupun hosting bersama.
   ========================================================================== */
(function () {
  'use strict';

  var C = window.CHAT_INBOX;
  if (!C) { return; }

  var tombol  = document.getElementById('ibTombol');
  var panel   = document.getElementById('ibPanel');
  var lencana = document.getElementById('ibLencana');
  var elDaftar = document.getElementById('ibDaftar');
  var elRuang  = document.getElementById('ibRuang');
  var elPesan  = document.getElementById('ibPesan');
  var elJudul  = document.getElementById('ibJudul');
  var elSub    = document.getElementById('ibSub');
  var balik    = document.getElementById('ibBalik');
  var buka     = document.getElementById('ibBuka');
  var form     = document.getElementById('ibForm');
  var teks     = document.getElementById('ibTeks');
  var info     = document.getElementById('ibInfo');
  if (!tombol || !panel) { return; }

  var terbuka = false;
  var aktif   = null;      // percakapan yang sedang dibuka
  var sejak   = 0;
  var timerDaftar = null;
  var timerPesan  = null;
  var subAsli = elSub ? elSub.textContent : '';
  var elSambut = document.getElementById('ibSambut');

  /* Layar lebar: daftar dan percakapan berdampingan, daftar tidak pernah
     disembunyikan. Layar sempit: satu lapis, dua langkah.

     Dipakai matchMedia, bukan pengukuran sekali saat halaman dibuka -
     jendela yang diperkecil di tengah jalan ikut berubah bentuk. */
  var lebar = window.matchMedia('(min-width: 900px)');

  function modeLebar() { return lebar.matches; }

  /* Dua kecepatan. Panel tertutup: cukup tahu ada pesan baru untuk lencana.
     Sedang membaca satu percakapan: orang menunggu balasan. */
  var JEDA_TUTUP = 25000;
  var JEDA_BUKA  = 12000;
  var JEDA_RUANG = 4000;

  /* ------------------------------------------------------------- bantu */

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }

  function pesanInfo(t, jenis) {
    if (!info) { return; }
    info.textContent = t || '';
    info.className = 'ib-info' + (jenis ? ' is-' + jenis : '');
  }

  function jam(w) {
    var t = new Date(String(w).replace(' ', 'T'));
    if (isNaN(t)) { return ''; }

    var hariIni = new Date();
    var samaHari = t.toDateString() === hariIni.toDateString();

    // Hari ini cukup jamnya; lebih lama dari itu tanggalnya yang berguna.
    return samaHari
      ? t.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
      : t.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit' });
  }

  function setLencana(n) {
    if (!lencana) { return; }
    lencana.textContent = n > 99 ? '99+' : n;
    if (n > 0) { lencana.removeAttribute('hidden'); }
    else { lencana.setAttribute('hidden', ''); }
  }

  function csrf(j) {
    if (window.CSRF && j && j.csrf_hash) {
      window.CSRF.name = j.csrf_name;
      window.CSRF.hash = j.csrf_hash;
    }
  }

  /* ----------------------------------------------------------- daftar */

  function ambilDaftar() {
    fetch(C.daftar, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { return jadwalDaftar(); }

        csrf(j);
        setLencana(parseInt(j.total, 10) || 0);

        // Daftar hanya digambar ulang saat terlihat - menimpanya di balik
        // ruang percakapan tidak ada gunanya dan membuat posisi gulir hilang.
        if (terbuka && !aktif) { gambarDaftar(j.daftar || []); }

        jadwalDaftar();
      })
      .catch(jadwalDaftar);
  }

  function jadwalDaftar() {
    clearTimeout(timerDaftar);
    timerDaftar = setTimeout(ambilDaftar, terbuka ? JEDA_BUKA : JEDA_TUTUP);
  }

  function gambarDaftar(rows) {
    if (!rows.length) {
      elDaftar.innerHTML = '<p class="ib-kosong">Belum ada percakapan.<br>'
        + 'Percakapan muncul di sini untuk tiap pesanan yang sudah dibayar.</p>';
      return;
    }

    var h = '';
    rows.forEach(function (r, i) {
      h += '<button type="button" class="ib-baris" data-i="' + i + '">'
        +   '<span class="ib-avatar">' + esc((r.lawan || '?').charAt(0).toUpperCase()) + '</span>'
        +   '<span class="ib-isi">'
        +     '<span class="ib-atas"><strong>' + esc(r.lawan) + '</strong>'
        +       '<em>' + jam(r.waktu) + '</em></span>'
        +     '<span class="ib-cuplik">' + esc(r.cuplik || 'Belum ada pesan') + '</span>'
        +     '<span class="ib-bawah">'
        +       '<span class="ib-nomor">' + esc(r.nomor) + '</span>'
        +       '<span class="ib-status">' + esc(r.status_teks || '') + '</span>'
        +     '</span>'
        +   '</span>'
        +   (r.belum > 0 ? '<span class="ib-baris-lencana">' + r.belum + '</span>' : '')
        + '</button>';
    });

    elDaftar.innerHTML = h;

    elDaftar.querySelectorAll('.ib-baris').forEach(function (b) {
      b.addEventListener('click', function () { bukaRuang(rows[+b.dataset.i]); });
    });
  }

  /* ------------------------------------------------------------ ruang */

  function bukaRuang(r) {
    aktif = r;
    sejak = 0;
    elPesan.innerHTML = '<p class="ib-memuat">Memuat pesan...</p>';

    // Di mode lebar daftarnya tetap terlihat di sebelah kiri.
    elDaftar.hidden = ! modeLebar();
    elRuang.hidden = false;
    balik.hidden = modeLebar();
    if (elSambut) { elSambut.hidden = true; }
    if (! modeLebar()) {
      elJudul.textContent = r.lawan;
      elSub.textContent = r.nomor;
    }

    if (buka) {
      buka.hidden = false;
      buka.href = r.url_buka;
    }

    pesanInfo('');
    clearTimeout(timerPesan);
    ambilPesan();
    if (teks) {
      teks.focus();
      // Diukur ulang sesaat setelah fokus: papan ketik baru muncul
      // beberapa saat sesudahnya, bukan seketika.
      setTimeout(ukurPanel, 300);
    }
  }

  function tutupRuang() {
    aktif = null;
    clearTimeout(timerPesan);

    elRuang.hidden = true;
    elDaftar.hidden = false;
    balik.hidden = true;
    if (elSambut) { elSambut.hidden = ! modeLebar(); }
    elJudul.textContent = 'Pesan';
    elSub.textContent = subAsli;
    if (buka) { buka.hidden = true; }

    // Daftar disegarkan supaya hitungan belum dibaca ikut turun.
    clearTimeout(timerDaftar);
    ambilDaftar();
  }

  function gelembung(m) {
    if (m.pengirim === 'sistem') {
      return '<div class="ib-sistem">' + esc(m.isi) + '</div>';
    }

    var milik = (m.pengirim === C.sisi);
    var h = '<div class="ib-baris-pesan' + (milik ? ' is-saya' : '') + '"><div class="ib-gelembung">';

    if (m.image) {
      var g = C.gambar + m.image;
      h += '<a href="' + g + '" target="_blank" rel="noopener">'
         + '<img src="' + g + '" alt="Foto" loading="lazy"></a>';
    }
    if (m.isi) { h += '<p>' + esc(m.isi).replace(/\n/g, '<br>') + '</p>'; }

    h += '<time>' + jam(m.created_at) + '</time></div></div>';
    return h;
  }

  function ambilPesan() {
    if (!aktif) { return; }

    // dibaca=1: ruang percakapannya memang sedang dibuka dan dibaca.
    fetch(aktif.url_baru + '?sejak=' + sejak + '&dibaca=1', {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { return jadwalPesan(); }

        if (j.pesan && j.pesan.length) {
          var memuat = elPesan.querySelector('.ib-memuat');
          if (memuat) { memuat.remove(); }

          j.pesan.forEach(function (m) {
            elPesan.insertAdjacentHTML('beforeend', gelembung(m));
            sejak = Math.max(sejak, parseInt(m.id, 10));
          });
          elPesan.scrollTop = elPesan.scrollHeight;
        } else if (sejak === 0) {
          elPesan.innerHTML = '<p class="ib-kosong">Belum ada pesan di sini.</p>';
        }

        jadwalPesan();
      })
      .catch(jadwalPesan);
  }

  function jadwalPesan() {
    clearTimeout(timerPesan);
    if (aktif) { timerPesan = setTimeout(ambilPesan, JEDA_RUANG); }
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!aktif) { return; }

      var t = teks.value.trim();
      if (!t) { return; }

      teks.value = '';
      pesanInfo('');

      var body = new FormData();
      body.append('isi', t);
      if (window.CSRF) { body.append(window.CSRF.name, window.CSRF.hash); }

      fetch(aktif.url_kirim, {
        method: 'POST', body: body, credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          csrf(j);
          if (!j.ok) {
            teks.value = t;   // kembalikan supaya tidak hilang
            return pesanInfo(j.pesan || 'Gagal mengirim.', 'error');
          }
          clearTimeout(timerPesan);
          timerPesan = setTimeout(ambilPesan, 300);
        })
        .catch(function () {
          teks.value = t;
          pesanInfo('Koneksi bermasalah.', 'error');
        });
    });
  }

  /* ------------------------------------------------------ buka/tutup */

  function setPanel(ya) {
    terbuka = ya;
    panel.hidden = !ya;
    tombol.setAttribute('aria-expanded', ya ? 'true' : 'false');
    tombol.classList.toggle('is-aktif', ya);

    clearTimeout(timerDaftar);
    if (ya) {
      ukurPanel();
      elDaftar.innerHTML = '<p class="ib-memuat">Memuat percakapan...</p>';
      if (elSambut) { elSambut.hidden = ! modeLebar(); }
      ambilDaftar();
    } else {
      tutupRuang();
      panel.style.maxHeight = '';
      panel.style.bottom = '';
    }
  }

  tombol.addEventListener('click', function () { setPanel(panel.hidden); });
  document.getElementById('ibTutup').addEventListener('click', function () { setPanel(false); });
  balik.addEventListener('click', tutupRuang);

  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' || panel.hidden) { return; }
    // Escape mundur selangkah dulu, bukan langsung menutup semuanya.
    if (aktif) { tutupRuang(); } else { setPanel(false); }
  });

  /* Saat jendela melewati batas lebar, bentuknya disesuaikan tanpa
     kehilangan percakapan yang sedang dibuka. */
  var ubahMode = function () {
    if (panel.hidden) { return; }

    if (aktif) {
      elDaftar.hidden = ! modeLebar();
      balik.hidden = modeLebar();
      if (elSambut) { elSambut.hidden = true; }
    } else if (elSambut) {
      elSambut.hidden = ! modeLebar();
    }
  };

  if (lebar.addEventListener) { lebar.addEventListener('change', ubahMode); }
  else if (lebar.addListener) { lebar.addListener(ubahMode); }

  /* ---------------------------------------------------- papan ketik HP */

  /* Saat papan ketik muncul, tinggi layar yang benar-benar terlihat
     menyusut - tapi CSS tetap menghitung dari tinggi jendela penuh, jadi
     bagian atas panel terdorong keluar layar dan percakapannya terpotong.

     visualViewport melaporkan tinggi yang SUNGGUH terlihat, termasuk saat
     papan ketik terbuka. Nilainya dipakai membatasi tinggi panel. */
  var vv = window.visualViewport;

  function ukurPanel() {
    if (!vv || panel.hidden) { return; }

    // Hanya di layar sempit; di desktop panelnya sudah pas.
    if (window.innerWidth >= 900) {
      panel.style.maxHeight = '';
      panel.style.bottom = '';
      return;
    }

    var tertutup = window.innerHeight - vv.height - vv.offsetTop;

    // Panel diangkat setinggi papan ketik, lalu tingginya dipangkas supaya
    // tetap muat di sisa ruang.
    panel.style.bottom = (tertutup > 60 ? tertutup + 8 : 136) + 'px';
    panel.style.maxHeight = Math.max(240, vv.height - 96) + 'px';
  }

  if (vv) {
    vv.addEventListener('resize', ukurPanel);
    vv.addEventListener('scroll', ukurPanel);
  }

  // Hitungan lencana tetap berjalan walau panelnya tertutup.
  ambilDaftar();

  document.addEventListener('visibilitychange', function () {
    clearTimeout(timerDaftar);
    clearTimeout(timerPesan);
    if (document.hidden) { return; }

    ambilDaftar();
    if (aktif) { ambilPesan(); }
  });
})();
