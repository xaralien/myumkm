/* template-chat.js - tombol balasan cepat di atas kolom chat penjual.
   Daftar template datang dari window.CHAT_TEMPLATE (diisi footer panel).
   Menekan tombol MENGISI kolom, tidak langsung mengirim - penjual masih
   bisa menambah nama pembeli atau mengoreksi sebelum menekan kirim. */
(function () {
  'use strict';
  var T = window.CHAT_TEMPLATE;
  if (!T || !T.length) { return; }

  function pasang(form, input) {
    if (!form || !input || form.previousElementSibling && form.previousElementSibling.classList.contains('tpl-chips')) { return; }
    var wadah = document.createElement('div');
    wadah.className = 'tpl-chips';
    T.forEach(function (t) {
      var b = document.createElement('button');
      b.type = 'button';
      b.textContent = t.judul;
      b.title = t.isi;
      b.addEventListener('click', function () {
        input.value = t.isi;
        input.focus();
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
      wadah.appendChild(b);
    });
    form.parentNode.insertBefore(wadah, form);
  }

  function semua() {
    pasang(document.getElementById('chatForm'), document.getElementById('chatIsi'));
    pasang(document.getElementById('ibForm'), document.getElementById('ibTeks'));
  }
  semua();
  document.addEventListener('DOMContentLoaded', semua);
})();
