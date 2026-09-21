(function () {
  'use strict';

  var C = window.CHAT_WIDGET;
  if (!C) { return; }

  /**
   * Merangkai URL secara langsung:
   * http://localhost/myflorist/chat/{aksi}/{nomor}/{token}
   */
  function url(aksi) {
    var base = C.baseUrl || '';
    return base + '/chat/' + aksi + '/' + encodeURIComponent(C.nomor || '') + '/' + encodeURIComponent(C.token || '');
  }

  var tombol   = document.getElementById('cwTombol');
  var panel    = document.getElementById('cwPanel');
  var isi      = document.getElementById('cwIsi');
  var form     = document.getElementById('cwForm');
  var teks     = document.getElementById('cwTeks');
  var info     = document.getElementById('cwInfo');
  var lencana  = document.getElementById('cwLencana');
  var kotakAcc = document.getElementById('cwAcc');
  var lightbox = document.getElementById('cwLightbox');
  if (!tombol || !panel || !isi) { return; }

  var sejak = 0;
  var terbuka = false;
  var belumDibaca = parseInt(lencana ? lencana.textContent : 0, 10) || 0;

  var JEDA_BUKA = 4000, JEDA_TUTUP = 30000;
  var timer = null;

  /* ------------------------------------------------------------ bantu */

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
    belumDibaca = n;
    if (!lencana) { return; }
    lencana.textContent = n;
    if (n > 0) { lencana.removeAttribute('hidden'); }
    else { lencana.setAttribute('hidden', ''); }
  }

  function bubble(m) {
    if (m.pengirim === 'sistem') {
      return '<div class="cw-sistem">' + esc(m.isi) + '</div>';
    }

    var milik = (m.pengirim === 'customer');
    var h = '<div class="cw-baris' + (milik ? ' is-saya' : '') + '"><div class="cw-gelembung">';

    if (m.image) {
      var g = C.gambar + m.image;
      h += '<img src="' + g + '" alt="" class="cw-gambar" data-besar="' + g + '" loading="lazy">';
      if (m.tipe === 'foto_acc') {
        h += '<span class="cw-tag">Untuk disetujui</span>';
      } else if (m.tipe === 'foto_lokasi') {
        h += '<span class="cw-tag">Foto di lokasi</span>';
      }
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
    if (window.CSRF && j.csrf_hash) {
      window.CSRF.name = j.csrf_name;
      window.CSRF.hash = j.csrf_hash;
    }
  }

  /** Perbarui tulisan & status tombol "Minta perbaikan". */
  function setSisaRevisi(sisa) {
    var btn = document.getElementById('cwRevisi');
    if (!btn) { return; }

    btn.textContent = 'Minta perbaikan (' + sisa + 'x)';

    /* Dimatikan kalau jatahnya habis. Membiarkannya aktif berarti customer
       menekan tombol lalu ditolak server - lebih membingungkan daripada
       tombol mati yang jelas menunjukkan sisanya nol. */
    btn.disabled = (sisa < 1);
  }

  /* ------------------------------------------------------- notifikasi */

  var judulAsli = C.judul || document.title;
  var kedipTimer = null;

  /* Judul tab berkedip - cara paling andal. Jalan di semua browser, tanpa
     izin apa pun, dan terlihat walau tab-nya di latar. */
  function kedipJudul(teks) {
    clearInterval(kedipTimer);
    var nyala = false;
    kedipTimer = setInterval(function () {
      document.title = nyala ? judulAsli : teks;
      nyala = !nyala;
    }, 1200);
  }

  function berhentiKedip() {
    clearInterval(kedipTimer);
    document.title = judulAsli;
  }

  /* Nada pendek lewat Web Audio, bukan berkas mp3 - tidak ada berkas
     tambahan yang harus diunduh dan tidak ada jeda saat pesan pertama. */
  function bunyi() {
    try {
      var AC = window.AudioContext || window.webkitAudioContext;
      if (!AC) { return; }

      var ctx = new AC();

      // Browser memblokir suara sebelum pengguna pernah menyentuh halaman.
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

  function ringkas(m) {
    if (m.tipe === 'foto_acc')    { return 'Foto rangkaian dikirim - butuh persetujuan kamu'; }
    if (m.tipe === 'foto_lokasi') { return 'Foto pengiriman dikirim'; }
    if (m.pengirim === 'sistem')  { return String(m.isi || '').slice(0, 80); }
    return String(m.isi || 'Pesan baru dari toko').slice(0, 80);
  }

  function beritahu(m) {
    var teks = ringkas(m);

    kedipJudul('(baru) ' + teks.slice(0, 30));
    bunyi();
    if (navigator.vibrate) { navigator.vibrate(180); }
    toast(teks);
  }

  /* Toast sendiri, BUKAN Notification API browser. Notification API
     meminta izin lewat dialog yang muncul tiba-tiba, dan kebanyakan orang
     menolaknya refleks - lalu pemberitahuannya hilang selamanya tanpa
     bisa dipulihkan. */
  function toast(teks) {
    if (terbuka) { return; }

    var lama = document.getElementById('cwToast');
    if (lama) { lama.remove(); }

    var el = document.createElement('button');
    el.type = 'button';
    el.id = 'cwToast';
    el.className = 'cw-toast';
    el.innerHTML = '<strong>Pesan baru dari toko</strong><span></span>';
    el.querySelector('span').textContent = teks;

    el.addEventListener('click', function () {
      el.remove();
      buka();
    });

    document.body.appendChild(el);

    // Menghilang sendiri; lencana di tombol tetap tinggal sebagai penanda.
    setTimeout(function () {
      if (el.parentNode) { el.remove(); }
    }, 8000);
  }

  /* --------------------------------------------------------- polling */

  function ambil() {
    /* dibaca=1 HANYA saat panel terbuka. Polling tetap jalan saat panel
       tertutup - kalau selalu dikirim, pesan penjual ditandai sudah dibaca
       padahal customer belum melihatnya, dan lencananya tidak pernah muncul. */
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
          } else {
            /* Pesan penjual DAN catatan sistem sama-sama dihitung.
               "Foto sudah dikirim" justru yang paling perlu diketahui,
               karena tenggat ACC mulai berjalan dari situ. */
            var dariLain = j.pesan.filter(function (m) {
              return m.pengirim !== 'customer';
            });
            if (dariLain.length) {
              beritahu(dariLain[dariLain.length - 1]);
            }
          }
        }

        /* Angka lencana diambil dari SERVER, bukan dijumlahkan di browser.
           Kalau dijumlahkan sendiri, membuka halaman di dua tab
           menghasilkan dua hitungan berbeda - dan keduanya salah. */
        if (typeof j.belum !== 'undefined') {
          setLencana(terbuka ? 0 : parseInt(j.belum, 10) || 0);
        }
        if (typeof j.sisa_revisi !== 'undefined') {
          setSisaRevisi(parseInt(j.sisa_revisi, 10) || 0);
        }

        if (j.acc_status && j.acc_status !== C.acc) {
          C.acc = j.acc_status;

          /* Tombol MUNCUL LAGI saat status kembali ke 'menunggu'.

             Versi lama hanya menyembunyikan, tidak pernah memunculkan.
             Akibatnya di siklus revisi: customer minta perbaikan (tombol
             hilang), penjual kirim foto baru (status kembali 'menunggu'),
             tapi tombolnya TIDAK kembali - customer harus memuat ulang
             halaman sendiri padahal tenggat ACC sudah berjalan. */
          if (kotakAcc) {
            kotakAcc.hidden = (j.acc_status !== 'menunggu');
          }
          var tenggat = document.getElementById('cwTenggat');
          if (tenggat) {
            tenggat.hidden = (j.acc_status !== 'menunggu');
          }
          var st = document.getElementById('cwStatus');
          if (st) {
            st.textContent = ({
              'disetujui': 'Sudah disetujui',
              'otomatis':  'Disetujui otomatis',
              'ditutup':   'Revisi ditutup',
              'revisi':    'Toko sedang memperbaiki',
              'menunggu':  'Butuh persetujuan kamu'
            })[j.acc_status] || st.textContent;
          }
        }

        jadwal();
      })
      .catch(function () { jadwal(); });
  }

  function jadwal() {
    clearTimeout(timer);
    timer = setTimeout(ambil, terbuka ? JEDA_BUKA : JEDA_TUTUP);
  }

  /* ------------------------------------------------------ buka/tutup */

  function buka() {
    terbuka = true;
    panel.hidden = false;
    berhentiKedip();

    var toastLama = document.getElementById('cwToast');
    if (toastLama) { toastLama.remove(); }

    tombol.setAttribute('aria-expanded', 'true');
    tombol.classList.add('is-aktif');
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
    jadwal();
  }

  tombol.addEventListener('click', function () { terbuka ? tutup() : buka(); });
  document.getElementById('cwTutup').addEventListener('click', tutup);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && terbuka) { tutup(); }
  });

  if (C.buka && !sessionStorage.getItem('cw_dibuka')) {
    sessionStorage.setItem('cw_dibuka', '1');
    setTimeout(buka, 1200);
  } else {
    jadwal();
  }

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
          teks.value = t;
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

  /* -------------------------------------------------------- ACC & revisi */

  var btnSetuju = document.getElementById('cwSetuju');
  var btnRevisi = document.getElementById('cwRevisi');

  if (btnSetuju) {
    btnSetuju.addEventListener('click', function () {
      if (!confirm('Setujui rangkaian ini?\n\n'
                 + 'Kata-kata papan akan dikunci dan tidak bisa diubah lagi.')) {
        return;
      }
      btnSetuju.disabled = true;

      fetch(url('setuju'), {
        method: 'POST', body: body({}), credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          csrf(j);
          btnSetuju.disabled = false;
          if (!j.ok) { return pesan(j.pesan || 'Gagal.', 'error'); }

          kotakAcc.hidden = true;
          C.acc = 'disetujui';
          pesan('Rangkaian disetujui.', 'ok');
          clearTimeout(timer);
          timer = setTimeout(ambil, 300);
        })
        .catch(function () {
          btnSetuju.disabled = false;
          pesan('Koneksi bermasalah.', 'error');
        });
    });
  }

  if (btnRevisi) {
    btnRevisi.addEventListener('click', function () {
      var catatan = prompt('Apa yang perlu diperbaiki?');
      if (catatan === null) { return; }

      catatan = catatan.trim();
      if (catatan.length < 5) {
        return pesan('Tulis minimal 5 karakter supaya toko tahu maksudnya.', 'error');
      }

      btnRevisi.disabled = true;

      fetch(url('revisi'), {
        method: 'POST', body: body({ catatan: catatan }), credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          csrf(j);
          btnRevisi.disabled = false;
          if (!j.ok) { return pesan(j.pesan || 'Gagal.', 'error'); }

          /* Disembunyikan SEMENTARA - akan muncul lagi sendiri begitu
             penjual mengirim foto perbaikan dan status kembali 'menunggu'. */
          kotakAcc.hidden = true;
          C.acc = 'revisi';

          if (typeof j.sisa_revisi !== 'undefined') {
            setSisaRevisi(parseInt(j.sisa_revisi, 10) || 0);
          }

          pesan('Permintaan perbaikan terkirim. Tunggu foto perbaikan dari toko.', 'ok');
          clearTimeout(timer);
          timer = setTimeout(ambil, 300);
        })
        .catch(function () {
          btnRevisi.disabled = false;
          pesan('Koneksi bermasalah.', 'error');
        });
    });
  }

  /* ------------------------------------------------------------ foto besar */

  isi.addEventListener('click', function (e) {
    var img = e.target.closest('[data-besar]');
    if (!img || !lightbox) { return; }
    lightbox.querySelector('img').src = img.dataset.besar;
    lightbox.hidden = false;
  });

  if (lightbox) {
    lightbox.addEventListener('click', function () { lightbox.hidden = true; });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { lightbox.hidden = true; }
    });
  }
})();