/* =============================================================================
   gps.js - menentukan lokasi pembeli lewat GPS perangkat
   Simpan di: assets/js/gps.js

   Menggantikan pemilihan wilayah bertingkat (provinsi > kota > kecamatan).
   Alasannya: pembeli jarang tahu nama kecamatannya sendiri, apalagi saat
   sedang bepergian - dan tiga dropdown berturut-turut adalah tiga
   kesempatan untuk menyerah sebelum melihat satu produk pun.

   Lokasi di sini hanya MENGURUTKAN hasil, tidak menyaring. Pembeli yang
   menolak izin GPS tetap melihat seluruh katalog, hanya tidak diurutkan
   dari yang terdekat.
   ========================================================================== */
(function () {
  'use strict';

  var C = window.GPS;
  if (!C) { return; }

  var toast = null, toastTimer = null;

  /**
   * Tampilkan pesan status.
   *
   * Dulu pesan hanya ditulis ke elemen #gpsInfo. Elemen itu tidak ada di
   * semua halaman, dan di halaman yang punya pun letaknya di bawah layar -
   * jadi tombol lokasi terlihat "tidak bekerja" padahal sebenarnya izinnya
   * ditolak. Sekarang pesan selalu muncul melayang di bawah layar, di mana
   * pun tombolnya ditekan.
   */
  function pesan(teks, jenis) {
    var el = document.getElementById('gpsInfo');
    if (el) {
      el.textContent = teks || '';
      el.className = 'gps-info' + (jenis ? ' is-' + jenis : '');
    }

    if (!teks) { return; }

    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'gps-toast';
      toast.setAttribute('role', 'status');
      document.body.appendChild(toast);
    }

    toast.textContent = teks;
    toast.className = 'gps-toast is-tampil' + (jenis ? ' is-' + jenis : '');

    clearTimeout(toastTimer);
    // Pesan galat dibiarkan lebih lama: isinya petunjuk yang perlu dibaca,
    // bukan sekadar kabar bahwa sesuatu sedang berjalan.
    toastTimer = setTimeout(function () {
      toast.className = 'gps-toast';
    }, jenis === 'error' ? 7000 : 2500);
  }

  function minta(btn) {
    if (!navigator.geolocation) {
      return pesan('Perangkat ini tidak mendukung deteksi lokasi.', 'error');
    }

    if (btn) { btn.disabled = true; }
    pesan('Mencari lokasimu...');

    navigator.geolocation.getCurrentPosition(
      function (pos) { kirim(pos.coords.latitude, pos.coords.longitude, btn); },
      function (err) {
        if (btn) { btn.disabled = false; }

        /* Pesan dibedakan: izin ditolak bisa diperbaiki pengguna, sedangkan
           https/localhost adalah batasan browser yang tidak ada hubungannya
           dengan dia. Satu pesan untuk keduanya membuat orang mencari-cari
           pengaturan yang tidak akan menolong. */
        if (err && err.code === 1) {
          pesan('Izin lokasi ditolak. Aktifkan lewat ikon gembok di bilah alamat, '
              + 'atau lanjutkan tanpa lokasi - semua produk tetap tampil.', 'error');
        } else {
          pesan('Lokasi tidak terbaca. Deteksi lokasi hanya jalan lewat https atau localhost. '
              + 'Semua produk tetap tampil tanpanya.', 'error');
        }
      },
      /* enableHighAccuracy: false, dan itu disengaja.

         Dengan true, perangkat menyalakan chip GPS sungguhan: 5-20 detik di
         luar ruangan, lebih lama di dalam ruangan, dan baterai terkuras.
         Dengan false, posisi diambil dari Wi-Fi dan menara seluler - muncul
         dalam 1-2 detik, meleset ratusan meter sampai sekitar satu kilometer.

         Meleset satu kilometer tidak masalah di sini: kita hanya mengurutkan
         toko dari yang terdekat, bukan menuntun kurir ke pintu rumah.
         Selisih sejauh itu tidak mengubah urutannya.

         timeout 15 detik: Safari di iOS bisa menggantung tanpa pernah
         memanggil callback mana pun, jadi tombolnya diam selamanya dan
         pengunjung mengira halamannya rusak.

         maximumAge 5 menit: pindah halaman tidak memicu pencarian ulang. */
      { enableHighAccuracy: false, timeout: 15000, maximumAge: 300000 }
    );
  }

  function kirim(lat, lng, btn) {
    var body = new FormData();
    body.append('lat', lat);
    body.append('lng', lng);
    if (window.CSRF) { body.append(window.CSRF.name, window.CSRF.hash); }

    fetch(C.simpan, {
      method: 'POST', body: body, credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) {
          if (btn) { btn.disabled = false; }
          return pesan(j.pesan || 'Gagal menyimpan lokasi.', 'error');
        }
        // Dimuat ulang supaya urutannya ikut berubah dari server.
        window.location.reload();
      })
      .catch(function () {
        if (btn) { btn.disabled = false; }
        pesan('Koneksi bermasalah. Coba lagi.', 'error');
      });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('[data-gps]') : null;
    if (!btn) { return; }

    e.preventDefault();
    minta(btn);
  });
})();
