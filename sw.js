/* sw.js - service worker Sapa UMKM.
 *
 * Tujuannya dua: aplikasi bisa DIPASANG di layar utama HP, dan tetap
 * berguna saat sinyal lemah (umum di daerah):
 *   - aset (CSS, JS, gambar) disimpan dan dipakai ulang -> hemat kuota;
 *   - halaman publik (beranda, katalog, produk, toko) yang pernah dibuka
 *     tetap tampil saat offline;
 *   - halaman lain menampilkan halaman "Sinyal sedang hilang".
 *
 * TIDAK PERNAH menyimpan halaman akun, panel penjual/admin, checkout, atau
 * pembayaran - di HP yang dipakai bergantian, halaman itu bisa berisi data
 * pribadi orang lain.
 */
var VERSI = 'umkm-v1';
var OFFLINE = 'offline';
var INTI = [
  OFFLINE,
  'assets/css/bootstrap.min.css', 'assets/css/style.css', 'assets/css/order.css',
  'assets/css/tema-nusantara.css', 'assets/css/fitur.css', 'assets/css/mobile.css',
  'assets/images/pwa-192.png', 'assets/images/sapa_umkm_icon.svg'
];

// Alamat dasar = folder tempat sw.js berada (situs bisa di subfolder XAMPP).
var DASAR = self.location.pathname.replace(/sw\.js$/, '');

self.addEventListener('install', function (e) {
  e.waitUntil(caches.open(VERSI).then(function (c) {
    return Promise.all(INTI.map(function (u) { return c.add(DASAR + u).catch(function () {}); }));
  }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (e) {
  e.waitUntil(caches.keys().then(function (k) {
    return Promise.all(k.filter(function (n) { return n !== VERSI; }).map(function (n) { return caches.delete(n); }));
  }).then(function () { return self.clients.claim(); }));
});

function publik(path) {
  var p = path.slice(DASAR.length - 1);
  return p === '/' || /^\/(shop|produk|toko|promo|ulasan\/produk)(\/|$|\?)/.test(p);
}

self.addEventListener('fetch', function (e) {
  var req = e.request;
  if (req.method !== 'GET') { return; }
  var url = new URL(req.url);
  if (url.origin !== self.location.origin) { return; }

  // Aset statis: pakai salinan dulu (cepat & hemat), perbarui di belakang.
  if (/\/(assets|upload\/produk|upload\/banner)\//.test(url.pathname)) {
    e.respondWith(caches.open(VERSI).then(function (c) {
      return c.match(req).then(function (ada) {
        var ambil = fetch(req).then(function (r) { if (r.ok) { c.put(req, r.clone()); } return r; }).catch(function () { return ada; });
        return ada || ambil;
      });
    }));
    return;
  }

  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).then(function (r) {
      if (r.ok && publik(url.pathname)) {
        var salin = r.clone();
        caches.open(VERSI).then(function (c) { c.put(req, salin); });
      }
      return r;
    }).catch(function () {
      return caches.match(req).then(function (ada) { return ada || caches.match(DASAR + OFFLINE); });
    }));
  }
});
