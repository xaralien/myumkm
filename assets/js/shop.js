/* =============================================================================
   shop.js - panel "tambah ke keranjang"
   Simpan di: assets/js/shop.js

   Dipanggil oleh tombol <button class="icon-cross btn-add" data-id="...">
   yang ada di kartu produk.

   Butuh window.CSRF di halaman (lihat README, bagian pemasangan).
   ========================================================================== */
(function () {
  'use strict';

  if (!window.SHOP_URLS) { return; }

  var panel = null;
  var state = null;   // { product, variants, addons, variantId, addonIds, qty }

  /* ------------------------------------------------------------ utilitas */

  function rupiah(n) {
    return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function post(url, data) {
    var body = new FormData();
    Object.keys(data).forEach(function (k) {
      if (Array.isArray(data[k])) {
        data[k].forEach(function (v) { body.append(k + '[]', v); });
      } else {
        body.append(k, data[k]);
      }
    });
    if (window.CSRF) { body.append(window.CSRF.name, window.CSRF.hash); }

    return fetch(url, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) {
      return r.json().then(function (j) {
        // Token CSRF berganti setiap request. Kalau tidak diperbarui,
        // klik kedua akan ditolak server dengan 403.
        if (window.CSRF && j.csrf_hash) {
          window.CSRF.name = j.csrf_name;
          window.CSRF.hash = j.csrf_hash;
        }
        if (typeof j.cart_count !== 'undefined') { setBadge(j.cart_count); }
        return { ok: r.ok, data: j };
      });
    });
  }

  /* Badge memakai atribut `hidden`, bukan style.display. Kalau dimatikan
     lewat inline style, aturan CSS .cart-badge[hidden] tetap menang dan
     badge tidak pernah muncul lagi setelah disembunyikan sekali. */
  function setBadge(n) {
    var b = document.querySelector('.cart-badge');
    if (!b) { return; }
    b.textContent = n;
    if (n > 0) { b.removeAttribute('hidden'); } else { b.setAttribute('hidden', ''); }
  }

  /* ------------------------------------------------------------- panel */

  function buildPanel() {
    panel = document.createElement('div');
    panel.className = 'addpanel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    panel.setAttribute('aria-label', 'Pilihan produk');
    panel.innerHTML =
      '<div class="addpanel-scrim" data-close></div>' +
      '<div class="addpanel-sheet">' +
      '<div class="addpanel-grip"></div>' +
      '<button type="button" class="addpanel-x" data-close aria-label="Tutup">&times;</button>' +
      '<div class="addpanel-body"></div>' +
      '</div>';
    document.body.appendChild(panel);

    panel.addEventListener('click', function (e) {
      if (e.target.hasAttribute('data-close')) { close(); }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && panel.classList.contains('is-open')) { close(); }
    });
  }

  function open() {
    panel.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    var f = panel.querySelector('button, input');
    if (f) { f.focus(); }
  }

  function close() {
    panel.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  /* ------------------------------------------------------------ render */

  function unitPrice() {
    var harga = state.product.price;

    var v = state.variants.filter(function (x) { return +x.id === +state.variantId; })[0];
    if (v) { harga += +v.price_delta; }

    state.addonIds.forEach(function (id) {
      var a = state.addons.filter(function (x) { return +x.id === +id; })[0];
      if (a) { harga += +a.price_delta; }
    });
    return harga;
  }

  function render() {
    var p = state.product;
    var h = '';

    h += '<div class="addpanel-head">' +
      '<img src="' + p.image + '" alt="">' +
      '<div>' +
      '<p class="addpanel-name">' + esc(p.name) + '</p>' +
      '<p class="addpanel-base">' + rupiah(p.price) + '</p>' +
      (state.sameDay
        ? '<p class="addpanel-ok">Bisa kirim hari ini</p>'
        : '<p class="addpanel-warn">Pesanan hari ini sudah tutup (jam ' +
        state.cutoff + ':00), kirim paling cepat besok</p>') +
      '</div>' +
      '</div>';

    if (state.variants.length) {
      h += '<p class="addpanel-label">Ukuran</p><div class="addpanel-opts">';
      state.variants.forEach(function (v) {
        var aktif = (+v.id === +state.variantId) ? ' is-active' : '';
        h += '<button type="button" class="addpanel-opt' + aktif + '" data-variant="' + v.id + '">' +
          '<span>' + esc(v.name) + '</span>' +
          '<em>' + rupiah(p.price + (+v.price_delta)) + '</em>' +
          '</button>';
      });
      h += '</div>';
    }

    if (state.addons.length) {
      h += '<p class="addpanel-label">Tambahan (opsional)</p><div class="addpanel-chips">';
      state.addons.forEach(function (a) {
        var aktif = state.addonIds.indexOf(+a.id) > -1 ? ' is-active' : '';
        h += '<button type="button" class="addpanel-chip' + aktif + '" data-addon="' + a.id + '">' +
          esc(a.name) + ' <em>' + (+a.price_delta ? '+' + rupiah(a.price_delta) : 'gratis') + '</em>' +
          '</button>';
      });
      h += '</div>';
    }

    // h += '<div class="addpanel-foot">' +
    //        '<div>' +
    //          '<p class="addpanel-totlabel">Total</p>' +
    //          '<p class="addpanel-total">' + rupiah(unitPrice() * state.qty) + '</p>' +
    //        '</div>' +
    //        '<div class="qty-box">' +
    //          '<button type="button" class="qty-btn" data-qty="-1" aria-label="Kurangi">&minus;</button>' +
    //          '<span class="qty-num">' + state.qty + '</span>' +
    //          '<button type="button" class="qty-btn" data-qty="1" aria-label="Tambah">+</button>' +
    //        '</div>' +
    //      '</div>' +
    //      '<div class="addpanel-actions">' +
    //        '<button type="button" class="btn btn-black-hover-outline" data-act="add">Tambah ke keranjang</button>' +
    //        '<button type="button" class="btn btn-primary" data-act="buy">Pesan sekarang</button>' +
    //      '</div>' +
    //      '<p class="addpanel-note" data-note></p>';


    h += '<div class="addpanel-foot">' +
      '<div>' +
      '<p class="addpanel-totlabel">Total</p>' +
      '<p class="addpanel-total">' + rupiah(unitPrice() * state.qty) + '</p>' +
      '</div>' +
      '<div class="qty-box">' +
      '<button type="button" class="qty-btn" data-qty="-1" aria-label="Kurangi">&minus;</button>' +
      '<span class="qty-num">' + state.qty + '</span>' +
      '<button type="button" class="qty-btn" data-qty="1" aria-label="Tambah">+</button>' +
      '</div>' +
      '</div>' +
      '<div class="addpanel-actions">' +
      '<button type="button" class="btn btn-black-hover-outline" data-act="add">Tambah ke keranjang</button>' +
      '</div>' +
      '<p class="addpanel-note" data-note></p>';

    panel.querySelector('.addpanel-body').innerHTML = h;
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  /* ------------------------------------------------------------ aksi */

  function onBodyClick(e) {
    var t = e.target.closest('[data-variant],[data-addon],[data-qty],[data-act]');
    if (!t || !state) { return; }

    if (t.dataset.variant) {
      state.variantId = +t.dataset.variant;
      return render();
    }
    if (t.dataset.addon) {
      var id = +t.dataset.addon;
      var i = state.addonIds.indexOf(id);
      if (i > -1) { state.addonIds.splice(i, 1); } else { state.addonIds.push(id); }
      return render();
    }
    if (t.dataset.qty) {
      state.qty = Math.max(1, Math.min(20, state.qty + (+t.dataset.qty)));
      return render();
    }
    if (t.dataset.act) {
      return submit(t.dataset.act, t);
    }
  }

  function submit(act, btn) {
    btn.disabled = true;
    var note = panel.querySelector('[data-note]');
    note.textContent = '';
    note.className = 'addpanel-note';

    post(window.SHOP_URLS.add, {
      product_id: state.product.id,
      variant_id: state.variantId || 0,
      addons: state.addonIds,
      qty: state.qty
    }).then(function (res) {
      btn.disabled = false;
      if (!res.ok || !res.data.ok) {
        /* Keranjang hanya boleh berisi satu toko. Jangan cuma menolak -
           tawarkan jalan keluarnya, kalau tidak pengunjung buntu. */
        if (res.data.code === 'beda_toko') {
          gantiToko(res.data);
          return;
        }
        note.textContent = res.data.message || 'Gagal menambahkan.';
        note.className = 'addpanel-note is-error';
        return;
      }
      if (act === 'buy') {
        window.location.href = window.SHOP_URLS.checkout;
      } else {
        note.textContent = 'Ditambahkan ke keranjang.';
        note.className = 'addpanel-note is-ok';
        setTimeout(close, 900);
      }
    }).catch(function () {
      btn.disabled = false;
      note.textContent = 'Koneksi bermasalah. Coba lagi.';
      note.className = 'addpanel-note is-error';
    });
  }

  function gantiToko(data) {
    var body = panel.querySelector('.addpanel-body');
    body.innerHTML =
      '<div class="addpanel-konfirm">' +
      '<p class="addpanel-konfirm-judul">Ganti toko?</p>' +
      '<p class="addpanel-konfirm-teks">Keranjangmu berisi produk dari ' +
      '<strong>' + esc(data.store_lama || 'toko lain') + '</strong>. ' +
      'Satu pesanan hanya bisa dari satu toko, karena ongkir dan jadwal ' +
      'pengirimannya berbeda.</p>' +
      '<p class="addpanel-konfirm-teks">Kosongkan keranjang dan mulai dari ' +
      '<strong>' + esc(data.store_baru || 'toko ini') + '</strong>?</p>' +
      '<div class="addpanel-actions">' +
      '<button type="button" class="btn btn-black-hover-outline" data-batal>Batal</button>' +
      '<button type="button" class="btn btn-primary" data-kosongkan>Ya, kosongkan</button>' +
      '</div>' +
      '</div>';

    body.querySelector('[data-batal]').addEventListener('click', close);
    body.querySelector('[data-kosongkan]').addEventListener('click', function () {
      this.disabled = true;
      post(window.SHOP_URLS.clear, {}).then(function () {
        render();
        submit('add', panel.querySelector('[data-act="add"]'));
      });
    });
  }

  /* ------------------------------------------------------------ mulai */

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.btn-add');
    if (!btn) { return; }
    e.preventDefault();

    if (!panel) {
      buildPanel();
      panel.querySelector('.addpanel-body').addEventListener('click', onBodyClick);
    }

    panel.querySelector('.addpanel-body').innerHTML =
      '<p class="addpanel-loading">Memuat pilihan...</p>';
    open();

    post(window.SHOP_URLS.options + '/' + btn.dataset.id, {})
      .then(function (res) {
        if (!res.ok || !res.data.ok) {
          panel.querySelector('.addpanel-body').innerHTML =
            '<p class="addpanel-note is-error">' +
            (res.data.message || 'Produk tidak ditemukan.') + '</p>';
          return;
        }
        var d = res.data;
        state = {
          product: d.product,
          variants: d.variants || [],
          addons: d.addons || [],
          variantId: (d.variants && d.variants.length) ? +d.variants[0].id : 0,
          addonIds: [],
          qty: 1,
          sameDay: d.same_day,
          cutoff: d.cutoff_jam
        };
        render();
      })
      .catch(function () {
        panel.querySelector('.addpanel-body').innerHTML =
          '<p class="addpanel-note is-error">Koneksi bermasalah.</p>';
      });
  });

})();