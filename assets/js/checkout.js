(function () {
  'use strict';

  var T = window.TOKO;
  var dis     = document.getElementById('dis');
  var rOngkir = document.getElementById('rowOngkir');
  var rTotal  = document.getElementById('rowTotal');
  var info    = document.getElementById('jangkauanInfo');
  if (!T || !dis || !rOngkir || !rTotal) { return; }

  function rupiah(n) {
    return 'Rp ' + Math.round(n).toLocaleString('id-ID');
  }

  function pesan(teks, jenis) {
    if (!info) { return; }
    info.textContent = teks || '';
    info.className = 'hint' + (jenis ? ' is-' + jenis : '');
  }

  /* Ongkir bertingkat, mengikuti perhitungan yang sama di server
     (Cart_lib::shipping_fee). Angka di sini HANYA untuk ditampilkan -
     server menghitung ulang dan menolak wilayah di luar jangkauan. */
  function hitung() {
    var kec = parseInt(dis.value, 10) || 0;
    var reg = parseInt(document.getElementById('reg').value, 10) || 0;
    var prv = parseInt(document.getElementById('prov').value, 10) || 0;

    if (!kec) {
      rOngkir.textContent = 'Pilih wilayah dulu';
      rTotal.textContent  = rupiah(T.subtotal);
      pesan('');
      return;
    }

    var ongkir = null, ket = '';

    if (kec === T.toko_dis) {
      ongkir = T.kecamatan;
      ket = 'Satu kecamatan dengan toko.';
    } else if (reg === T.toko_reg) {
      ongkir = T.kota;
      ket = 'Masih dalam ' + T.nama_kota + '.';
    } else if (prv === T.toko_prov) {
      if (T.provinsi === null) {
        rOngkir.textContent = 'Tidak dilayani';
        rTotal.textContent  = rupiah(T.subtotal);
        pesan(T.nama_toko + ' hanya melayani ' + T.nama_kota + ' dan sekitarnya.', 'error');
        return;
      }
      ongkir = T.provinsi;
      ket = 'Luar ' + T.nama_kota + ', masih dalam ' + T.nama_prov + '.';
    } else {
      rOngkir.textContent = 'Tidak dilayani';
      rTotal.textContent  = rupiah(T.subtotal);
      pesan(T.nama_toko + ' tidak mengirim ke luar ' + T.nama_prov
            + '. Bunga segar tidak tahan perjalanan antarprovinsi.', 'error');
      return;
    }

    var gratis = T.gratis_min > 0 && T.subtotal >= T.gratis_min;
    if (gratis) { ongkir = 0; }

    rOngkir.textContent = gratis ? 'Gratis' : rupiah(ongkir);
    rTotal.textContent  = rupiah(T.subtotal + ongkir);
    pesan(ket + (gratis ? ' Gratis ongkir berlaku.' : ''), '');
  }

  dis.addEventListener('change', hitung);
  hitung();

  /* --- daftar metode Duitku dihapus: metode dipilih di dalam popup --- */

  /* --- penghitung karakter kartu ucapan --- */
  var msg = document.getElementById('card_message');
  var cnt = document.getElementById('msgCount');
  if (msg && cnt) {
    var upd = function () { cnt.textContent = msg.value.length; };
    msg.addEventListener('input', upd);
    upd();
  }

  /* --- "tanpa nama pengirim" mematikan kolom Dari --- */
  var anon = document.getElementById('isAnon');
  var from = document.getElementById('card_from');
  if (anon && from) {
    var sync = function () {
      from.disabled = anon.checked;
      if (anon.checked) { from.value = ''; }
    };
    anon.addEventListener('change', sync);
    sync();
  }

  /* --- cegah klik ganda yang bikin pesanan kembar --- */
  var form = document.getElementById('formCheckout');
  var btn  = document.getElementById('btnPlace');
  if (form && btn) {
    form.addEventListener('submit', function () {
      btn.disabled = true;
      btn.textContent = 'Memproses...';
    });
  }

})();