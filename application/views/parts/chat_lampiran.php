<?php
/* =============================================================================
   Lampiran produk di atas kolom tulis.

   Produk hanya terkirim bersama pesan pertama yang benar-benar ditulis -
   kalau pembeli berubah pikiran dan menutup halaman, tidak ada apa pun yang
   tertinggal di chat penjual.

   Diperlukan: $lampiran (baris produk, atau NULL).
   ========================================================================== */
if (empty($lampiran)) {
    return;
}
?>
<div class="chat-lampiran" id="chatLampiran" data-produk="<?= (int) $lampiran['id'] ?>">
  <span class="chat-lampiran-label">Menanyakan produk ini</span>

  <div class="chat-lampiran-isi">
    <img src="<?= base_url('upload/produk/' . $lampiran['image']) ?>" alt="" loading="lazy">
    <span>
      <strong><?= html_escape($lampiran['name']) ?></strong>
      <em><?= rupiah($lampiran['price']) ?></em>
    </span>

    <button type="button" class="chat-lampiran-batal" id="chatLampiranBatal"
            aria-label="Jangan sertakan produk ini">&times;</button>
  </div>
</div>
