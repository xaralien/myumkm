/* =============================================================================
   nearby.js - minta izin lokasi lalu masuk mode "toko di sekitar saya"
   Simpan di: assets/js/nearby.js
   ========================================================================== */
(function () {
  'use strict';

  var btn = document.getElementById('btnDekat');
  if (!btn || !window.LOKASI_URLS) { return; }

  var info = document.getElementById('dekatInfo');

  function pesan(teks, jenis) {
    if (!info) { return; }
    info.textContent = teks || '';
    info.className = 'dekat-info' + (jenis ? ' is-' + jenis : '');
  }

  btn.addEventListener('click', function () {
    if (!navigator.geolocation) {
      return pesan('Browser ini tidak mendukung deteksi lokasi. '
                 + 'Pakai pilihan wilayah di atas saja.', 'error');
    }

    btn.disabled = true;
    pesan('Mencari lokasi kamu...', '');

    navigator.geolocation.getCurrentPosition(
      function (pos) {
        var body = new FormData();
        body.append('lat', pos.coords.latitude);
        body.append('lng', pos.coords.longitude);
        if (window.CSRF) { body.append(window.CSRF.name, window.CSRF.hash); }

        /* Koordinat disimpan di SESSION lewat server, tidak ditaruh di URL.
           Kalau lewat URL, link yang dibagikan akan membawa lokasi
           pengirimnya - penerima link melihat toko di kota orang lain
           tanpa sadar. */
        fetch(window.LOKASI_URLS.titik, {
          method: 'POST', body: body, credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function (r) { return r.json(); })
          .then(function (j) {
            if (window.CSRF && j.csrf_hash) {
              window.CSRF.name = j.csrf_name;
              window.CSRF.hash = j.csrf_hash;
            }
            if (!j.ok) {
              btn.disabled = false;
              return pesan(j.pesan || 'Gagal menyimpan lokasi.', 'error');
            }
            window.location = '?dekat=1&radius=10';
          })
          .catch(function () {
            btn.disabled = false;
            pesan('Koneksi bermasalah. Coba lagi.', 'error');
          });
      },
      function (err) {
        btn.disabled = false;

        /* Geolocation HANYA jalan di HTTPS atau localhost. Di XAMPP lewat
           http://localhost aman; begitu diakses dari HP lewat IP jaringan
           lokal seperti 192.168.1.5, browser menolak tanpa dialog izin -
           dan itu terlihat seperti fiturnya rusak. */
        if (err.code === 1) {
          pesan('Izin lokasi ditolak. Kamu masih bisa memilih wilayah manual '
              + 'lewat tombol Ganti di atas.', 'error');
        } else if (err.code === 2) {
          pesan('Lokasi tidak terdeteksi. Kalau situs ini dibuka lewat alamat IP, '
              + 'deteksi lokasi memang hanya jalan di https atau localhost.', 'error');
        } else {
          pesan('Pencarian lokasi kelamaan. Coba lagi atau pilih wilayah manual.', 'error');
        }
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
    );
  });
})();