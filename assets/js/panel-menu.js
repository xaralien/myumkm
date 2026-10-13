/* Menu panel berkelompok: hanya satu kelompok terbuka, dan menutup saat
   mengetuk di luar - perilaku yang diharapkan dari sebuah menu. */
(function () {
  var grup = document.querySelectorAll('.panel-grup');
  grup.forEach(function (g) {
    g.addEventListener('toggle', function () {
      if (g.open) { grup.forEach(function (x) { if (x !== g) { x.open = false; } }); }
    });
  });
  document.addEventListener('click', function (e) {
    grup.forEach(function (g) { if (g.open && !g.contains(e.target)) { g.open = false; } });
  });
})();
