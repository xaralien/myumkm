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
   geser penanda, tekan tombol, memilih kecamatan, selesai mengubah alamat)
   ditambah satu pemeriksaan saat halaman dibuka - tidak pernah tiap
   ketikan - dan ada jeda minimum antarpermintaan.
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
  var dariPeta = 0;

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

        // 'change' yang dipicu setWilayah() dari sini tidak boleh memindahkan
        // titik lagi - titiknya justru sumber wilayah ini.
        dariPeta = Date.now();
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
        /* Di SINI enableHighAccuracy sengaja tetap true. Titik ini jadi
           alamat antar yang dibaca kurir, jadi meleset satu kilometer berarti
           barang diantar ke blok yang salah - beda dengan pengurutan katalog,
           yang tidak terpengaruh selisih sejauh itu.

           timeout dinaikkan ke 15 detik karena chip GPS memang butuh waktu,
           dan Safari di iOS bisa menggantung tanpa memanggil callback. */
        { enableHighAccuracy: true, timeout: 15000 }
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

  /* ------------------------------------------- titik dari alamat & wilayah

     Titik diletakkan otomatis dari isian Alamat & wilayah:
       - saat pengguna memilih kecamatan,
       - saat kolom alamat selesai diubah (keluar dari kolom),
       - sekali saat halaman dibuka, kalau titik belum ada ATAU letaknya
         lebih dari 30 km dari kecamatan yang dipilih (mis. titik bawaan
         di Batam padahal wilayahnya Jakarta).
     Hasilnya hanya memindah penanda & koordinat - kolom alamat dan wilayah
     TIDAK ditimpa, karena yang mengisinya justru pengguna. Tetap perkiraan:
     penjual diminta menggeser penanda ke posisi tepat. */

  var elProv = document.getElementById('prov');
  var elReg  = document.getElementById('reg');
  var elDis  = document.getElementById('dis');

  function teksPilihan(sel) {
    return (sel && sel.selectedIndex > 0) ? sel.options[sel.selectedIndex].text : '';
  }

  // "Kota Administrasi Jakarta Timur" -> "Jakarta Timur": awalan resmi
  // Kemendagri itu tidak dikenal mesin pencari peta.
  function rapikan(n) {
    return n.replace(/^(Kota Administrasi|Kabupaten Administrasi|Kabupaten|Kota|Kecamatan)\s+/i, '')
            .replace(/^Daerah Khusus Ibukota\s+/i, 'DKI ');
  }

  function cari(q) {
    var jeda = Date.now() - permintaanTerakhir;
    if (jeda < 1100) {
      return new Promise(function (ok) { setTimeout(ok, 1100 - jeda); }).then(function () { return cari(q); });
    }
    permintaanTerakhir = Date.now();
    return fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=id&accept-language=id&q='
                 + encodeURIComponent(q))
      .then(function (r) { return r.json(); })
      .then(function (d) { return (d && d.length) ? { lat: +d[0].lat, lng: +d[0].lon } : null; })
      .catch(function () { return null; });
  }

  // Coba beberapa susunan berurutan, dari yang paling rinci.
  function cariBerurutan(daftar) {
    if (!daftar.length) { return Promise.resolve(null); }
    return cari(daftar[0].q).then(function (h) {
      return h ? { titik: h, tingkat: daftar[0].tingkat } : cariBerurutan(daftar.slice(1));
    });
  }

  function jarakKm(a, b) {
    var R = 6371, rad = Math.PI / 180;
    var dLat = (b.lat - a.lat) * rad, dLng = (b.lng - a.lng) * rad;
    var x = Math.sin(dLat / 2) * Math.sin(dLat / 2)
          + Math.cos(a.lat * rad) * Math.cos(b.lat * rad) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return 2 * R * Math.asin(Math.sqrt(x));
  }

  function taruh(t, zoom) {
    penanda.setLatLng([t.lat, t.lng]);
    peta.setView([t.lat, t.lng], zoom);
    simpanKoordinat(t.lat, t.lng);
  }

  /**
   * @param pakaiAlamat  sertakan teks alamat (paling rinci) di urutan pertama
   * @param syarat       fungsi(titikBaru) -> boleh dipindah? (opsional)
   */
  function taruhDariIsian(pakaiAlamat, syarat) {
    var kec = rapikan(teksPilihan(elDis)), kota = rapikan(teksPilihan(elReg)), prov = rapikan(teksPilihan(elProv));
    if (!kec && !kota) { return; }

    var daftar = [];
    var alamat = (pakaiAlamat && elAlamat) ? elAlamat.value.trim() : '';
    if (alamat.length >= 6) {
      daftar.push({ q: [alamat, kec, kota].filter(Boolean).join(', '), tingkat: 'alamat' });
    }
    if (kec) { daftar.push({ q: [kec, kota, prov].filter(Boolean).join(', '), tingkat: 'kecamatan' }); }
    if (kota) { daftar.push({ q: [kota, prov].filter(Boolean).join(', '), tingkat: 'kota' }); }

    pesan('Menaruh titik dari alamat & wilayah...', '');
    cariBerurutan(daftar).then(function (h) {
      if (!h) {
        return pesan('Titik belum bisa diperkirakan dari alamat. Klik peta atau geser penandanya.', 'error');
      }
      if (syarat && !syarat(h.titik)) { return pesan('', ''); }
      taruh(h.titik, h.tingkat === 'alamat' ? 17 : (h.tingkat === 'kecamatan' ? 15 : 13));
      pesan(h.tingkat === 'alamat'
        ? 'Titik diperkirakan dari alamat. Geser penanda kalau kurang tepat.'
        : 'Titik ditaruh di tengah ' + (h.tingkat === 'kecamatan' ? 'kecamatan' : 'kota')
          + '. Geser penanda ke lokasi persisnya.', 'ok');
    });
  }

  /* Dua sumber 'change' otomatis yang TIDAK boleh dianggap pilihan
     pengguna: isian awal dari region-select.js saat halaman dibuka, dan
     setWilayah() dari klik/geser peta. Selebihnya dianggap pilihan pengguna
     (memilih langsung, atau memilih alamat tersimpan). */
  var cekAwalSelesai = !(window.PILIHAN_AWAL && window.PILIHAN_AWAL.district);

  if (elDis) {
    elDis.addEventListener('change', function () {
      if (!elDis.value) { return; }

      // Dari klik/geser di peta: titiknya sudah benar.
      if (Date.now() - dariPeta < 5000) { return; }

      if (cekAwalSelesai) { return taruhDariIsian(true); }

      // Isian awal saat halaman dibuka.
      cekAwalSelesai = true;
      if (!adaTitik) { return taruhDariIsian(true); }

      var kini = penanda.getLatLng();
      taruhDariIsian(false, function (baru) {
        var jauh = jarakKm({ lat: kini.lat, lng: kini.lng }, baru) > 30;
        if (jauh) {
          setTimeout(function () {
            pesan('Titik sebelumnya jauh dari kecamatan yang dipilih, jadi dipindah ke sini. '
                + 'Geser ke lokasi persis, lalu simpan.', 'error');
          }, 50);
        }
        return jauh;
      });
    });
  }

  if (elAlamat) {
    // 'change' = selesai mengubah lalu keluar dari kolom, bukan tiap ketikan.
    elAlamat.addEventListener('change', function () {
      if (elDis && elDis.value) { taruhDariIsian(true); }
    });
  }
})();
