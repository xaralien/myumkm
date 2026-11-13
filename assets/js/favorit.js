/* favorit.js - tombol Favorit & Ikuti toko tanpa memuat ulang halaman.
   Formulirnya tetap formulir biasa: kalau skrip ini gagal dimuat, tombol
   masih bekerja lewat pengalihan halaman. */
(function () {
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.matches || !f.matches('form[data-fav], form[data-ikuti]')) return;
    e.preventDefault();

    var btn = f.querySelector('button');
    btn.disabled = true;

    fetch(f.action, {
      method: 'POST',
      body: new FormData(f),
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        // Token CSRF berganti tiap permintaan - perbarui semua formulir.
        if (d.csrf_name) {
          document.querySelectorAll('input[name="' + d.csrf_name + '"]').forEach(function (i) { i.value = d.csrf_hash; });
        }
        if (!d.ok) { alert(d.pesan); return; }

        var ikuti = f.hasAttribute('data-ikuti');
        btn.classList.toggle('is-aktif', d.aktif);
        btn.setAttribute('aria-pressed', d.aktif ? 'true' : 'false');
        var t = f.querySelector('[data-fav-teks]');
        if (t) t.textContent = ikuti ? (d.aktif ? '✓ Mengikuti' : '+ Ikuti toko') : (d.aktif ? 'Tersimpan' : 'Favorit');
        var j = f.parentNode.querySelector('[data-fav-jumlah]');
        if (j && typeof d.jumlah !== 'undefined') j.textContent = d.jumlah;
      })
      .catch(function () { f.submit(); })
      .then(function () { btn.disabled = false; });
  });
})();

/* Salin tautan - dengan cadangan untuk browser lama tanpa Clipboard API. */
document.addEventListener('click', function (e) {
  var b = e.target.closest && e.target.closest('[data-salin]');
  if (!b) return;
  var url = b.getAttribute('data-salin');
  // Hanya label yang diganti (kalau ada), supaya ikon di tombol tidak hilang.
  var el = b.querySelector('[data-salin-teks]') || b;
  var selesai = function () { var t = el.textContent; el.textContent = 'Tersalin ✓'; setTimeout(function () { el.textContent = t; }, 1800); };
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(url).then(selesai);
  } else {
    var i = document.createElement('input'); i.value = url; document.body.appendChild(i);
    i.select(); try { document.execCommand('copy'); selesai(); } catch (x) {} document.body.removeChild(i);
  }
});

/* Menu Bagikan: tampilkan "Lainnya…" kalau HP punya menu bagikan bawaan,
   dan tutup menu saat mengeklik di luar atau memilih salah satu. */
(function () {
  if (navigator.share) {
    document.querySelectorAll('[data-bagikan-native]').forEach(function (b) { b.hidden = false; });
  }
  document.addEventListener('click', function (e) {
    var nat = e.target.closest && e.target.closest('[data-bagikan-native]');
    if (nat) {
      navigator.share({ title: nat.getAttribute('data-judul'), text: nat.getAttribute('data-judul'),
                        url: nat.getAttribute('data-url') }).catch(function () {});
    }
    document.querySelectorAll('details[data-bagikan][open]').forEach(function (d) {
      var diMenu = d.contains(e.target);
      var pilih = e.target.closest && e.target.closest('.bagikan-menu a, [data-bagikan-native]');
      if (!diMenu || pilih) { d.removeAttribute('open'); }
    });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('details[data-bagikan][open]').forEach(function (d) { d.removeAttribute('open'); });
    }
  });
})();
