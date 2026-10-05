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
      <?php if (! empty($m['product_id']) && ! empty($m['produk_nama'])): ?>
        <!-- Kartu produk: rujukan, bukan salinan. Nama & harga dibaca dari
             tabel produk saat ditampilkan, jadi kalau penjual mengubah
             harganya, kartu di percakapan lama ikut menunjukkan yang
             berlaku sekarang - bukan angka usang yang bisa jadi sumber
             salah paham. -->
        <div class="chat-produk">
          <a class="chat-produk-isi" href="<?= site_url('produk/' . $m['produk_toko'] . '/' . $m['produk_slug']) ?>">
            <img src="<?= base_url('upload/produk/' . $m['produk_gambar']) ?>" alt="" loading="lazy">
            <span>
              <strong><?= html_escape($m['produk_nama']) ?></strong>
              <em><?= rupiah($m['produk_harga']) ?></em>
            </span>
          </a>

          <?php if (empty($sisi) || $sisi === 'customer'): ?>
            <!-- Tombol hanya untuk sisi pembeli. Penjual tidak membeli
                 produknya sendiri, dan tombol yang tidak berguna di sana
                 hanya menambah ramai. -->
            <div class="chat-produk-aksi">
              <button type="button" class="chat-produk-btn btn-add" data-id="<?= (int) $m['product_id'] ?>">
                + Keranjang
              </button>
              <a class="chat-produk-btn is-utama"
                 href="<?= site_url('produk/' . $m['produk_toko'] . '/' . $m['produk_slug']) ?>">
                Beli
              </a>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

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
