/* =============================================================================
   baris-gambar.js - pratinjau gambar pada baris varian & tambahan
   Simpan di: assets/js/baris-gambar.js
   Muat SETELAH baris-dinamis.js.
   ========================================================================== */
(function () {
  'use strict';

  var JENIS = ['image/jpeg', 'image/png', 'image/webp'];
  var MAKS  = 2 * 1024 * 1024;

  function pratinjau(input) {
    var kotak = input.closest('.baris-gambar');
    var img   = kotak.querySelector('.baris-gambar-pratinjau');
    var teks  = kotak.querySelector('.baris-gambar-teks');
    var baris = input.closest('.varian-baris');
    var hapus = baris.querySelector('input[name$="_hapus[]"]');

    var f = input.files && input.files[0];
    if (!f) { return; }

    if (JENIS.indexOf(f.type) === -1) {
      input.value = '';
      alert('Gambar harus JPG, PNG, atau WEBP.');
      return;
    }
    if (f.size > MAKS) {
      input.value = '';
      alert('Gambar maksimal 2 MB. Ukuran berkas ini '
            + Math.round(f.size / 1024) + ' KB.');
      return;
    }

    /* createObjectURL, bukan FileReader: FileReader mengubah berkas jadi
       teks base64 yang ~33% lebih besar dan ditahan di memori. Di form
       dengan banyak baris, bedanya terasa. */
    if (img.dataset.blob) { URL.revokeObjectURL(img.dataset.blob); }

    var url = URL.createObjectURL(f);
    img.dataset.blob = url;
    img.src = url;
    img.classList.remove('is-kosong');
    teks.textContent = 'Ganti';

    // Batalkan tanda hapus - penjual jelas ingin memakai gambar baru ini.
    if (hapus) { hapus.value = '0'; }
  }

  // Delegasi: ikut bekerja pada baris yang baru ditambahkan.
  document.addEventListener('change', function (e) {
    if (e.target.matches('.baris-gambar input[type="file"]')) {
      pratinjau(e.target);
    }
  });

  /* Baris hasil clone membawa src gambar baris pertama. Dikosongkan,
     termasuk input tersembunyi gambar_lama - kalau tidak, baris baru akan
     "mewarisi" gambar milik baris lain. */
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-tambah-baris]')) { return; }

    setTimeout(function () {
      var list = document.getElementById(
        e.target.closest('[data-tambah-baris]').dataset.tambahBaris
      );
      if (!list) { return; }

      var baris = list.lastElementChild;
      if (!baris) { return; }

      var img = baris.querySelector('.baris-gambar-pratinjau');
      if (img) {
        img.removeAttribute('src');
        img.removeAttribute('data-blob');
        img.classList.add('is-kosong');
      }
      var teks = baris.querySelector('.baris-gambar-teks');
      if (teks) { teks.textContent = 'Foto'; }

      baris.querySelectorAll('input[type="hidden"]').forEach(function (h) {
        h.value = h.name.indexOf('_hapus') > -1 ? '0' : '';
      });
    }, 0);
  });

  window.addEventListener('pagehide', function () {
    document.querySelectorAll('.baris-gambar-pratinjau[data-blob]').forEach(function (i) {
      URL.revokeObjectURL(i.dataset.blob);
    });
  });
})();