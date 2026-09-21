/* =============================================================================
   filter.js - buka/tutup panel filter + kirim otomatis
   Simpan di: assets/js/filter.js

   Filter tetap bekerja tanpa JavaScript. Semua kontrol ada di dalam satu
   <form method="get"> dengan tombol "Terapkan", jadi kalau JS gagal dimuat
   halaman ini masih berfungsi penuh. Yang ditambahkan di sini cuma
   kenyamanan.
   ========================================================================== */
(function () {
  'use strict';

  var form   = document.getElementById('shopFilter');
  var panel  = document.getElementById('filterPanel');
  var toggle = document.getElementById('btnFilter');
  if (!form || !panel || !toggle) { return; }

  /* Panel dibuka otomatis kalau memang ada filter yang sedang aktif -
     supaya orang tahu kenapa hasilnya sedikit. */
  function set(open) {
    panel.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  set(!!window.FILTER_OPEN);

  toggle.addEventListener('click', function () {
    set(!panel.classList.contains('is-open'));
  });

  /* Urutan dan kategori langsung dikirim saat diklik - dua kali klik
     untuk satu pilihan terasa lambat. Kolom harga TIDAK ikut, karena
     orang masih mengetik angkanya. */
  form.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () {
      resetPage();
      form.submit();
    });
  });

  /* Ganti filter -> kembali ke halaman 1. Tanpa ini, orang di halaman 5
     yang mengubah kategori bisa mendarat di halaman kosong. */
  function resetPage() {
    var lama = form.querySelector('input[name="page"]');
    if (lama) { lama.remove(); }
  }
  form.addEventListener('submit', resetPage);

  /* Enter di kolom harga = terapkan */
  form.querySelectorAll('.filter-price input').forEach(function (el) {
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        resetPage();
        form.submit();
      }
    });
  });

})();
