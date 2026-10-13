<?php
/* -----------------------------------------------------------------------------
   parts/verif_spanduk.php - pengingat "email belum diverifikasi"

   Dipanggil dari halaman akun:
       <?php $this->load->view('parts/verif_spanduk'); ?>

   Tidak menampilkan apa pun kalau tidak perlu - jadi aman dipanggil di
   halaman mana saja tanpa pengecekan di pemanggilnya.
   -------------------------------------------------------------------------- */

$CI_v =& get_instance();
$CI_v->load->library('auth_lib');

// Dimuat di sini supaya bagian ini bisa ditempel di halaman mana saja -
// termasuk yang controllernya belum memuat helper form.
$CI_v->load->helper('form');

// terverifikasi() mengembalikan NULL kalau tidak ada yang login, dan TRUE
// kalau migrasi 25 belum dijalankan. Keduanya berarti tidak ada yang perlu
// ditampilkan.
if ($CI_v->auth_lib->terverifikasi() !== FALSE) {
    return;
}

$surel = $CI_v->auth_lib->row()['email'] ?? '';
?>
<div class="verif-spanduk" role="status">
  <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
       stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M4 4h16v16H4z"></path>
    <path d="M4 7l8 6 8-6"></path>
  </svg>

  <div class="verif-spanduk-isi">
    <p class="verif-spanduk-judul">Email belum diverifikasi</p>
    <p class="verif-spanduk-teks">
      Belanja tetap bisa seperti biasa. Verifikasi diperlukan kalau nanti kamu
      mau membuka toko, dan supaya kami bisa mengirim tautan sandi baru kalau
      kamu lupa.
      <?php if ($surel): ?>
        Tautannya dikirim ke <strong><?= html_escape($surel) ?></strong> - cek juga folder spam.
      <?php endif; ?>
    </p>

    <!-- form_open, bukan tautan biasa: mengirim ulang email adalah aksi yang
         mengubah keadaan, dan tautan GET bisa terpicu sendiri oleh pemuat
         pratinjau atau tombol kembali. -->
    <?= form_open('auth/kirim_verifikasi') ?>
      <button type="submit" class="verif-spanduk-kirim">Kirim ulang email verifikasi</button>
    <?= form_close() ?>
  </div>
</div>
