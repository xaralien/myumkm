/* pwa.js - daftarkan service worker (lihat /sw.js). */
(function () {
  if (!('serviceWorker' in navigator) || !window.PWA_SW) { return; }
  window.addEventListener('load', function () {
    navigator.serviceWorker.register(window.PWA_SW).catch(function () {});
  });
})();
