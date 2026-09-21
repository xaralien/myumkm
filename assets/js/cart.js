/* =============================================================================
   cart.js - ubah jumlah & hapus baris di halaman keranjang
   Simpan di: assets/js/cart.js
   ========================================================================== */
(function () {
  'use strict';

  if (!window.CART_URLS) { return; }

  function rupiah(n) {
    return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function post(url, data) {
    var body = new FormData();
    Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
    if (window.CSRF) { body.append(window.CSRF.name, window.CSRF.hash); }

    return fetch(url, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) {
      return r.json().then(function (j) {
        if (window.CSRF && j.csrf_hash) {
          window.CSRF.name = j.csrf_name;
          window.CSRF.hash = j.csrf_hash;
        }
        var b = document.querySelector('.cart-badge');
        if (b && typeof j.cart_count !== 'undefined') {
          b.textContent = j.cart_count;
          b.style.display = j.cart_count > 0 ? '' : 'none';
        }
        return j;
      });
    });
  }

  function refresh(data) {
    var el = document.getElementById('sumSubtotal');
    if (el) { el.textContent = rupiah(data.subtotal); }

    // Baris terakhir dihapus -> muat ulang supaya tampil pesan keranjang kosong
    if (!data.items || !data.items.length) {
      window.location.reload();
      return;
    }
    data.items.forEach(function (it) {
      var line = document.querySelector('.cart-line[data-key="' + it.key + '"]');
      if (!line) { return; }
      line.querySelector('.cart-line-total').textContent = rupiah(it.line_total);
      line.querySelector('.qty-input').value = it.qty;
    });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-act]');
    if (!btn) { return; }
    var line = btn.closest('.cart-line');
    if (!line) { return; }

    var key = line.dataset.key;
    var act = btn.dataset.act;

    if (act === 'remove') {
      line.style.opacity = '.4';
      post(window.CART_URLS.remove, { key: key }).then(function (d) {
        line.remove();
        refresh(d);
      });
      return;
    }

    var input = line.querySelector('.qty-input');
    var qty   = parseInt(input.value, 10) || 1;
    qty = (act === 'plus') ? Math.min(20, qty + 1) : Math.max(1, qty - 1);
    input.value = qty;
    post(window.CART_URLS.update, { key: key, qty: qty }).then(refresh);
  });

  // Ketik langsung di kolom jumlah
  document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('qty-input')) { return; }
    var line = e.target.closest('.cart-line');
    var qty  = Math.max(1, Math.min(20, parseInt(e.target.value, 10) || 1));
    e.target.value = qty;
    post(window.CART_URLS.update, { key: line.dataset.key, qty: qty }).then(refresh);
  });

})();
