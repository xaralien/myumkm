/* =============================================================================
   peta-alamat.js  -  pilih alamat lewat peta OpenStreetMap
   Simpan di: assets/js/peta-alamat.js

   Dipakai bersama oleh profil toko, profil pengguna, dan checkout. Butuh
   Leaflet (CSS + JS) dan region-select.js sudah dimuat lebih dulu.

   Cara memakainya - letakkan SEBELUM <script> berkas ini:

       window.PETA_ALAMAT = {
         peta:    'petaAlamat',     // id <div> peta
         alamat:  'address',        // id <textarea>/<input> alamat
         info:    'petaInfo',       // id <p> pesan (opsional)
         cari:    'btnCariAlamat',  // id tombol cari dari teks (opsional)
         lokasiSaya: 'btnLokasiSaya',
         lat: 'inputLat', lng: 'inputLng',   // opsional: simpan koordinat
         zoomAwal: 16
       };

   Wilayah (provinsi/kabupaten/kecamatan) diisi lewat window.setWilayah()
   milik region-select.js.

   CATATAN SOAL NOMINATIM
   Layanan geocoding OpenStreetMap gratis tapi dibatasi: maksimal 1
   permintaan per detik, dan dilarang dipakai massal. Karena itu di sini
   permintaan HANYA dikirim saat pengguna benar-benar bertindak (klik peta,
   geser penanda, tekan tombol) - tidak pernah otomatis saat mengetik - dan
   ada jeda minimum antarpermintaan.
   ========================================================================== */
(function () {
  'use strict';

  var C = window.PETA_ALAMAT;
  if (!C || typeof L === 'undefined') { return; }

  var elPeta = document.getElementById(C.peta);
  if (!elPeta) { return; }

  var elAlamat = C.alamat ? document.getElementById(C.alamat) : null;
  var elInfo   = C.info   ? document.getElementById(C.info)   : null;
  var elLat    = C.lat    ? document.getElementById(C.lat)    : null;
  var elLng    = C.lng    ? document.getElementById(C.lng)    : null;

  /* ------------------------------------------------------------- pesan */

  function pesan(teks, jenis) {
    if (!elInfo) { return; }
    elInfo.textContent = teks || '';
    elInfo.className = 'peta-info' + (jenis ? ' is-' + jenis : '');
  }

  /* -------------------------------------------------------------- peta */

  var latAwal = parseFloat(elLat && elLat.value) || parseFloat(C.latAwal) || 1.0456;
  var lngAwal = parseFloat(elLng && elLng.value) || parseFloat(C.lngAwal) || 104.0305;
  var adaTitik = !!(parseFloat(elLat && elLat.value) || parseFloat(C.latAwal));

  var peta = L.map(elPeta).setView([latAwal, lngAwal], adaTitik ? (C.zoomAwal || 16) : 11);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; Kontributor OpenStreetMap'
  }).addTo(peta);

  var penanda = L.marker([latAwal, lngAwal], { draggable: true }).addTo(peta);

  /* Peta yang dibuat saat wadahnya belum berukuran final akan tampil
     abu-abu sebagian. Ini memaksa Leaflet mengukur ulang. */
  setTimeout(function () { peta.invalidateSize(); }, 200);

  function simpanKoordinat(la, ln) {
    if (elLat) { elLat.value = la.toFixed(7); }
    if (elLng) { elLng.value = ln.toFixed(7); }
  }

  /* ------------------------------------------------- geocoding terbalik */

  var permintaanTerakhir = 0;

  /**
   * Titik -> alamat. Mengisi kolom alamat dan dropdown wilayah.
   * Pengguna tetap bisa mengubah keduanya sesudahnya - hasil peta itu
   * perkiraan, dan yang tahu patokan rumahnya cuma pemiliknya.
   */
  function isiDariTitik(la, ln) {
    var jeda = Date.now() - permintaanTerakhir;
    if (jeda < 1100) {
      // Menghormati batas 1 permintaan/detik Nominatim.
      return setTimeout(function () { isiDariTitik(la, ln); }, 1100 - jeda);
    }
    permintaanTerakhir = Date.now();

    pesan('Membaca alamat dari peta...', '');

    fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=18'
          + '&accept-language=id&lat=' + la + '&lon=' + ln)
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d || !d.address) {
          return pesan('Alamat tidak terbaca dari titik ini. Isi manual saja.', 'error');
        }
        var a = d.address;

        /* Alamat jalan disusun dari bagian yang paling berguna saja.
           display_name bawaan Nominatim memuat kecamatan, kota, provinsi,
           dan kode pos sekaligus - kalau dipakai apa adanya, isinya jadi
           berulang dengan dropdown wilayah di bawahnya. */
        var jalan = [
          a.road || a.pedestrian || a.neighbourhood,
          a.house_number,
          a.village || a.hamlet || a.suburb
        ].filter(Boolean).join(' ');

        if (elAlamat && jalan) {
          elAlamat.value = jalan;
        }

        cocokkanWilayah(a, jalan);
      })
      .catch(function () {
        pesan('Gagal membaca alamat. Periksa koneksi, atau isi manual.', 'error');
      });
  }

  /* OSM menaruh tingkat wilayah di kolom yang berbeda-beda tergantung
     daerahnya, jadi semua kemungkinan dikirim sebagai kandidat dan server
     yang memilih. */
  function cocokkanWilayah(a, jalan) {
    if (typeof window.setWilayah !== 'function' || !window.REGION_URLS) { return; }

    var q = new URLSearchParams({
      provinsi:  [a.state, a.region].filter(Boolean).join('|'),
      kabupaten: [a.city, a.county, a.municipality, a.town, a.state_district].filter(Boolean).join('|'),
      kecamatan: [a.city_district, a.district, a.subdistrict, a.municipality, a.suburb, a.village].filter(Boolean).join('|')
    });

    fetch((C.urlCocok || '') + '?' + q.toString(), {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (w) {
        if (!w.province_id) {
          return pesan('Wilayah di titik ini belum ada di daftar. Pilih provinsi, kota, dan kecamatan secara manual.', 'error');
        }

        window.setWilayah(w.province_id, w.regency_id, w.district_id);

        if (w.district_id) {
          pesan('Terisi: ' + w.district_name + ', ' + w.regency_name
                + '. Periksa dan perbaiki kalau kurang tepat.', 'ok');
        } else if (w.regency_id) {
          pesan('Kota terisi (' + w.regency_name + '), tapi kecamatannya belum terbaca. Pilih sendiri kecamatannya.', 'error');
        } else {
          pesan('Hanya provinsi yang terbaca. Pilih kota dan kecamatan secara manual.', 'error');
        }
      })
      .catch(function () {
        pesan('Wilayah gagal dicocokkan. Pilih manual saja.', 'error');
      });
  }

  /* ------------------------------------------------------------ tindakan */

  function pindah(la, ln, zoom) {
    penanda.setLatLng([la, ln]);
    if (zoom) { peta.setView([la, ln], zoom); }
    simpanKoordinat(la, ln);
    isiDariTitik(la, ln);
  }

  peta.on('click', function (e) { pindah(e.latlng.lat, e.latlng.lng); });

  penanda.on('dragend', function () {
    var p = penanda.getLatLng();
    pindah(p.lat, p.lng);
  });

  /* --- tombol "lokasi saya" --- */
  var btnSaya = C.lokasiSaya ? document.getElementById(C.lokasiSaya) : null;
  if (btnSaya) {
    btnSaya.addEventListener('click', function () {
      if (!navigator.geolocation) {
        return pesan('Perangkat ini tidak mendukung deteksi lokasi.', 'error');
      }
      pesan('Mencari lokasimu...', '');

      navigator.geolocation.getCurrentPosition(
        function (pos) { pindah(pos.coords.latitude, pos.coords.longitude, 17); },
        function () {
          /* Deteksi lokasi hanya jalan di HTTPS atau localhost. Lewat
             alamat IP jaringan lokal, browser menolaknya tanpa penjelasan -
             makanya disebutkan di sini. */
          pesan('Lokasi ditolak browser. Ini hanya jalan lewat https atau localhost. Klik peta saja.', 'error');
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    });
  }

  /* --- tombol cari dari teks alamat --- */
  var btnCari = C.cari ? document.getElementById(C.cari) : null;
  if (btnCari) {
    btnCari.addEventListener('click', function () {
      var q = elAlamat ? elAlamat.value.trim() : '';
      if (q.length < 6) {
        return pesan('Tulis alamatnya dulu, minimal nama jalan dan daerahnya.', 'error');
      }

      // Nama kota ikut dikirim supaya hasilnya tidak melompat ke kota lain
      // yang punya nama jalan sama.
      var reg = document.getElementById('reg');
      if (reg && reg.selectedIndex > 0) { q += ', ' + reg.options[reg.selectedIndex].text; }

      pesan('Mencari alamat...', '');

      fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1'
            + '&countrycodes=id&accept-language=id&q=' + encodeURIComponent(q))
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d.length) {
            return pesan('Alamat tidak ditemukan. Klik langsung di peta saja.', 'error');
          }
          /* pindah(), bukan sekadar menggeser penanda: wilayahnya ikut
             diisi dari titik hasil pencarian. Kalau tidak, alamat ketemu
             tapi dropdown wilayah tetap kosong. */
          pindah(+d[0].lat, +d[0].lon, 17);
        })
        .catch(function () {
          pesan('Pencarian gagal. Klik langsung di peta saja.', 'error');
        });
    });
  }
})();
