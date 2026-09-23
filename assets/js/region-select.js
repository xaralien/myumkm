/* =============================================================================
   region-select.js - dropdown wilayah bertingkat untuk form admin
   Simpan di: assets/js/region-select.js
   ========================================================================== */
(function () {
  'use strict';

  var prov = document.getElementById('prov');
  var reg  = document.getElementById('reg');
  var dis  = document.getElementById('dis');
  if (!prov || !reg || !dis || !window.REGION_URLS) { return; }

  var awal = window.PILIHAN_AWAL || {};

  function ambil(url) {
    return fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); });
  }

  function isi(select, daftar, kosong, terpilih) {
    select.innerHTML = '<option value="">' + kosong + '</option>';
    daftar.forEach(function (item) {
      var o = document.createElement('option');
      o.value = item.id;
      o.textContent = item.name;
      if (terpilih && Number(terpilih) === Number(item.id)) { o.selected = true; }
      select.appendChild(o);
    });
    select.disabled = daftar.length === 0;
  }

  function muatKabupaten(terpilih) {
    if (!prov.value) {
      reg.innerHTML = '<option value="">Pilih provinsi dulu</option>';
      reg.disabled = true;
      return Promise.resolve();
    }
    return ambil(window.REGION_URLS.regencies + '/' + prov.value)
      .then(function (d) { isi(reg, d, 'Pilih kabupaten/kota', terpilih); });
  }

  function muatKecamatan(terpilih) {
    if (!reg.value) {
      dis.innerHTML = '<option value="">Pilih kabupaten/kota dulu</option>';
      dis.disabled = true;
      return Promise.resolve();
    }
    return ambil(window.REGION_URLS.districts + '/' + reg.value)
      .then(function (d) { isi(dis, d, 'Pilih kecamatan', terpilih); });
  }

  prov.addEventListener('change', function () {
    muatKabupaten().then(function () { muatKecamatan(); });
  });
  reg.addEventListener('change', function () { muatKecamatan(); });

  /* Saat mengubah toko yang sudah ada, dua dropdown bawah harus terisi
     lebih dulu supaya nilai lamanya kelihatan - bukan kosong seolah
     belum pernah diisi. */
  /* Dipanggil dari luar (peta-alamat.js) untuk mengisi ketiga dropdown
     sekaligus. Mengembalikan Promise supaya pemanggilnya tahu kapan
     kecamatan selesai dimuat - daftar kecamatan baru ada SETELAH kabupaten
     diambil dari server, jadi tidak bisa diisi seketika. */
  window.setWilayah = function (provinceId, regencyId, districtId) {
    prov.value = provinceId ? String(provinceId) : '';

    if (!prov.value) {
      isi(reg, [], 'Kabupaten / Kota');
      isi(dis, [], 'Kecamatan');
      return Promise.resolve();
    }

    return muatKabupaten(regencyId)
      .then(function () { return muatKecamatan(districtId); })
      .then(function () {
        // Halaman yang menghitung ongkir mendengarkan 'change' di kecamatan.
        if (dis.value) {
          dis.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
  };

  if (awal.province) {
    muatKabupaten(awal.regency)
      .then(function () { return muatKecamatan(awal.district); })
      .then(function () {
        /* Memicu 'change' setelah isian awal terpasang. Tanpa ini, halaman
           yang bereaksi pada pilihan kecamatan (ongkir di checkout) tidak
           pernah tahu ada nilai awal - ongkir tetap "Pilih wilayah dulu"
           sampai pembeli mengubahnya sendiri. */
        if (dis.value) {
          dis.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
  }
})();
