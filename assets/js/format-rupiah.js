/* =============================================================================
   format-rupiah.js - titik ribuan pada kolom nominal
   Simpan di: assets/js/format-rupiah.js

   Pasang dengan class="input-rupiah" pada <input type="text">.

   KENAPA type="text", BUKAN type="number":
   Spesifikasi HTML hanya mengizinkan angka dan titik DESIMAL di input number.
   Begitu ada titik ribuan, browser menganggap nilainya tidak sah dan
   .value mengembalikan string kosong - jadi "285.000" tidak akan pernah
   terkirim. Karena itu dipakai type="text" + inputmode="numeric", yang tetap
   memunculkan papan ketik angka di HP.

   Titiknya dibersihkan lagi di server (Seller::bersihkan_angka_post), jadi
   tetap benar walau JavaScript gagal dimuat.
   ========================================================================== */
(function () {
  'use strict';

  function beriTitik(angka) {
    return angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  /**
   * Sisakan digit saja. Minus di depan dipertahankan - varian boleh negatif
   * untuk ukuran yang lebih kecil dari harga dasar.
   */
  function bersihkan(teks, bolehMinus) {
    var minus = bolehMinus && teks.trim().charAt(0) === '-';
    var digit = teks.replace(/[^0-9]/g, '');
    return { minus: minus, digit: digit };
  }

  function format(el) {
    var bolehMinus = el.dataset.minus === '1';
    var hasil = bersihkan(el.value, bolehMinus);

    if (hasil.digit === '') {
      el.value = hasil.minus ? '-' : '';
      return;
    }
    el.value = (hasil.minus ? '-' : '') + beriTitik(hasil.digit);
  }

  /**
   * Menulis ulang value memindahkan kursor ke ujung kanan. Kalau pengguna
   * sedang menyunting di tengah angka, itu terasa seperti bug. Jadi posisi
   * kursor dihitung ulang berdasarkan JUMLAH DIGIT di sebelah kirinya,
   * bukan jumlah karakter - karena jumlah titik ikut berubah.
   */
  function formatJagaKursor(el) {
    var posisi = el.selectionStart;
    var digitKiri = el.value.slice(0, posisi).replace(/[^0-9]/g, '').length;

    format(el);

    if (el.type !== 'text') { return; }

    var hitung = 0;
    var baru = el.value.length;
    for (var i = 0; i < el.value.length; i++) {
      if (/[0-9]/.test(el.value[i])) {
        hitung++;
        if (hitung === digitKiri) { baru = i + 1; break; }
      }
    }
    if (digitKiri === 0) { baru = el.value.charAt(0) === '-' ? 1 : 0; }

    try { el.setSelectionRange(baru, baru); } catch (e) { /* diabaikan */ }
  }

  // Delegasi: ikut bekerja pada baris varian/addons yang baru ditambahkan.
  document.addEventListener('input', function (e) {
    if (e.target.classList && e.target.classList.contains('input-rupiah')) {
      formatJagaKursor(e.target);
    }
  });

  // Rapikan nilai yang sudah ada saat halaman dibuka.
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.input-rupiah').forEach(format);
  });

  /* Baris hasil clone punya nilai kosong, tapi kalau nanti ada yang
     menggandakan baris berisi angka, formatnya ikut dirapikan. */
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-tambah-baris]')) { return; }
    setTimeout(function () {
      document.querySelectorAll('.input-rupiah').forEach(format);
    }, 0);
  });

})();
