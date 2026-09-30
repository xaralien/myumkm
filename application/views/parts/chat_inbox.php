<?php
/* =============================================================================
   Gelembung kotak masuk - melayang di semua halaman.

   Dipakai dua sisi:
     penjual  di panel  -> daftar percakapan dengan pembeli
     pembeli  di situs  -> daftar percakapan dengan toko

   Yang membedakan hanya alamat daftarnya; isi panel dan perilakunya sama.
   Sengaja TIDAK ditampilkan untuk tamu: mereka belum punya daftar pesanan,
   dan percakapannya diakses lewat gelembung khusus di halaman pesanan.
   ========================================================================== */

$CI =& get_instance();
$CI->load->library('auth_lib');

$akun = $CI->auth_lib->row();
if ( ! $akun) {
    return;
}

/* Tidak ditampilkan di halaman Pesan itu sendiri: isinya sama persis, dan
   di layar HP gelembungnya justru menutupi kolom balasan. */
$halaman_pesan = ($CI->uri->segment(1) === 'seller' && $CI->uri->segment(2) === 'pesan')
    || ($CI->uri->segment(1) === 'akun' && $CI->uri->segment(2) === 'pesan');

if ($halaman_pesan) {
    return;
}

$toko = $CI->auth_lib->store();

// Penjual melihat percakapan tokonya; selain itu, percakapan belanjanya.
$sisi = (! empty($sisi_paksa) && $sisi_paksa === 'seller') || ($toko && $CI->uri->segment(1) === 'seller')
    ? 'seller'
    : 'customer';

$url_daftar = ($sisi === 'seller') ? site_url('seller/inbox_json') : site_url('akun/inbox_json');
?>

<button type="button" class="ib-tombol" id="ibTombol" aria-expanded="false" aria-controls="ibPanel"
        aria-label="Buka pesan">
  <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
  </svg>
  <span class="ib-lencana" id="ibLencana" hidden>0</span>
</button>

<div class="ib-panel" id="ibPanel" role="dialog" aria-label="Pesan" hidden>

  <div class="ib-kepala">
    <button type="button" class="ib-balik" id="ibBalik" aria-label="Kembali ke daftar" hidden>
      <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"></path></svg>
    </button>
    <div class="ib-judul">
      <strong id="ibJudul">Pesan</strong>
      <em id="ibSub"><?= $sisi === 'seller' ? 'Percakapan dengan pembeli' : 'Percakapan dengan toko' ?></em>
    </div>
    <a href="#" class="ib-buka" id="ibBuka" hidden>Buka</a>
    <button type="button" class="ib-tutup" id="ibTutup" aria-label="Tutup">&times;</button>
  </div>

  <!-- Dua lapis dalam satu panel: daftar, lalu ruang percakapan. Di layar
       lebar pun tetap satu lapis - gelembung ini untuk membalas cepat;
       tampilan dua panel ada di halaman Pesan. -->
  <div class="ib-daftar" id="ibDaftar">
    <p class="ib-memuat">Memuat percakapan...</p>
  </div>

  <!-- Terlihat di layar lebar saat belum ada percakapan yang dipilih.
       Di layar sempit tidak pernah muncul: di sana daftarnya sendiri yang
       memenuhi panel. -->
  <div class="ib-sambut" id="ibSambut">
    <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
    <strong>Mari mulai percakapan</strong>
    <span>Pilih salah satu di sebelah kiri.</span>
  </div>

  <div class="ib-ruang" id="ibRuang" hidden>
    <div class="ib-pesan" id="ibPesan"></div>
    <form class="ib-form" id="ibForm">
      <input type="text" id="ibTeks" maxlength="1000" autocomplete="off"
             placeholder="Tulis pesan..." aria-label="Pesan">
      <button type="submit" aria-label="Kirim">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
      </button>
    </form>
    <p class="ib-info" id="ibInfo" aria-live="polite"></p>
  </div>
</div>

<script>
  window.CHAT_INBOX = {
    sisi:   <?= json_encode($sisi) ?>,
    daftar: <?= json_encode($url_daftar) ?>,
    gambar: <?= json_encode(base_url('upload/produk/')) ?>
  };
  window.CSRF = window.CSRF || {
    name: '<?= $this->security->get_csrf_token_name() ?>',
    hash: '<?= $this->security->get_csrf_hash() ?>'
  };
</script>
<script src="<?= aset('assets/js/chat-inbox.js') ?>"></script>
