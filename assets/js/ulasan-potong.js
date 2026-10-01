/* =============================================================================
   ulasan-potong.js - tombol "Selengkapnya" pada teks ulasan
   Simpan di: assets/js/ulasan-potong.js

   Teks dipotong oleh CSS (-webkit-line-clamp). Berkas ini hanya menentukan
   PERLU TIDAKNYA tombol itu muncul: ulasan pendek tidak terpotong, dan
   tombol yang tetap tampil di sana membuat orang menekannya lalu tidak
   terjadi apa-apa.
   ========================================================================== */
(function () {
  'use strict';

  function periksa(kotak) {
    var p = kotak.querySelector('p');
    var btn = kotak.querySelector('.mb-ulasan-lanjut');
    if (!p || !btn) { return; }

    /* scrollHeight = tinggi seluruh teks, clientHeight = tinggi yang
       terlihat setelah dipotong. Selisih kecil diabaikan karena pembulatan
       tinggi baris bisa menghasilkan beda satu-dua piksel pada teks yang
       sebenarnya utuh. */
    var terpotong = p.scrollHeight - p.clientHeight > 4;
    btn.hidden = !terpotong;
  }

  function siapkan() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-potong]'), function (kotak) {
      periksa(kotak);

      var btn = kotak.querySelector('.mb-ulasan-lanjut');
      if (!btn || btn.dataset.siap) { return; }
      btn.dataset.siap = '1';

      btn.addEventListener('click', function () {
        var buka = kotak.classList.toggle('is-buka');
        btn.textContent = buka ? 'Lebih sedikit' : 'Selengkapnya';
      });
    });
  }

  siapkan();

  /* Diperiksa ulang saat lebar berubah: teks yang muat tiga baris dalam
     mode melintang bisa jadi terpotong saat ditegakkan kembali. */
  var tunda = null;
  window.addEventListener('resize', function () {
    clearTimeout(tunda);
    tunda = setTimeout(siapkan, 250);
  });
})();
