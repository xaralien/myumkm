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
  var selesai = function () { var t = b.textContent; b.textContent = 'Tersalin ✓'; setTimeout(function () { b.textContent = t; }, 1800); };
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(url).then(selesai);
  } else {
    var i = document.createElement('input'); i.value = url; document.body.appendChild(i);
    i.select(); try { document.execCommand('copy'); selesai(); } catch (x) {} document.body.removeChild(i);
  }
});
