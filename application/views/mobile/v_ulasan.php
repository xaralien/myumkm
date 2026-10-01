<!-- application/views/mobile/v_ulasan.php - semua ulasan, versi HP -->
<?php
  $dasar = site_url('ulasan/produk/' . $p['store_slug'] . '/' . $p['slug']);

  $url = function (array $ganti = array()) use ($dasar, $tab, $filter) {
    $q = array_filter(array(
      'tab'     => $tab === 'toko' ? 'toko' : NULL,
      'bintang' => $filter['rating'] ?: NULL,
      'media'   => $filter['media'] ? '1' : NULL,
    ));
    foreach ($ganti as $k => $v) {
      if ($v === NULL) { unset($q[$k]); } else { $q[$k] = $v; }
    }
    return $dasar . ($q ? '?' . http_build_query($q) : '');
  };

  $samar = function ($n) {
    return mb_strlen($n) > 2
      ? mb_substr($n, 0, 1) . str_repeat('*', min(3, mb_strlen($n) - 2)) . mb_substr($n, -1)
      : $n;
  };
?>

<div class="mb-ul-atas">
  <a href="<?= site_url('produk/' . $p['store_slug'] . '/' . $p['slug']) ?>" class="mb-ul-balik" aria-label="Kembali ke produk">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"></path></svg>
  </a>
  <strong>Ulasan</strong>
</div>

<!-- Dua tab, sama seperti versi desktop: ulasan produk ini saja, atau
     seluruh produk toko. -->
<nav class="mb-ul-tab" aria-label="Jenis ulasan">
  <a href="<?= html_escape($url(array('tab' => NULL))) ?>" class="<?= $tab === 'produk' ? 'is-aktif' : '' ?>">Produk</a>
  <a href="<?= html_escape($url(array('tab' => 'toko'))) ?>" class="<?= $tab === 'toko' ? 'is-aktif' : '' ?>">Toko</a>
</nav>

<section class="mb-ul-ringkas">
  <div class="mb-ul-nilai">
    <svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
    <strong><?= number_format($ringkasan['rata'], 1, ',', '.') ?><span>/5</span></strong>
  </div>
  <p class="mb-ul-puas"><?= (int) $ringkasan['puas'] ?>% pembeli puas</p>
  <p class="mb-ul-jumlah">
    <?= (int) $ringkasan['total'] ?> rating &bull; <?= (int) ($ringkasan['berisi'] ?? $ringkasan['total']) ?> ulasan
  </p>

  <?php if ($ringkasan['total'] > 0): ?>
    <div class="mb-ul-sebaran">
      <?php foreach (array(5, 4, 3, 2, 1) as $b): ?>
        <?php
          $n = (int) $ringkasan['sebaran'][$b];
          $persen = $ringkasan['total'] ? ($n / $ringkasan['total']) * 100 : 0;
        ?>
        <div class="mb-ul-baris">
          <span><?= $b ?></span>
          <i><b style="width: <?= round($persen, 1) ?>%"></b></i>
          <em><?= $n ?></em>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Filter sebagai chip yang digeser: panel filter bersusun seperti versi
     desktop akan memakan satu layar penuh sebelum ulasan pertama terlihat. -->
<div class="mb-ulasan-chip is-halaman">
  <a href="<?= html_escape($url(array('bintang' => NULL, 'media' => NULL))) ?>"
     class="<?= (! $filter['rating'] && ! $filter['media']) ? 'is-aktif' : '' ?>">Semua</a>

  <?php if ((int) $ringkasan['media'] > 0): ?>
    <a href="<?= html_escape($url(array('media' => $filter['media'] ? NULL : '1'))) ?>"
       class="<?= $filter['media'] ? 'is-aktif' : '' ?>">Dengan media (<?= (int) $ringkasan['media'] ?>)</a>
  <?php endif; ?>

  <?php foreach (array(5, 4, 3, 2, 1) as $b): ?>
    <?php if ((int) $ringkasan['sebaran'][$b] === 0) { continue; } ?>
    <a href="<?= html_escape($url(array('bintang' => (int) $filter['rating'] === $b ? NULL : $b))) ?>"
       class="<?= (int) $filter['rating'] === $b ? 'is-aktif' : '' ?>">
      <?= $b ?> bintang (<?= (int) $ringkasan['sebaran'][$b] ?>)
    </a>
  <?php endforeach; ?>
</div>

<?php if ($media): ?>
  <section class="mb-ul-galeri">
    <h2>Foto &amp; video pembeli</h2>
    <div class="mb-ulasan-media is-geser">
      <?php foreach ($media as $m): ?>
        <?php $src = base_url('upload/ulasan/' . $m['berkas']); ?>
        <a class="mb-ulasan-petak" href="<?= $src ?>" data-penuh data-tipe="<?= $m['tipe'] ?>">
          <?php if ($m['tipe'] === 'video'): ?>
            <video src="<?= $src ?>" preload="metadata" muted playsinline></video>
            <span class="mb-ulasan-putar" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M8 5v14l11-7z"></path></svg>
            </span>
          <?php else: ?>
            <img src="<?= $src ?>" alt="" loading="lazy">
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<section class="mb-ul-daftar">
  <?php if (! $ulasan): ?>
    <p class="mb-ulasan-kosong">Tidak ada ulasan yang cocok.</p>
  <?php endif; ?>

  <?php foreach ($ulasan as $u): ?>
    <article class="mb-ulasan-item">
      <div class="mb-ulasan-orang">
        <span class="mb-ulasan-avatar" aria-hidden="true"><?= html_escape(strtoupper(mb_substr($u['nama'], 0, 1))) ?></span>
        <b><?= html_escape($samar($u['nama'])) ?></b>
      </div>

      <p class="mb-ulasan-bintang">
        <?php for ($i = 1; $i <= 5; $i++): ?>
          <svg viewBox="0 0 24 24" width="13" height="13" class="<?= $i <= (int) $u['rating'] ? 'is-isi' : '' ?>" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
        <?php endfor; ?>
        <em><?= waktu_lalu($u['created_at']) ?></em>
      </p>

      <?php if ($tab === 'toko' && $u['produk_nama']): ?>
        <a class="mb-ulasan-produk" href="<?= site_url('produk/' . $u['toko_slug'] . '/' . $u['produk_slug']) ?>">
          <?= html_escape($u['produk_nama']) ?>
        </a>
      <?php endif; ?>

      <?php if ($u['variant_name']): ?>
        <p class="mb-ulasan-varian">Varian: <?= html_escape($u['variant_name']) ?></p>
      <?php endif; ?>

      <?php if ($u['isi']): ?>
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

  <?php if ($hal_total > 1): ?>
    <nav class="mb-ul-halaman" aria-label="Halaman ulasan">
      <?php if ($hal > 1): ?>
        <a href="<?= html_escape($url(array('page' => $hal - 1))) ?>">Sebelumnya</a>
      <?php else: ?><span></span><?php endif; ?>
      <em><?= (int) $hal ?> / <?= (int) $hal_total ?></em>
      <?php if ($hal < $hal_total): ?>
        <a href="<?= html_escape($url(array('page' => $hal + 1))) ?>">Berikutnya</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</section>

<script src="<?= aset('assets/js/media-penuh.js') ?>"></script>
<script src="<?= aset('assets/js/ulasan-potong.js') ?>"></script>
