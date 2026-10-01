<?php
/* =============================================================================
   Bagian ulasan versi HP (halaman produk).

   Diperlukan: $p, $ulasan_ringkas, $ulasan_terbaru, $ulasan_media, $ulasan_filter
   ========================================================================== */
$r = $ulasan_ringkas;
$url_semua = site_url('ulasan/produk/' . $p['store_slug'] . '/' . $p['slug']);

$dasar = site_url('produk/' . $p['store_slug'] . '/' . $p['slug']);
$url_f = function (array $ganti) use ($dasar, $ulasan_filter) {
    $q = array_filter(array(
        'ub'     => $ulasan_filter['rating'] ?: NULL,
        'umedia' => $ulasan_filter['media'] ? '1' : NULL,
    ));
    foreach ($ganti as $k => $v) {
        if ($v === NULL) { unset($q[$k]); } else { $q[$k] = $v; }
    }
    return $dasar . ($q ? '?' . http_build_query($q) : '') . '#ulasan';
};
?>

<section class="mb-ulasan" id="ulasan">

  <div class="mb-ulasan-kepala">
    <h2>Ulasan pembeli</h2>
    <a href="<?= $url_semua ?>">Lihat semua</a>
  </div>

  <?php if ((int) $r['total'] === 0): ?>
    <p class="mb-ulasan-kosong">
      Belum ada ulasan. Jadilah yang pertama setelah pesananmu sampai.
    </p>
  <?php else: ?>

    <p class="mb-ulasan-nilai">
      <svg viewBox="0 0 24 24" width="17" height="17" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
      <strong><?= number_format($r['rata'], 1, ',', '.') ?></strong>
      <span>dari <?= (int) $r['total'] ?> rating &bull; <?= (int) ($r['berisi'] ?? $r['total']) ?> ulasan</span>
    </p>

    <!-- Chip filter, digeser mendatar. Di layar sempit, panel filter
         bersusun ke bawah seperti versi desktop akan memakan satu layar
         penuh sebelum ulasan pertamanya terlihat. -->
    <div class="mb-ulasan-chip">
      <a href="<?= html_escape($url_f(array('ub' => NULL, 'umedia' => NULL))) ?>"
         class="<?= (! $ulasan_filter['rating'] && ! $ulasan_filter['media']) ? 'is-aktif' : '' ?>">
        Semua
      </a>

      <?php if ((int) $r['media'] > 0): ?>
        <a href="<?= html_escape($url_f(array('umedia' => $ulasan_filter['media'] ? NULL : '1'))) ?>"
           class="<?= $ulasan_filter['media'] ? 'is-aktif' : '' ?>">
          Dengan media (<?= (int) $r['media'] ?>)
        </a>
      <?php endif; ?>

      <?php foreach (array(5, 4, 3, 2, 1) as $b): ?>
        <?php if ((int) $r['sebaran'][$b] === 0) { continue; } ?>
        <a href="<?= html_escape($url_f(array('ub' => (int) $ulasan_filter['rating'] === $b ? NULL : $b))) ?>"
           class="<?= (int) $ulasan_filter['rating'] === $b ? 'is-aktif' : '' ?>">
          <?= $b ?> bintang (<?= (int) $r['sebaran'][$b] ?>)
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($ulasan_media && ! $ulasan_filter['rating'] && ! $ulasan_filter['media']): ?>
      <div class="mb-ulasan-media">
        <?php foreach (array_slice($ulasan_media, 0, 4) as $i => $m): ?>
          <?php
            $src = base_url('upload/ulasan/' . $m['berkas']);
            $sisa = ((int) $r['media']) - 4;
            $akhir = ($i === 3 && $sisa > 0);
          ?>
          <a class="mb-ulasan-petak" href="<?= $src ?>" data-penuh data-tipe="<?= $m['tipe'] ?>">
            <?php if ($m['tipe'] === 'video'): ?>
              <video src="<?= $src ?>" preload="metadata" muted playsinline></video>
              <span class="mb-ulasan-putar" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M8 5v14l11-7z"></path></svg>
              </span>
            <?php else: ?>
              <img src="<?= $src ?>" alt="" loading="lazy">
            <?php endif; ?>

            <?php if ($akhir): ?>
              <span class="mb-ulasan-lebih" aria-hidden="true">+<?= $sisa ?> lainnya</span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if (! $ulasan_terbaru): ?>
      <p class="mb-ulasan-kosong">Tidak ada ulasan yang cocok dengan filter.</p>
    <?php endif; ?>

    <?php foreach (array_slice($ulasan_terbaru, 0, 2) as $u): ?>
      <article class="mb-ulasan-item">
        <div class="mb-ulasan-orang">
          <span class="mb-ulasan-avatar" aria-hidden="true">
            <?= html_escape(strtoupper(mb_substr($u['nama'], 0, 1))) ?>
          </span>
          <?php
            $n = $u['nama'];
            $samar = mb_strlen($n) > 2
                ? mb_substr($n, 0, 1) . str_repeat('*', min(3, mb_strlen($n) - 2)) . mb_substr($n, -1)
                : $n;
          ?>
          <b><?= html_escape($samar) ?></b>
        </div>

        <p class="mb-ulasan-bintang">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <svg viewBox="0 0 24 24" width="13" height="13" class="<?= $i <= (int) $u['rating'] ? 'is-isi' : '' ?>" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
          <?php endfor; ?>
          <em><?= waktu_lalu($u['created_at']) ?></em>
        </p>

        <?php if ($u['variant_name']): ?>
          <p class="mb-ulasan-varian">Varian: <?= html_escape($u['variant_name']) ?></p>
        <?php endif; ?>

        <?php if ($u['isi']): ?>
          <!-- Teks dipotong tiga baris. Ulasan panjang yang ditampilkan utuh
               membuat ulasan kedua tidak pernah terlihat tanpa menggulir
               jauh. -->
          <div class="mb-ulasan-teks" data-potong>
            <p><?= nl2br(html_escape($u['isi'])) ?></p>
            <button type="button" class="mb-ulasan-lanjut">Selengkapnya</button>
          </div>
        <?php endif; ?>

        <?php if (! empty($u['media'])): ?>
          <div class="mb-ulasan-media is-kecil">
            <?php foreach (array_slice($u['media'], 0, 4) as $m): ?>
              <?php $src = base_url('upload/ulasan/' . $m['berkas']); ?>
              <a class="mb-ulasan-petak" href="<?= $src ?>" data-penuh data-tipe="<?= $m['tipe'] ?>">
                <?php if ($m['tipe'] === 'video'): ?>
                  <video src="<?= $src ?>" preload="metadata" muted playsinline></video>
                  <span class="mb-ulasan-putar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 5v14l11-7z"></path></svg>
                  </span>
                <?php else: ?>
                  <img src="<?= $src ?>" alt="" loading="lazy">
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($u['balasan']): ?>
          <div class="mb-ulasan-balasan">
            <strong>Balasan penjual</strong>
            <p><?= nl2br(html_escape($u['balasan'])) ?></p>
          </div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>

    <?php if ((int) $r['total'] > 2): ?>
      <a class="mb-ulasan-semua" href="<?= $url_semua ?>">
        Lihat semua <?= (int) $r['total'] ?> ulasan
      </a>
    <?php endif; ?>
  <?php endif; ?>
</section>
