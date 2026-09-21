/* =============================================================================
   produk-galeri.js - foto utama ikut berubah saat varian/tambahan dipilih
   Simpan di: assets/js/produk-galeri.js
   Muat SETELAH produk.js.

   Mengenali DUA bentuk markup:
     - input radio/checkbox  (yang sekarang dipakai v_produk.php)
     - tombol .btn-varian / .btn-addons dengan kelas .btn-selected

   Dibuat begitu supaya tidak diam-diam mati kalau markupnya nanti diganti -
   kegagalannya tidak menimbulkan error apa pun, cuma gambar yang tidak
   pernah berubah, dan itu sulit disadari.

   Berjalan berdampingan dengan produk.js, tidak menggantikannya: produk.js
   mengurus harga dan keranjang, berkas ini hanya mengurus gambar.
   ========================================================================== */
(function () {
  'use strict';

  var foto = document.getElementById('fotoUtama');
  var form = document.querySelector('.produk-form');
  if (!foto || !form) { return; }

  var asal   = foto.dataset.asal || foto.src;
  var galeri = document.getElementById('produkGaleri');

  /* --------------------------------------------------- membaca pilihan */

  function varianTerpilih() {
    // Bentuk input radio
    var r = form.querySelector('input[name="variant_id"]:checked');
    if (r) { return r; }

    // Bentuk tombol
    return form.querySelector('.btn-varian.btn-selected');
  }

  function addonAktif(el) {
    // Untuk checkbox, status dibaca dari .checked
    if (el.type === 'checkbox') { return el.checked; }

    // Untuk tombol, dari kelasnya
    return el.classList.contains('btn-selected');
  }

  /* ------------------------------------------------------ ganti gambar */

  function ganti(url) {
    if (!url || foto.src === url) { return; }

    /* Gambar dimuat lebih dulu di latar sebelum ditukar. Tanpa ini, foto
       lama sempat hilang beberapa saat sebelum yang baru selesai diunduh -
       terlihat berkedip, terutama di koneksi lambat. */
    var muat = new Image();
    muat.onload = function () {
      foto.src = url;
      tandaiThumb(url);
    };
    muat.onerror = function () {
      // Gambar rusak atau terhapus dari disk - biarkan yang sekarang.
    };
    muat.src = url;
  }

  function tandaiThumb(url) {
    if (!galeri) { return; }
    galeri.querySelectorAll('.produk-thumb').forEach(function (t) {
      t.classList.toggle('is-aktif', t.dataset.gambar === url);
    });
  }

  /* Urutan prioritas, dari yang paling kuat:
       1. tambahan yang TERAKHIR disentuh, kalau masih aktif dan punya gambar
       2. varian yang sedang terpilih, kalau punya gambar
       3. foto utama produk

     Tambahan menang karena itulah yang baru saja disentuh pembeli. Begitu
     dilepas, gambarnya mundur ke varian - bukan bertahan, yang akan
     membingungkan karena tidak ada lagi yang menunjukkan kenapa foto itu
     yang tampil. */
  var addonTerakhir = null;

  function segarkan() {
    if (addonTerakhir && addonAktif(addonTerakhir) && addonTerakhir.dataset.gambar) {
      return ganti(addonTerakhir.dataset.gambar);
    }

    var v = varianTerpilih();
    if (v && v.dataset.gambar) {
      return ganti(v.dataset.gambar);
    }

    ganti(asal);
  }

  /* ------------------------------------------------------------ event */

  /* Untuk input radio/checkbox, 'change' adalah kejadian yang tepat -
     ia menyala setelah .checked diperbarui browser, jadi tidak perlu
     penundaan apa pun. */
  form.addEventListener('change', function (e) {
    var t = e.target;
    if (t.name === 'addons[]') { addonTerakhir = t; }
    if (t.name === 'addons[]' || t.name === 'variant_id') { segarkan(); }
  });

  /* Untuk bentuk tombol, 'change' tidak pernah menyala. Kelas
     .btn-selected juga baru ditukar produk.js pada klik yang SAMA, jadi
     pembacaannya ditunda satu giliran - tanpa itu statusnya masih yang
     lama dan gambarnya terbalik. */
  form.addEventListener('click', function (e) {
    var t = e.target.closest('.btn-varian, .btn-addons');
    if (!t) { return; }
    if (t.classList.contains('btn-addons')) { addonTerakhir = t; }
    setTimeout(segarkan, 0);
  });

  /* Klik thumbnail HANYA mengganti gambar, tidak mengubah pilihan varian
     maupun harga. Pembeli yang ingin melihat-lihat foto tidak seharusnya
     mengubah apa yang akan dibelinya. */
  if (galeri) {
    galeri.addEventListener('click', function (e) {
      var t = e.target.closest('.produk-thumb');
      if (t) { ganti(t.dataset.gambar); }
    });
  }

  segarkan();
})();