/* =============================================================================
   map-picker.js - penanda lokasi toko di peta
   Simpan di: assets/js/map-picker.js

   Butuh Leaflet 1.9+ dimuat lebih dulu.

   Peta hanya ALAT BANTU. Nilai sebenarnya ada di input tersembunyi, jadi
   kalau Leaflet gagal dimuat, nilai lama tetap terkirim dan tidak hilang.
   ========================================================================== */
(function () {
  'use strict';

  var el = document.getElementById('petaToko');
  var iLat = document.getElementById('inputLat');
  var iLng = document.getElementById('inputLng');
  if (!el || !iLat || !iLng) { return; }

  if (typeof L === 'undefined') {
    el.innerHTML = '<p class="peta-gagal">Peta gagal dimuat. Titik lokasi lama '
                 + 'tetap tersimpan.</p>';
    return;
  }

  var info = document.getElementById('petaInfo');

  // Titik awal: koordinat tersimpan, atau tengah Batam kalau belum ada.
  var awalLat = parseFloat(iLat.value);
  var awalLng = parseFloat(iLng.value);
  var adaTitik = !isNaN(awalLat) && !isNaN(awalLng);

  var lat = adaTitik ? awalLat : 1.1301;
  var lng = adaTitik ? awalLng : 104.0529;

  var peta = L.map(el).setView([lat, lng], adaTitik ? 16 : 12);

  /* OpenStreetMap gratis dan tanpa kunci API, TAPI syarat pemakaiannya
     melarang lalu lintas berat. Untuk panel penjual yang dibuka
     sesekali ini aman; kalau nanti peta dipasang di halaman publik yang
     ramai, pindah ke penyedia ubin berbayar. Atribusi WAJIB ditampilkan. */
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
  }).addTo(peta);

  var penanda = L.marker([lat, lng], { draggable: true }).addTo(peta);

  function pesan(teks, jenis) {
    if (!info) { return; }
    info.textContent = teks || '';
    info.className = 'peta-info' + (jenis ? ' is-' + jenis : '');
  }

  function simpan(la, ln) {
    /* Dibulatkan 7 desimal - setara ~1 cm, jauh melebihi ketelitian GPS
       mana pun. Tanpa pembulatan, nilainya bisa 15 digit dan ditolak
       kolom DECIMAL(10,7). */
    iLat.value = la.toFixed(7);
    iLng.value = ln.toFixed(7);
    pesan('Titik: ' + iLat.value + ', ' + iLng.value + ' (belum tersimpan)', 'ok');
  }

  penanda.on('dragend', function () {
    var p = penanda.getLatLng();
    simpan(p.lat, p.lng);
  });

  // Klik di peta memindahkan penanda - lebih cepat daripada menyeret jauh.
  peta.on('click', function (e) {
    penanda.setLatLng(e.latlng);
    simpan(e.latlng.lat, e.latlng.lng);
  });

  if (adaTitik) {
    pesan('Titik: ' + iLat.value + ', ' + iLng.value, '');
  } else {
    pesan('Belum ada titik. Klik di peta atau seret penanda ke lokasi toko.', '');
  }

  /* --- Pakai lokasi perangkat --- */
  var btnSaya = document.getElementById('btnLokasiSaya');
  if (btnSaya) {
    btnSaya.addEventListener('click', function () {
      if (!navigator.geolocation) {
        return pesan('Browser ini tidak mendukung deteksi lokasi.', 'error');
      }

      pesan('Mencari lokasi...', '');
      navigator.geolocation.getCurrentPosition(
        function (pos) {
          var la = pos.coords.latitude, ln = pos.coords.longitude;
          penanda.setLatLng([la, ln]);
          peta.setView([la, ln], 17);
          simpan(la, ln);
        },
        function (err) {
          /* Geolocation hanya jalan di HTTPS atau localhost. Di XAMPP
             lewat http://localhost aman; begitu diakses lewat IP jaringan
             lokal seperti 192.168.x.x, browser menolaknya. */
          pesan(err.code === 1
            ? 'Izin lokasi ditolak. Geser penanda manual, atau pastikan situs '
              + 'diakses lewat https:// atau localhost.'
            : 'Gagal mendapatkan lokasi. Geser penanda manual saja.', 'error');
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    });
  }

  /* --- Cari dari teks alamat (Nominatim) --- */
  var btnCari = document.getElementById('btnCariAlamat');
  if (btnCari) {
    btnCari.addEventListener('click', function () {
      var alamat = document.getElementById('address');
      var q = alamat ? alamat.value.trim() : '';

      if (q.length < 8) {
        return pesan('Isi alamat toko dulu, minimal agak lengkap.', 'error');
      }

      pesan('Mencari alamat...', '');

      /* Nominatim membatasi 1 permintaan per detik dan melarang pemakaian
         massal. Karena itu pencarian hanya jalan saat tombol ditekan,
         bukan otomatis saat mengetik. */
      fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=id&q='
            + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d.length) {
            return pesan('Alamat tidak ditemukan. Geser penanda manual saja.', 'error');
          }
          var la = parseFloat(d[0].lat), ln = parseFloat(d[0].lon);
          penanda.setLatLng([la, ln]);
          peta.setView([la, ln], 16);
          simpan(la, ln);
          pesan('Perkiraan dari alamat - periksa dan geser kalau meleset.', 'ok');
        })
        .catch(function () {
          pesan('Pencarian alamat gagal. Geser penanda manual saja.', 'error');
        });
    });
  }

  /* Peta yang dibuat saat wadahnya belum berukuran final akan tampil
     abu-abu sebagian. Ini memaksa Leaflet mengukur ulang. */
  setTimeout(function () { peta.invalidateSize(); }, 200);
})();