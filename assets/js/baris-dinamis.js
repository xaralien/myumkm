/* =============================================================================
   baris-dinamis.js - tambah/hapus baris berulang (varian, addons, dll)
   Simpan di: assets/js/baris-dinamis.js

   Satu penangan untuk semua daftar, memakai delegasi event. Sebelumnya kode
   ini disalin dua kali - sekali untuk varian, sekali untuk addons - dan tiap
   daftar baru berarti menyalinnya lagi. Delegasi juga otomatis bekerja pada
   baris yang baru ditambahkan, yang tidak dilakukan addEventListener biasa.
   ========================================================================== */
(function () {
  'use strict';

  /* --- tambah baris --- */
  document.addEventListener('click', function (e) {
    var tombol = e.target.closest('[data-tambah-baris]');
    if (!tombol) { return; }

    var list = document.getElementById(tombol.dataset.tambahBaris);
    if (!list || !list.firstElementChild) { return; }

    var baris = list.firstElementChild.cloneNode(true);
    baris.querySelectorAll('input').forEach(function (i) { i.value = ''; });
    list.appendChild(baris);

    // Fokuskan ke kolom pertama baris baru supaya bisa langsung diketik.
    var pertama = baris.querySelector('input');
    if (pertama) { pertama.focus(); }
  });

  /* --- hapus baris --- */
  document.addEventListener('click', function (e) {
    var tombol = e.target.closest('[data-hapus-baris]');
    if (!tombol) { return; }

    var list = tombol.closest('.baris-dinamis');
    if (!list) { return; }

    /* Sisakan minimal satu baris. Kalau habis, tombol "tambah" tidak punya
       contoh untuk digandakan dan daftarnya jadi mati permanen. */
    if (list.children.length > 1) {
      tombol.closest('.varian-baris').remove();
    } else {
      list.querySelectorAll('input').forEach(function (i) { i.value = ''; });
    }
  });

})();
