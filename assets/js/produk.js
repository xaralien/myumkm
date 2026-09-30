/* =============================================================================
   produk.js - pilihan varian/tambahan + tambah ke keranjang
   Simpan di: assets/js/produk.js   (TIMPA seluruh isi berkas lama)

   Mengenali DUA bentuk markup:
     - input radio/checkbox        (yang dipakai v_produk.php sekarang)
     - tombol .btn-varian/.btn-addons dengan kelas .btn-selected

   Dibuat begitu supaya tidak diam-diam mati kalau markupnya diganti -
   kegagalannya tidak memunculkan error apa pun, harga cuma tidak pernah
   berubah, dan itu sulit disadari.

   Angka di halaman ini HANYA untuk ditampilkan. Server menghitung ulang
   seluruh harga saat produk masuk keranjang, jadi mengubah data-delta
   lewat DevTools tidak mengubah harga yang ditagihkan.
   ========================================================================== */
(function () {
  'use strict';

  /* Beberapa wadah, bukan satu.

     Di tampilan mobile, pilihan varian ada di badan halaman sementara
     tombol jumlah dan "Keranjang" ada di bilah yang menempel di bawah -
     dua elemen .produk-form terpisah. Versi lama hanya mengikat yang
     pertama, jadi tombol di bilah bawah tidak bereaksi sama sekali.
     Di tampilan desktop wadahnya cuma satu, jadi tetap jalan seperti biasa. */
  var wadah = Array.prototype.slice.call(document.querySelectorAll('.produk-form'));
  if (!wadah.length || !window.PRODUK_URLS) { return; }

  var form = wadah[0];

  /** Cari satu elemen di semua wadah. */
  function cari(sel) {
    for (var i = 0; i < wadah.length; i++) {
      var el = wadah[i].querySelector(sel);
      if (el) { return el; }
    }
    return null;
  }

  /** Kumpulkan elemen dari semua wadah. */
  function cariSemua(sel) {
    var out = [];
    wadah.forEach(function (w) {
      Array.prototype.forEach.call(w.querySelectorAll(sel), function (el) { out.push(el); });
    });
    return out;
  }

  var AKTIF = 'btn-selected';

  var hargaDasar = parseInt(form.dataset.harga, 10) || 0;
  var produkId   = form.dataset.id;
  var qty        = 1;

  var elHarga = document.getElementById('hargaTampil');
  var elTotal = document.getElementById('totalTampil');
  var elQty   = document.getElementById('qtyTampil');
  var note    = cari('[data-note]');

  /* ------------------------------------------------- membaca pilihan */

  /** @return {Element|null} elemen varian yang terpilih */
  function varianAktif() {
    // Bentuk radio
    var r = cari('input[name="variant_id"]:checked');
    if (r) { return r; }

    // Bentuk tombol
    var b = cariSemua('.btn-varian');
    for (var i = 0; i < b.length; i++) {
      if (b[i].classList.contains(AKTIF)) { return b[i]; }
    }
    return null;
  }

  /** @return {Array} semua tambahan yang sedang aktif */
  function addonsAktif() {
    var out = [];

    var cb = cariSemua('input[name="addons[]"]:checked');
    for (var i = 0; i < cb.length; i++) { out.push(cb[i]); }
    if (out.length) { return out; }

    var b = cariSemua('.btn-addons');
    for (var j = 0; j < b.length; j++) {
      if (b[j].classList.contains(AKTIF)) { out.push(b[j]); }
    }
    return out;
  }

  function delta(el) {
    return el ? (parseInt(el.dataset.delta, 10) || 0) : 0;
  }

  /** Nilai yang dikirim ke server - beda letak antara radio dan tombol. */
  function nilai(el, jenisTombol) {
    if (!el) { return 0; }
    return (el.value !== undefined && el.value !== '')
      ? el.value
      : el.dataset[jenisTombol];
  }

  /* ---------------------------------------------------------- hitungan */

  /* Harga satuan = dasar + varian + SEMUA tambahan terpilih.
     Kesalahan yang gampang terjadi di sini: menghitung ulang dari harga
     dasar tiap kali satu pilihan disentuh. Kalau begitu, memilih tambahan
     akan menghapus varian yang sudah dipilih, dan sebaliknya. */
  function satuan() {
    var h = hargaDasar + delta(varianAktif());

    addonsAktif().forEach(function (a) { h += delta(a); });
    return h;
  }

  function rupiah(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
  }

  function segarkan() {
    var s = satuan();
    if (elHarga) { elHarga.textContent = rupiah(s); }
    if (elTotal) { elTotal.textContent = rupiah(s * qty); }
    if (elQty)   { elQty.textContent = qty; }
  }

  function pesan(teks, jenis) {
    if (!note) { return; }
    note.textContent = teks || '';
    note.className = 'produk-catatan' + (jenis ? ' is-' + jenis : '');
  }

  /* ------------------------------------------------------------- event */

  /* Untuk radio/checkbox, 'change' menyala SETELAH .checked diperbarui
     browser - jadi tidak perlu penundaan apa pun. */
  document.addEventListener('change', function (e) {
    if (!e.target.closest || !e.target.closest('.produk-form')) { return; }
    if (e.target.name === 'variant_id' || e.target.name === 'addons[]') {
      segarkan();
    }
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest || !e.target.closest('.produk-form')) { return; }
    var t = e.target.closest('[data-variant],[data-addon],[data-qty],[data-act]');
    if (!t) { return; }

    // Varian bentuk tombol: hanya satu boleh aktif, tidak boleh dilepas semua.
    if (t.dataset.variant && t.tagName === 'BUTTON') {
      cariSemua('.btn-varian').forEach(function (b) {
        b.classList.toggle(AKTIF, b === t);
        b.setAttribute('aria-pressed', b === t ? 'true' : 'false');
      });
      return segarkan();
    }

    // Tambahan bentuk tombol: bebas, bisa banyak, bisa dilepas lagi.
    if (t.dataset.addon && t.tagName === 'BUTTON') {
      var on = t.classList.toggle(AKTIF);
      t.setAttribute('aria-pressed', on ? 'true' : 'false');
      return segarkan();
    }

    if (t.dataset.qty) {
      qty = Math.max(1, Math.min(20, qty + parseInt(t.dataset.qty, 10)));
      return segarkan();
    }

    if (t.dataset.act) {
      kirim(t.dataset.act, t);
    }
  });

  /* --------------------------------------------------- kirim ke server */

  function kirim(act, btn, paksa) {
    /* "Beli sekarang" tidak lewat keranjang: barangnya disimpan di wadah
       terpisah, lalu langsung ke checkout. Aturan satu toko per keranjang
       tidak berlaku di sini, jadi pembeli tidak perlu mengosongkan
       keranjangnya dulu hanya untuk membeli satu barang. */
    if (act === 'buy' && window.PRODUK_URLS.langsung) {
      return beliLangsung(btn);
    }

    btn.disabled = true;
    pesan('');

    var body = new FormData();
    body.append('product_id', produkId);
    body.append('qty', qty);

    var v = varianAktif();
    body.append('variant_id', v ? nilai(v, 'variant') : 0);

    addonsAktif().forEach(function (a) {
      body.append('addons[]', nilai(a, 'addon'));
    });

    if (window.CSRF) { body.append(window.CSRF.name, window.CSRF.hash); }

    fetch(window.PRODUK_URLS.add, {
      method: 'POST', body: body, credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        // Token CSRF berganti tiap permintaan; tanpa diperbarui, klik
        // kedua ditolak 403.
        if (window.CSRF && j.csrf_hash) {
          window.CSRF.name = j.csrf_name;
          window.CSRF.hash = j.csrf_hash;
        }

        var badge = document.querySelector('.cart-badge');
        if (badge && typeof j.cart_count !== 'undefined') {
          badge.textContent = j.cart_count;
          if (j.cart_count > 0) { badge.removeAttribute('hidden'); }
          else { badge.setAttribute('hidden', ''); }
        }

        btn.disabled = false;

        if (!j.ok) {
          // Keranjang hanya boleh berisi satu toko - tawarkan jalan keluarnya.
          if (j.code === 'beda_toko' && !paksa) {
            var ya = confirm(
              'Keranjangmu berisi produk dari ' + (j.store_lama || 'toko lain') +
              '.\n\nSatu pesanan hanya bisa dari satu toko, karena ongkir dan ' +
              'jadwal pengirimannya berbeda.\n\nKosongkan keranjang dan mulai ' +
              'dari ' + (j.store_baru || 'toko ini') + '?'
            );
            if (!ya) { return pesan('Dibatalkan.', ''); }

            var kosong = new FormData();
            if (window.CSRF) { kosong.append(window.CSRF.name, window.CSRF.hash); }

            return fetch(window.PRODUK_URLS.clear, {
              method: 'POST', body: kosong, credentials: 'same-origin',
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); })
              .then(function (k) {
                if (window.CSRF && k.csrf_hash) {
                  window.CSRF.name = k.csrf_name;
                  window.CSRF.hash = k.csrf_hash;
                }
                kirim(act, btn, true);
              });
          }
          return pesan(j.message || 'Gagal menambahkan.', 'error');
        }

        if (act === 'buy') {
          window.location.href = window.PRODUK_URLS.checkout;
        } else {
          pesan('Ditambahkan ke keranjang.', 'ok');
        }
      })
      .catch(function () {
        btn.disabled = false;
        pesan('Koneksi bermasalah. Coba lagi.', 'error');
      });
  }

  function beliLangsung(btn) {
    btn.disabled = true;
    pesan('');

    var body = new FormData();
    body.append('product_id', produkId);
    body.append('qty', qty);

    var v = varianAktif();
    body.append('variant_id', v ? nilai(v, 'variant') : 0);

    addonsAktif().forEach(function (a) {
      body.append('addons[]', nilai(a, 'addon'));
    });

    if (window.CSRF) { body.append(window.CSRF.name, window.CSRF.hash); }

    fetch(window.PRODUK_URLS.langsung, {
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
          return pesan(j.message || 'Gagal memproses.', 'error');
        }
        window.location.href = j.lanjut;
      })
      .catch(function () {
        btn.disabled = false;
        pesan('Koneksi bermasalah. Coba lagi.', 'error');
      });
  }

  segarkan();
})(); 