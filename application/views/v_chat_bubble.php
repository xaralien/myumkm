<!-- application/views/v_chat_bubble.php
     Satu gelembung percakapan - dipakai bersama sisi pembeli dan penjual,
     supaya tampilannya tidak pelan-pelan berbeda. -->
<?php
  $milik_saya = ($m['pengirim'] === $sisi);
  $sistem     = ($m['pengirim'] === 'sistem');
?>
<?php if ($sistem): ?>
  <!-- Catatan otomatis: rata tengah tanpa gelembung, supaya tidak terbaca
       sebagai ucapan salah satu pihak. -->
  <div class="chat-sistem"><?= html_escape($m['isi']) ?></div>
<?php else: ?>
  <div class="chat-baris <?= $milik_saya ? 'is-saya' : '' ?>">
    <div class="chat-gelembung">
      <?php if ($m['image']): ?>
        <a href="<?= base_url('upload/produk/' . $m['image']) ?>" target="_blank" rel="noopener">
          <img src="<?= base_url('upload/produk/' . $m['image']) ?>" alt="Foto" class="chat-gambar" loading="lazy">
        </a>
      <?php endif; ?>
      <?php if ($m['isi']): ?>
        <p><?= nl2br(html_escape($m['isi'])) ?></p>
      <?php endif; ?>
      <time><?= date('d/m H:i', strtotime($m['created_at'])) ?></time>
    </div>
  </div>
<?php endif; ?>
