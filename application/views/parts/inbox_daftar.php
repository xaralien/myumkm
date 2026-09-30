<?php
/* =============================================================================
   Daftar percakapan - dipakai bersama penjual dan pembeli.

   Diperlukan: $daftar, $aktif, $url (fungsi: baris -> alamat halaman chat),
               $label (peta status pesanan)
   ========================================================================== */
?>
<?php if (! $daftar): ?>
  <p class="inbox-kosong">
    Belum ada percakapan. Percakapan muncul di sini untuk tiap pesanan yang
    sudah dibayar.
  </p>
<?php else: ?>
  <ul class="inbox-daftar">
    <?php foreach ($daftar as $d): ?>
      <?php
        $terpilih = $aktif && (int) $aktif['id'] === (int) $d['id'];

        // Cuplikan pesan terakhir. Pesan berupa foto tidak punya teks, jadi
        // ditandai sendiri - kalau tidak, barisnya terlihat kosong.
        $cuplik = trim((string) $d['pesan_akhir']);
        if ($cuplik === '') {
            $cuplik = $d['gambar_akhir'] ? 'Mengirim foto' : 'Belum ada pesan';
        }
        $waktu = $d['waktu_akhir'] ?: $d['created_at'];
      ?>
      <li>
        <a href="<?= $url($d) ?>" class="inbox-baris <?= $terpilih ? 'is-aktif' : '' ?>"
           <?= $terpilih ? 'aria-current="true"' : '' ?>>
          <span class="inbox-avatar" aria-hidden="true">
            <?= html_escape(strtoupper(mb_substr(trim((string) $d['lawan']), 0, 1))) ?>
          </span>

          <span class="inbox-isi">
            <span class="inbox-atas">
              <strong><?= html_escape($d['lawan'] ?: 'Tanpa nama') ?></strong>
              <em><?= date('d/m', strtotime($waktu)) ?></em>
            </span>
            <span class="inbox-cuplik"><?= html_escape($cuplik) ?></span>
            <span class="inbox-bawah">
              <span class="badge-status <?= $d['order_status'] === 'delivered' ? 'is-ok' : 'is-wait' ?>">
                <?= html_escape(isset($label[$d['order_status']]) ? $label[$d['order_status']] : $d['order_status']) ?>
              </span>
              <span class="inbox-nomor"><?= html_escape($d['order_number']) ?></span>
            </span>
          </span>

          <?php if ((int) $d['belum'] > 0): ?>
            <span class="inbox-lencana"><?= (int) $d['belum'] ?></span>
          <?php endif; ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
