/* =============================================================================
   avatar-preview.js - pratinjau avatar toko sebelum diunggah
   Simpan di: assets/js/avatar-preview.js

   createObjectURL, bukan FileReader: FileReader mengubah berkas jadi teks
   base64 yang ~33% lebih besar dan ditahan di memori. createObjectURL hanya
   membuat penunjuk ke berkas yang sudah ada di disk.
   ========================================================================== */
(function () {
  'use strict';

  var input   = document.getElementById('avatar');
  var preview = document.getElementById('avatarPreview');
  var info    = document.getElementById('avatarInfo');
  if (!input || !preview) { return; }

  var JENIS = ['image/jpeg', 'image/png', 'image/webp'];
  var MAKS  = 1024 * 1024;          // 1 MB, samakan dengan batas di server

  var urlAsli = preview.getAttribute('src') || '';
  var urlBlob = null;

  function pesan(teks, jenis) {
    if (!info) { return; }
    info.textContent = teks || '';
    info.className = 'img-info' + (jenis ? ' is-' + jenis : '');
  }

  function bersihkan() {
    // Wajib dilepas: objek URL menahan berkas di memori sampai halaman
    // ditutup, jadi mencoba sepuluh foto berarti sepuluh berkas tertahan.
    if (urlBlob) {
      URL.revokeObjectURL(urlBlob);
      urlBlob = null;
    }
  }

  input.addEventListener('change', function () {
    var f = input.files && input.files[0];

    if (!f) {                       // dialog dibuka lalu Batal
      bersihkan();
      preview.src = urlAsli;
      return pesan('');
    }

    /* Diperiksa di sini supaya salahnya ketahuan seketika. Server TETAP
       memeriksa ulang - pemeriksaan di browser bisa dilewati siapa saja. */
    if (JENIS.indexOf(f.type) === -1) {
      input.value = '';
      bersihkan();
      preview.src = urlAsli;
      return pesan('Format harus JPG, PNG, atau WEBP.', 'error');
    }
    if (f.size > MAKS) {
      input.value = '';
      bersihkan();
      preview.src = urlAsli;
      return pesan('Ukuran ' + Math.round(f.size / 1024) + ' KB, maksimal 1 MB.', 'error');
    }

    bersihkan();
    urlBlob = URL.createObjectURL(f);
    preview.src = urlBlob;
    pesan('Belum tersimpan - tekan Simpan profil.', 'ok');
  });

  window.addEventListener('pagehide', bersihkan);
})();