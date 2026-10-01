/* =============================================================================
   media-penuh.js - penampil foto & video ulasan, layar penuh
   Simpan di: assets/js/media-penuh.js

   Dibangun sendiri, bukan memakai komponen modal Bootstrap: komponen itu
   membawa pengelolaan fokus, animasi, dan kelas pada <body> yang bentrok
   dengan panel lain di halaman ini (lembar akun, gelembung chat). Yang
   dibutuhkan di sini cuma satu lapisan gelap berisi satu media.

   Cara pakai di markup:
     <a class="ul-galeri-item" data-penuh href="{url}" data-tipe="foto">
   Semua elemen ber-atribut data-penuh di halaman akan dikumpulkan jadi satu
   deretan, sehingga tombol panah bisa berpindah antarmedia.
   ========================================================================== */
(function () {
  'use strict';

  var daftar = [];
  var posisi = 0;
  var lapisan = null;

  function kumpulkan() {
    daftar = Array.prototype.slice.call(document.querySelectorAll('[data-penuh]'));
  }

  /* --------------------------------------------------------------- bangun */

  function bangun() {
    if (lapisan) { return lapisan; }

    lapisan = document.createElement('div');
    lapisan.className = 'mp-lapisan';
    lapisan.setAttribute('role', 'dialog');
    lapisan.setAttribute('aria-label', 'Foto dan video ulasan');
    lapisan.hidden = true;

    lapisan.innerHTML =
        '<button type="button" class="mp-tutup" aria-label="Tutup">&times;</button>'
      + '<button type="button" class="mp-nav mp-prev" aria-label="Sebelumnya">'
      +   '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"></path></svg>'
      + '</button>'
      + '<div class="mp-isi"></div>'
      + '<button type="button" class="mp-nav mp-next" aria-label="Berikutnya">'
      +   '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"></path></svg>'
      + '</button>'
      + '<p class="mp-hitung"></p>';

    document.body.appendChild(lapisan);

    lapisan.querySelector('.mp-tutup').addEventListener('click', tutup);
    lapisan.querySelector('.mp-prev').addEventListener('click', function (e) {
      e.stopPropagation();
      geser(-1);
    });
    lapisan.querySelector('.mp-next').addEventListener('click', function (e) {
      e.stopPropagation();
      geser(1);
    });

    /* Klik pada latar gelap menutup; klik pada medianya sendiri tidak -
       kalau ikut menutup, menekan tombol putar video jadi menutup
       tampilannya. */
    lapisan.addEventListener('click', function (e) {
      if (e.target === lapisan || e.target.classList.contains('mp-isi')) {
        tutup();
      }
    });

    return lapisan;
  }

  /* ---------------------------------------------------------------- isi */

  function tampilkan() {
    var el = daftar[posisi];
    if (!el) { return; }

    var isi = lapisan.querySelector('.mp-isi');
    isi.innerHTML = '';

    var src  = el.getAttribute('href') || el.dataset.src;
    var tipe = el.dataset.tipe === 'video' ? 'video' : 'foto';

    if (tipe === 'video') {
      var v = document.createElement('video');
      v.src = src;
      v.controls = true;
      v.autoplay = true;
      v.playsInline = true;
      v.className = 'mp-media';
      isi.appendChild(v);
    } else {
      var g = document.createElement('img');
      g.src = src;
      g.alt = 'Foto dari pembeli';
      g.className = 'mp-media';
      isi.appendChild(g);
    }

    var banyak = daftar.length > 1;
    lapisan.querySelector('.mp-prev').hidden = !banyak;
    lapisan.querySelector('.mp-next').hidden = !banyak;
    lapisan.querySelector('.mp-hitung').textContent =
      banyak ? (posisi + 1) + ' / ' + daftar.length : '';
  }

  function geser(arah) {
    hentikanVideo();
    // Berputar: dari terakhir ke pertama, supaya tidak ada ujung buntu.
    posisi = (posisi + arah + daftar.length) % daftar.length;
    tampilkan();
  }

  function hentikanVideo() {
    var v = lapisan && lapisan.querySelector('video');
    if (v) { v.pause(); }
  }

  /* ------------------------------------------------------- buka & tutup */

  function buka(el) {
    kumpulkan();
    posisi = daftar.indexOf(el);
    if (posisi < 0) { posisi = 0; }

    bangun();
    lapisan.hidden = false;

    /* Halaman di belakang dikunci - tanpa ini, menggulir di atas lapisan
       ikut menggerakkan halaman di baliknya, dan posisi bacanya hilang
       begitu tampilan ditutup. */
    document.body.style.overflow = 'hidden';

    tampilkan();
    lapisan.querySelector('.mp-tutup').focus();
  }

  function tutup() {
    if (!lapisan) { return; }
    hentikanVideo();
    lapisan.hidden = true;
    document.body.style.overflow = '';
  }

  /* ------------------------------------------------------------ pemicu */

  document.addEventListener('click', function (e) {
    var el = e.target.closest ? e.target.closest('[data-penuh]') : null;
    if (!el) { return; }

    e.preventDefault();
    buka(el);
  });

  document.addEventListener('keydown', function (e) {
    if (!lapisan || lapisan.hidden) { return; }

    if (e.key === 'Escape')     { tutup(); }
    if (e.key === 'ArrowLeft')  { geser(-1); }
    if (e.key === 'ArrowRight') { geser(1); }
  });

  /* Sapuan jari di HP. Hanya gerakan mendatar yang dihitung - gerakan
     menurun biasanya niat menggulir, bukan berpindah media. */
  var mulaiX = null, mulaiY = null;

  document.addEventListener('touchstart', function (e) {
    if (!lapisan || lapisan.hidden) { return; }
    mulaiX = e.touches[0].clientX;
    mulaiY = e.touches[0].clientY;
  }, { passive: true });

  document.addEventListener('touchend', function (e) {
    if (mulaiX === null || !lapisan || lapisan.hidden) { return; }

    var dx = e.changedTouches[0].clientX - mulaiX;
    var dy = e.changedTouches[0].clientY - mulaiY;

    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
      geser(dx < 0 ? 1 : -1);
    }
    mulaiX = mulaiY = null;
  }, { passive: true });
})();
