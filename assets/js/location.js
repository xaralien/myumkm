/* =============================================================================
   location.js - pemilih wilayah bertingkat
   Simpan di: assets/js/location.js
   ========================================================================== */
(function () {
  'use strict';

  if (!window.REGION_URLS) { return; }

  var modal  = document.getElementById('locModal');
  var prov   = document.getElementById('locProv');
  var reg    = document.getElementById('locReg');
  var dis    = document.getElementById('locDis');
  var simpan = document.getElementById('locSimpan');
  var note   = document.getElementById('locNote');
  var cari   = document.getElementById('locSearch');
  var hasil  = document.getElementById('locHasil');
  if (!modal || !prov) { return; }

  var terpilih = null;

  /* ------------------------------------------------------------- bantu */

  function ambil(url) {
    return fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); });
  }

  function isi(select, daftar, kosong) {
    select.innerHTML = '';
    var o = document.createElement('option');
    o.value = '';
    o.textContent = kosong;
    select.appendChild(o);

    daftar.forEach(function (item) {
      var op = document.createElement('option');
      op.value = item.id;
      op.textContent = item.name;
      select.appendChild(op);
    });
    select.disabled = daftar.length === 0;
  }

  function matikan(select, teks) {
    select.innerHTML = '<option value="">' + teks + '</option>';
    select.disabled = true;
  }

  function segarkanTombol() {
    terpilih = dis.value || null;
    simpan.disabled = !terpilih;
  }

  function pesan(teks, jenis) {
    note.textContent = teks || '';
    note.className = 'locmodal-note' + (jenis ? ' is-' + jenis : '');
  }

  /* ----------------------------------------------------------- dropdown */

  ambil(window.REGION_URLS.provinces)
    .then(function (d) {
      isi(prov, d, 'Pilih provinsi');
      if (!d.length) {
        pesan('Data wilayah belum diimpor. Jalankan database/import_wilayah.php', 'error');
      }
    })
    .catch(function () {
      matikan(prov, 'Gagal memuat');
      pesan('Gagal memuat data wilayah. Periksa koneksi lalu muat ulang.', 'error');
    });

  prov.addEventListener('change', function () {
    matikan(reg, 'Memuat...');
    matikan(dis, 'Pilih kabupaten/kota dulu');
    segarkanTombol();
    if (!prov.value) { return matikan(reg, 'Pilih provinsi dulu'); }

    ambil(window.REGION_URLS.regencies + '/' + prov.value)
      .then(function (d) { isi(reg, d, 'Pilih kabupaten/kota'); })
      .catch(function () { matikan(reg, 'Gagal memuat'); });
  });

  reg.addEventListener('change', function () {
    matikan(dis, 'Memuat...');
    segarkanTombol();
    if (!reg.value) { return matikan(dis, 'Pilih kabupaten/kota dulu'); }

    ambil(window.REGION_URLS.districts + '/' + reg.value)
      .then(function (d) { isi(dis, d, 'Pilih kecamatan'); })
      .catch(function () { matikan(dis, 'Gagal memuat'); });
  });

  dis.addEventListener('change', segarkanTombol);

  /* ------------------------------------------------------- cari cepat */

  var jeda = null;

  cari.addEventListener('input', function () {
    clearTimeout(jeda);
    var q = cari.value.trim();

    if (q.length < 3) {
      hasil.hidden = true;
      return;
    }

    // Ditunda 300ms supaya tidak mengirim permintaan tiap ketukan huruf.
    jeda = setTimeout(function () {
      ambil(window.REGION_URLS.search + '?q=' + encodeURIComponent(q))
        .then(function (d) {
          hasil.innerHTML = '';
          if (!d.length) {
            hasil.innerHTML = '<li class="locmodal-kosong">Tidak ditemukan</li>';
            hasil.hidden = false;
            return;
          }
          d.forEach(function (item) {
            var li = document.createElement('li');
            li.tabIndex = 0;
            li.dataset.id = item.id;
            li.innerHTML = '<strong></strong><em></em>';
            li.querySelector('strong').textContent = item.district_name;
            li.querySelector('em').textContent =
              item.regency_name + ', ' + item.province_name;
            hasil.appendChild(li);
          });
          hasil.hidden = false;
        })
        .catch(function () { hasil.hidden = true; });
    }, 300);
  });

  function pilihDariHasil(el) {
    terpilih = el.dataset.id;
    cari.value = el.querySelector('strong').textContent;
    hasil.hidden = true;
    simpan.disabled = false;
    pesan('');
  }

  hasil.addEventListener('click', function (e) {
    var li = e.target.closest('li[data-id]');
    if (li) { pilihDariHasil(li); }
  });

  hasil.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') { return; }
    var li = e.target.closest('li[data-id]');
    if (li) { e.preventDefault(); pilihDariHasil(li); }
  });

  /* --------------------------------------------------------- simpan */

  simpan.addEventListener('click', function () {
    if (!terpilih) { return; }

    simpan.disabled = true;
    simpan.textContent = 'Menyimpan...';

    var body = new FormData();
    body.append('district_id', terpilih);
    if (window.CSRF) { body.append(window.CSRF.name, window.CSRF.hash); }

    fetch(window.REGION_URLS.set, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (window.CSRF && j.csrf_hash) {
          window.CSRF.name = j.csrf_name;
          window.CSRF.hash = j.csrf_hash;
        }
        if (!j.ok) {
          pesan(j.message || 'Gagal menyimpan lokasi.', 'error');
          simpan.disabled = false;
          simpan.textContent = 'Simpan lokasi';
          return;
        }
        // Muat ulang supaya katalog terisi ulang mengikuti lokasi baru.
        window.location.reload();
      })
      .catch(function () {
        pesan('Koneksi bermasalah. Coba lagi.', 'error');
        simpan.disabled = false;
        simpan.textContent = 'Simpan lokasi';
      });
  });

  /* ---------------------------------------------------- buka & tutup */

  function buka()  { modal.classList.add('is-open');  document.body.style.overflow = 'hidden'; }
  function tutup() { modal.classList.remove('is-open'); document.body.style.overflow = ''; }

  if (modal.classList.contains('is-open')) { document.body.style.overflow = 'hidden'; }

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-open-location]')) {
      e.preventDefault();
      buka();
      return;
    }
    if (e.target.hasAttribute && e.target.hasAttribute('data-close')
        && e.target.closest('#locModal')) {
      tutup();
    }
  });

  document.addEventListener('keydown', function (e) {
    // Esc hanya berlaku kalau lokasi SUDAH pernah dipilih. Kalau belum,
    // menutup modal cuma menyisakan katalog kosong tanpa penjelasan.
    if (e.key === 'Escape' && modal.querySelector('.locmodal-x')) { tutup(); }
  });

})();
