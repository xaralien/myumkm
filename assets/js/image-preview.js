/* =============================================================================
   image-preview.js - pratinjau gambar sebelum diunggah
   Simpan di: assets/js/image-preview.js

   Memakai URL.createObjectURL, bukan FileReader.readAsDataURL. FileReader
   mengubah file jadi teks base64 yang ~33% lebih besar dan ditahan di memori;
   untuk foto 4MB dari kamera HP itu terasa nge-lag. createObjectURL hanya
   membuat penunjuk ke file yang sudah ada di disk - instan berapa pun ukurannya.
   ========================================================================== */
(function () {
  'use strict';

  var input   = document.getElementById('image');
  var preview = document.getElementById('imgPreview');
  var kotak   = document.getElementById('imgPreviewWrap');
  var info    = document.getElementById('imgInfo');
  if (!input || !preview) { return; }

  var JENIS_BOLEH = ['image/jpeg', 'image/png', 'image/webp'];
  var MAKS_BYTE   = 2 * 1024 * 1024;   // 2 MB, samakan dengan max_size di server

  var urlAsli = preview.getAttribute('src') || '';
  var urlBlob = null;

  function ukuran(byte) {
    return byte < 1024 * 1024
      ? Math.round(byte / 1024) + ' KB'
      : (byte / 1024 / 1024).toFixed(1) + ' MB';
  }

  function pesan(teks, jenis) {
    info.textContent = teks || '';
    info.className = 'img-info' + (jenis ? ' is-' + jenis : '');
  }

  function bersihkan() {
    /* Wajib dilepas. Objek URL menahan file di memori sampai halaman
       ditutup - kalau pengguna mencoba 10 foto berturut-turut tanpa ini,
       kesepuluhnya tetap tersimpan di memori browser. */
    if (urlBlob) {
      URL.revokeObjectURL(urlBlob);
      urlBlob = null;
    }
  }

  function kembalikan() {
    bersihkan();
    if (urlAsli) {
      preview.src = urlAsli;
      kotak.hidden = false;
    } else {
      kotak.hidden = true;
    }
    pesan('');
  }

  input.addEventListener('change', function () {
    var file = input.files && input.files[0];

    // Pengguna membuka dialog lalu menekan Batal.
    if (!file) { return kembalikan(); }

    /* Diperiksa di sini supaya salahnya ketahuan seketika, bukan setelah
       menunggu unggahan 2MB selesai. Server TETAP memeriksa ulang -
       pemeriksaan di browser bisa dilewati siapa saja. */
    if (JENIS_BOLEH.indexOf(file.type) === -1) {
      input.value = '';
      kembalikan();
      return pesan('Format harus JPG, PNG, atau WEBP.', 'error');
    }

    if (file.size > MAKS_BYTE) {
      input.value = '';
      kembalikan();
      return pesan('Ukuran ' + ukuran(file.size) + ', maksimal 2 MB. Perkecil dulu gambarnya.', 'error');
    }

    bersihkan();
    urlBlob = URL.createObjectURL(file);
    preview.src = urlBlob;
    kotak.hidden = false;

    pesan(file.name + ' \u00b7 ' + ukuran(file.size) + ' \u00b7 belum tersimpan, tekan Simpan', 'ok');
  });

  // Gambar rusak / tidak ditemukan: sembunyikan, jangan tampilkan ikon patah.
  preview.addEventListener('error', function () {
    kotak.hidden = true;
  });

  window.addEventListener('pagehide', bersihkan);

})();
