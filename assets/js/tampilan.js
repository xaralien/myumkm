/* =============================================================================
   tampilan.js - menyesuaikan tampilan dengan lebar layar sungguhan
   Simpan di: assets/js/tampilan.js

   Server hanya bisa MENEBAK tampilan mana yang cocok: browser tidak
   mengirimkan lebar layar pada permintaan pertama. Berkas ini mengukur
   lebar sungguhan dan membetulkan tebakan itu - termasuk saat jendela
   diubah ukurannya, misalnya lewat mode perangkat di DevTools.

   Tidak berlaku kalau pengguna MENGUNCI pilihannya lewat sakelar di menu
   profil. Kalau ikut ditimpa, sakelar itu jadi tidak berguna: ditekan,
   lalu langsung dikembalikan lagi oleh skrip ini.
   ========================================================================== */
(function () {
  'use strict';

  var C = window.TAMPILAN;
  if (!C || C.manual) { return; }

  /* 820px: di bawah ini tata letak dua kolom mulai terasa sempit, di atasnya
     tampilan HP terlihat kosong melompong. Angka yang sama dipakai untuk
     kedua arah supaya tidak ada wilayah abu-abu yang membuat halaman
     memuat ulang bolak-balik. */
  var BATAS = 820;

  function cocok() {
    var lebar = window.innerWidth || document.documentElement.clientWidth;
    return (lebar <= BATAS) ? 'mobile' : 'desktop';
  }

  function simpan(mode) {
    document.cookie = 'tampilan_lebar=' + mode + ';path=/;max-age=31536000;samesite=lax';
  }

  function sesuaikan() {
    var perlu = cocok();
    if (perlu === C.mode) { return; }

    simpan(perlu);
    window.location.reload();
  }

  // Pemuatan pertama: betulkan kalau tebakan server meleset.
  sesuaikan();

  /* Perubahan ukuran jendela. Ditunda sesaat supaya menyeret tepi jendela
     tidak memicu pemuatan ulang berkali-kali - yang dihitung hanya ukuran
     setelah orang berhenti menyeret. */
  var tunda = null;
  window.addEventListener('resize', function () {
    clearTimeout(tunda);
    tunda = setTimeout(sesuaikan, 400);
  });
})();
