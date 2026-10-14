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

  /* ------------------------------------------------ titik -> wilayah

     Titik GPS diterjemahkan ke nama provinsi/kota/kecamatan oleh layanan
     peta, dicoba berurutan sampai ada yang cocok dengan daftar wilayah:

       1. OpenStreetMap (Nominatim)  - gratis, tanpa kunci
       2. BigDataCloud               - gratis, tanpa kunci (khusus browser)
       3. Google Maps Geocoding      - hanya kalau kunci diisi di
                                       Admin > Pengaturan > Peta

     Semuanya dipanggil dari BROWSER, bukan server: PHP di Windows/XAMPP
     sering gagal membuka HTTPS karena sertifikat CA-nya belum diatur. Tiap
     layanan dibatasi 6 detik. Hasilnya dicek ke region/cocok; yang pertama
     cocok sampai tingkat kecamatan yang dipakai. */

  function ambilJson(url) {
    var ctl = window.AbortController ? new AbortController() : null;
    var t = ctl ? setTimeout(function () { ctl.abort(); }, 6000) : null;
    return fetch(url, ctl ? { signal: ctl.signal } : {})
      .then(function (r) { if (t) { clearTimeout(t); } if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); })
      .catch(function (e) { if (t) { clearTimeout(t); } throw e; });
  }

  function gabung(arr) {
    var lihat = {};
    return arr.filter(function (x) {
      if (!x || lihat[x]) { return false; }
      lihat[x] = true;
      return true;
    }).join('|');
  }

  var LAYANAN = [
    {
      nama: 'OpenStreetMap',
      baca: function (lat, lng) {
        return ambilJson('https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=18&addressdetails=1'
                         + '&accept-language=id&lat=' + lat + '&lon=' + lng)
          .then(function (d) {
            var a = (d && d.address) || {};
            var k = function (ks) { return gabung(ks.map(function (x) { return a[x]; })); };
            // Sama dengan kandidat di peta alamat (peta-alamat.js).
            return {
              provinsi:  k(['state', 'region']),
              kabupaten: k(['city', 'county', 'municipality', 'town', 'state_district']),
              kecamatan: k(['city_district', 'district', 'subdistrict', 'municipality', 'suburb', 'village'])
            };
          });
      }
    },
    {
      nama: 'BigDataCloud',
      baca: function (lat, lng) {
        return ambilJson('https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=' + lat
                         + '&longitude=' + lng + '&localityLanguage=id')
          .then(function (d) {
            // Tingkat wilayah Indonesia: 4 provinsi, 5 kab/kota, 6 kecamatan.
            var adm = ((d && d.localityInfo && d.localityInfo.administrative) || []);
            var tingkat = function (n) {
              return adm.filter(function (x) { return x.adminLevel === n; }).map(function (x) { return x.name; });
            };
            return {
              provinsi:  gabung([d.principalSubdivision].concat(tingkat(4))),
              kabupaten: gabung(tingkat(5).concat([d.city])),
              kecamatan: gabung(tingkat(6).concat([d.locality]))
            };
          });
      }
    },
    {
      nama: 'Google Maps',
      aktif: function () { return !!C.gkey; },
      baca: function (lat, lng) {
        return ambilJson('https://maps.googleapis.com/maps/api/geocode/json?latlng=' + lat + ',' + lng
                         + '&language=id&key=' + encodeURIComponent(C.gkey))
          .then(function (d) {
            if (!d || d.status !== 'OK') { throw new Error('Google: ' + (d && (d.error_message || d.status))); }
            var per = { administrative_area_level_1: [], administrative_area_level_2: [], administrative_area_level_3: [] };
            (d.results || []).forEach(function (r) {
              (r.address_components || []).forEach(function (c) {
                Object.keys(per).forEach(function (t) {
                  if (c.types.indexOf(t) >= 0) { per[t].push(c.long_name); }
                });
              });
            });
            return {
              provinsi:  gabung(per.administrative_area_level_1),
              kabupaten: gabung(per.administrative_area_level_2),
              kecamatan: gabung(per.administrative_area_level_3)
            };
          });
      }
    }
  ];

  function cocok(w) {
    var q = 'provinsi=' + encodeURIComponent(w.provinsi) + '&kabupaten=' + encodeURIComponent(w.kabupaten)
          + '&kecamatan=' + encodeURIComponent(w.kecamatan);
    return fetch((C.cocok || '') + '?' + q, {
      credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); });
  }

  /** @return Promise<{w: kandidat yang cocok | null, catatan: [teks per layanan]}> */
  function cariWilayah(lat, lng) {
    var catatan = [];
    var i = 0;
    function berikut() {
      while (i < LAYANAN.length && LAYANAN[i].aktif && !LAYANAN[i].aktif()) { i++; }
      if (i >= LAYANAN.length) { return Promise.resolve({ w: null, catatan: catatan }); }
      var L = LAYANAN[i++];
      return L.baca(lat, lng)
        .then(function (w) {
          if (window.console) { console.log('[gps] ' + L.nama + ':', w); }
          return cocok(w).then(function (h) {
            if (h && h.district_id) { return { w: w, catatan: catatan }; }
            catatan.push(L.nama + ' membaca "' + ([w.kecamatan, w.kabupaten, w.provinsi].filter(Boolean).join(', ') || 'kosong')
                         + '" - belum cocok');
            return berikut();
          });
        })
        .catch(function (e) {
          if (window.console) { console.warn('[gps] ' + L.nama + ' gagal:', e); }
          catatan.push(L.nama + ' tidak bisa dihubungi');
          return berikut();
        });
    }
    return berikut();
  }

  function kirim(lat, lng, btn) {
    pesan('Membaca wilayahmu...');
    cariWilayah(lat, lng).then(function (hasil) {
      var w = hasil.w;
      var body = new FormData();
      body.append('lat', lat);
      body.append('lng', lng);
      if (w) {
        body.append('provinsi', w.provinsi);
        body.append('kabupaten', w.kabupaten);
        body.append('kecamatan', w.kecamatan);
      }
      if (window.CSRF) { body.append(window.CSRF.name, window.CSRF.hash); }

      return fetch(C.simpan, {
        method: 'POST', body: body, credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (!j.ok) {
            if (btn) { btn.disabled = false; }
            return pesan(j.pesan || 'Gagal menyimpan lokasi.', 'error');
          }
          if (j.label) {
            // Dimuat ulang supaya urutan dan label "Kirim ke" ikut berubah.
            pesan('Lokasi: ' + j.label);
            window.location.reload();
          } else {
            /* Titik tersimpan (toko tetap diurutkan dari yang terdekat), tapi
               kecamatannya tidak terbaca - beri tahu dulu alasannya sebelum
               memuat ulang, supaya tidak tampak seperti tombol yang tidak bekerja. */
            pesan('Toko diurutkan dari lokasimu, tapi kecamatannya tidak terbaca ('
                + (hasil.catatan.join('; ') || 'tidak ada layanan peta') + '). Pilih kecamatan lewat menu lokasi.', 'error');
            setTimeout(function () { window.location.reload(); }, 9000);
          }
        });
    }).catch(function () {
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
