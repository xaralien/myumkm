/* =============================================================================
   ai-deskripsi.js - tombol "Buatkan dengan AI" pada kolom deskripsi
   Simpan di: assets/js/ai-deskripsi.js

   Kunci API TIDAK ADA di sini. Permintaan dikirim ke seller/deskripsi di
   server kita sendiri, dan server yang menghubungi layanan AI.
   ========================================================================== */
(function () {
  'use strict';

  var btn  = document.getElementById('btnAi');
  var area = document.getElementById('description');
  var nama = document.getElementById('name');
  var info = document.getElementById('aiInfo');
  if (!btn || !area || !nama || !window.AI_URL) { return; }

  function pesan(teks, jenis) {
    info.textContent = teks || '';
    info.className = 'ai-info' + (jenis ? ' is-' + jenis : '');
  }

  function sibuk(on) {
    btn.disabled = on;
    btn.querySelector('.btn-ai-teks').textContent = on ? 'Menulis...' : 'Buatkan dengan AI';
  }

  function kategoriTerpilih() {
    var r = document.querySelector('input[name="category_id"]:checked')
         || document.getElementById('category_id');
    return r ? r.value : '';
  }

  /* Bilah kuota disegarkan dari balasan server, bukan dihitung di browser.
     Kuota dipakai bersama semua penjual, jadi angka yang dihitung sendiri
     di satu browser akan meleset begitu ada toko lain yang memakainya. */
  function segarkanKuota(k) {
    var kotak = document.getElementById('aiKuota');
    if (!kotak || !k) { return; }

    var bar   = kotak.querySelector('.ai-kuota-bar span');
    var teks  = kotak.querySelector('.ai-kuota-teks');
    if (bar) { bar.style.width = k.persen + '%'; }

    kotak.classList.toggle('is-habis', !!k.habis);
    kotak.classList.toggle('is-hampir', !k.habis && !!k.peringatan);

    if (teks) {
      if (k.habis) {
        teks.textContent = 'Kuota AI hari ini habis. Terisi lagi pukul ' + k.reset_lokal + '.';
      } else if (k.peringatan) {
        teks.textContent = 'Kuota AI tersisa ' + (100 - k.persen) + '%. Terisi lagi pukul '
                         + k.reset_lokal + '.';
      } else {
        teks.textContent = 'Kuota AI hari ini terpakai ' + k.persen + '%.';
      }
    }

    if (k.habis) {
      btn.disabled = true;
      btn.querySelector('.btn-ai-teks').textContent = 'Kuota AI habis';
    }
  }

  btn.addEventListener('click', function () {
    if (nama.value.trim() === '') {
      pesan('Isi nama produk dulu, itu bahan utamanya.', 'error');
      nama.focus();
      return;
    }

    /* Jangan timpa tulisan yang sudah ada tanpa bertanya. Deskripsi yang
       sudah disusun penjual jauh lebih berharga daripada hasil AI, dan
       tidak ada tombol undo di sini. */
    if (area.value.trim() !== '' &&
        !confirm('Deskripsi yang sekarang akan diganti. Lanjutkan?')) {
      return;
    }

    sibuk(true);
    var adaFoto = document.getElementById('image');
    adaFoto = adaFoto && adaFoto.files && adaFoto.files[0];
    pesan(adaFoto ? 'Membaca foto produk...' : '', '');

    var body = new FormData();
    body.append('name', nama.value);
    body.append('category_id', kategoriTerpilih());
    body.append('id', document.querySelector('input[name="id"]') ?
                      document.querySelector('input[name="id"]').value : '');

    /* Foto yang baru dipilih hanya ada di browser - belum tersimpan di
       server. Jadi berkasnya ikut dikirim, supaya AI bisa melihat produk
       yang sebenarnya, bukan menebak dari namanya saja.

       Kalau tidak ada berkas baru dan ini produk lama, server membaca
       foto yang sudah tersimpan. */
    var berkas = document.getElementById('image');
    if (berkas && berkas.files && berkas.files[0]) {
        body.append('gambar', berkas.files[0]);
    }

    if (window.CSRF) { body.append(window.CSRF.name, window.CSRF.hash); }

    fetch(window.AI_URL, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        // Token CSRF berganti tiap permintaan; tanpa diperbarui, klik
        // kedua akan ditolak server dengan 403.
        if (window.CSRF && j.csrf_hash) {
          window.CSRF.name = j.csrf_name;
          window.CSRF.hash = j.csrf_hash;
        }
        sibuk(false);
        segarkanKuota(j.kuota);

        if (!j.ok) {
          pesan(j.pesan || 'Gagal membuat deskripsi.', 'error');
          return;
        }

        area.value = j.teks;
        area.focus();
        pesan(j.pakai_gambar
            ? 'Dibuat AI dari foto produk - baca dan sesuaikan sebelum menyimpan.'
            : 'Dibuat AI dari nama dan kategori. Pilih foto dulu untuk hasil lebih tepat.',
            'ok');
      })
      .catch(function () {
        sibuk(false);
        pesan('Koneksi bermasalah. Coba lagi.', 'error');
      });
  });

})();