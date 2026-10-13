/* checkout.js - rincian checkout dihitung SERVER (checkout/hitung).
 *
 * Dulu ongkir dihitung ulang di sini dengan rumus salinan dari PHP. Sejak
 * keranjang bisa berisi beberapa toko, voucher, dan pre-order, rumusnya
 * terlalu banyak untuk dijaga tetap sama di dua tempat - jadi browser cukup
 * mengirim wilayah, tanggal kirim, dan voucher, lalu menampilkan jawabannya.
 */
(function () {
  'use strict';

  var C = window.CHECKOUT;
  var dis = document.getElementById('dis');
  var form = document.getElementById('formCheckout');
  if (!C || !dis || !form) { return; }

  var $ = function (id) { return document.getElementById(id); };
  var info = $('jangkauanInfo');

  function rupiah(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
  }

  function csrf() {
    var i = form.querySelector('input[name^="csrf"]');
    return i ? { name: i.name, value: i.value } : null;
  }

  function perbaruiCsrf(d) {
    if (!d || !d.csrf_name) { return; }
    document.querySelectorAll('input[name="' + d.csrf_name + '"]').forEach(function (i) { i.value = d.csrf_hash; });
  }

  function dataDasar() {
    var fd = new FormData();
    var t = csrf();
    if (t) { fd.append(t.name, t.value); }
    fd.append('district_id', dis.value || '0');
    document.querySelectorAll('[data-tanggal]').forEach(function (el) { fd.append(el.name, el.value); });
    return fd;
  }

  function kirim(url, fd) {
    return fetch(url, {
      method: 'POST', body: fd, credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); });
  }

  function setBaris(blok, kunci, nilai) {
    var baris = blok.querySelector('[data-baris="' + kunci + '"]');
    var sel = blok.querySelector('[data-k="' + kunci + '"]');
    if (sel) { sel.textContent = '−' + rupiah(nilai); }
    if (baris) { baris.hidden = !(nilai > 0); }
  }

  function tampilkan(d) {
    perbaruiCsrf(d);
    var adaWilayah = !!(parseInt(dis.value, 10));

    (d.kelompok || []).forEach(function (k) {
      var blok = document.querySelector('.ck-toko[data-toko="' + k.store_id + '"]');
      if (!blok) { return; }
      var o = blok.querySelector('[data-k="ongkir"]');
      if (!adaWilayah) {
        o.textContent = 'Pilih wilayah dulu';
      } else if (!k.ongkir_ok) {
        o.textContent = 'Tidak dilayani';
      } else {
        o.textContent = k.ongkir > 0 ? rupiah(k.ongkir) : 'Gratis';
      }
      setBaris(blok, 'diskon_toko', k.diskon_toko);
      setBaris(blok, 'diskon_platform', k.diskon_platform);
      setBaris(blok, 'subsidi_ongkir', k.subsidi_ongkir);

      var p = blok.querySelector('[data-k="pesan"]');
      var galat = (k.galat || []).filter(function (g) { return adaWilayah || g.indexOf('wilayah') === -1; });
      p.textContent = galat.length ? galat.join(' ') : (adaWilayah ? k.ongkir_pesan : '');
      p.className = 'hint ck-pesan' + (galat.length ? ' is-error' : '');
    });

    $('rowSubtotal').textContent = rupiah(d.subtotal);
    $('rowOngkir').textContent = adaWilayah ? (d.ongkir > 0 ? rupiah(d.ongkir) : 'Gratis') : 'Pilih wilayah dulu';
    $('barisPotongan').hidden = !(d.potongan > 0);
    $('rowPotongan').textContent = '−' + rupiah(d.potongan);
    $('rowTotal').textContent = rupiah(d.total);
    var dp = d.bayar_sekarang < d.total;
    $('barisBayarSekarang').hidden = !dp;
    $('rowBayarSekarang').textContent = rupiah(d.bayar_sekarang);

    gambarVoucher(d.voucher || {});
    if (info) {
      info.textContent = '';
    }
  }

  function gambarVoucher(v) {
    var ul = $('daftarVoucher');
    ul.innerHTML = '';
    Object.keys(v).forEach(function (kode) {
      var x = v[kode];
      var li = document.createElement('li');
      li.className = x.ok ? 'is-ok' : 'is-bad';
      var span = document.createElement('span');
      var b = document.createElement('strong'); b.textContent = kode;
      var em = document.createElement('em');
      em.textContent = x.ok ? (x.ket + (x.toko ? ' · ' + x.toko : '') + ' (−' + rupiah(x.potongan) + ')') : x.pesan;
      span.appendChild(b); span.appendChild(em);
      var btn = document.createElement('button');
      btn.type = 'button'; btn.setAttribute('data-lepas', kode); btn.setAttribute('aria-label', 'Lepas voucher');
      btn.textContent = '×';
      li.appendChild(span); li.appendChild(btn);
      ul.appendChild(li);
    });
  }

  var antre = null;
  function hitung() {
    clearTimeout(antre);
    antre = setTimeout(function () {
      kirim(C.hitung, dataDasar()).then(tampilkan).catch(function () {});
    }, 120);
  }

  function voucher(aksi, kode) {
    var fd = dataDasar();
    fd.append('aksi', aksi);
    fd.append('kode', kode);
    var pesan = $('pesanVoucher');
    pesan.textContent = aksi === 'pasang' ? 'Memeriksa...' : '';
    kirim(C.voucher, fd).then(function (d) {
      tampilkan(d);
      pesan.textContent = d.ok === false && d.pesan ? d.pesan : '';
      pesan.className = 'hint mb-0' + (d.ok === false ? ' is-error' : '');
      if (aksi === 'pasang' && d.ok !== false) { $('kodeVoucher').value = ''; }
    }).catch(function () { pesan.textContent = 'Gagal memeriksa voucher. Coba lagi.'; });
  }

  dis.addEventListener('change', hitung);
  document.querySelectorAll('[data-tanggal]').forEach(function (el) { el.addEventListener('change', hitung); });

  $('btnVoucher').addEventListener('click', function () {
    var k = $('kodeVoucher').value.trim();
    if (k) { voucher('pasang', k); }
  });
  $('kodeVoucher').addEventListener('keydown', function (e) {
    // Enter di kolom voucher memasang voucher, BUKAN mengirim pesanan.
    if (e.key === 'Enter') { e.preventDefault(); $('btnVoucher').click(); }
  });
  $('daftarVoucher').addEventListener('click', function (e) {
    var b = e.target.closest('[data-lepas]');
    if (b) { voucher('lepas', b.getAttribute('data-lepas')); }
  });

  hitung();

  /* --- cegah klik ganda yang bikin pesanan kembar --- */
  var btn = $('btnPlace');
  form.addEventListener('submit', function () {
    btn.disabled = true;
    btn.textContent = 'Memproses...';
  });
})();
