<!-- application/views/mobile/v_toko.php - halaman toko versi HP -->
<?php
  $inisial = strtoupper(mb_substr(trim($toko['name']), 0, 1));

  /* Keduanya hanya dikirim controller pada tab produk. Diberi nilai
     bawaan di sini supaya tab ulasan tidak memicu peringatan - dan karena
     peringatan PHP ikut tercetak ke halaman, pengunjung yang melihatnya
     mengira situsnya rusak. */
  $kategori = isset($kategori) ? $kategori : '';
  $urut     = isset($urut) ? $urut : 'baru';

  $url = function (array $ganti = array()) use ($toko, $tab, $kategori, $urut) {
    $q = array_filter(array(
      'tab'      => $tab === 'ulasan' ? 'ulasan' : NULL,
      'kategori' => $kategori !== '' ? $kategori : NULL,
      'urut'     => $urut !== 'baru' ? $urut : NULL,
    ));
    foreach ($ganti as $k => $v) {
      if ($v === NULL || $v === '') { unset($q[$k]); } else { $q[$k] = $v; }
    }
    return site_url('toko/' . $toko['slug']) . ($q ? '?' . http_build_query($q) : '');
  };
?>

<section class="mb-tk-kepala">
  <div class="mb-tk-atas">
    <span class="mb-tk-avatar">
      <?php if ($toko['avatar']): ?>
        <img src="<?= base_url('upload/avatar/' . $toko['avatar']) ?>" alt="">
      <?php else: ?><?= html_escape($inisial) ?><?php endif; ?>
    </span>
    <div class="mb-tk-nama">
      <h1><?= html_escape($toko['name']) ?></h1>
      <p>
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
        <?= html_escape($toko['district_name']) ?>, <?= html_escape($toko['regency_name']) ?>
      </p>
    </div>
  </div>

  <!-- Tiga angka dalam satu baris: di layar sempit, menumpuknya ke bawah
       mendorong daftar produk keluar layar. -->
  <div class="mb-tk-angka">
    <div class="is-rating">
      <strong>
        <svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
        <?= $statistik['ulasan'] ? number_format($statistik['rating'], 1, ',', '.') : '-' ?>
      </strong>
      <span><?= (int) $statistik['ulasan'] ?> ulasan</span>
    </div>
    <div><strong><?= (int) $statistik['produk'] ?></strong><span>Produk</span></div>
    <div><strong><?= (int) $statistik['terjual'] ?></strong><span>Terjual</span></div>
  </div>

  <?= form_open('chat/toko/' . $toko['slug'], array('class' => 'mb-tk-chat')) ?>
    <button type="submit">
      <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
      Chat toko
    </button>
  <?= form_close() ?>
</section>

<nav class="mb-ul-tab" aria-label="Isi toko">
  <a href="<?= html_escape($url(array('tab' => NULL))) ?>" class="<?= $tab === 'produk' ? 'is-aktif' : '' ?>">
    Produk <em><?= (int) $statistik['produk'] ?></em>
  </a>
  <a href="<?= html_escape($url(array('tab' => 'ulasan', 'kategori' => NULL, 'urut' => NULL))) ?>"
     class="<?= $tab === 'ulasan' ? 'is-aktif' : '' ?>">
    Ulasan <em><?= (int) $statistik['ulasan'] ?></em>
  </a>
</nav>

<?php if ($tab === 'ulasan'): ?>

  <?php if ((int) $ringkasan['total'] === 0): ?>
    <p class="mb-ulasan-kosong" style="padding: 24px var(--mb-tepi);">Toko ini belum punya ulasan.</p>
  <?php else: ?>
    <section class="mb-ul-ringkas">
      <div class="mb-ul-nilai">
        <svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
        <strong><?= number_format($ringkasan['rata'], 1, ',', '.') ?><span>/5</span></strong>
      </div>
      <p class="mb-ul-puas"><?= (int) $ringkasan['puas'] ?>% pembeli puas</p>
      <p class="mb-ul-jumlah">
        <?= (int) $ringkasan['total'] ?> rating &bull; <?= (int) ($ringkasan['berisi'] ?? $ringkasan['total']) ?> ulasan
      </p>
    </section>

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
      <?php foreach ($ulasan as $u): ?>
        <article class="mb-ulasan-item">
          <div class="mb-ulasan-orang">
            <span class="mb-ulasan-avatar" aria-hidden="true"><?= html_escape(strtoupper(mb_substr($u['nama'], 0, 1))) ?></span>
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
          <?php if ($u['produk_nama']): ?>
            <a class="mb-ulasan-produk" href="<?= site_url('produk/' . $u['toko_slug'] . '/' . $u['produk_slug']) ?>">
              <?= html_escape($u['produk_nama']) ?>
            </a>
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
                  <?php else: ?>
                    <img src="<?= $src ?>" alt="" loading="lazy">
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>

      <?php if ($hal_total > 1): ?>
        <nav class="mb-ul-halaman" aria-label="Halaman ulasan">
          <?php if ($hal > 1): ?>
            <a href="<?= html_escape($url(array('tab' => 'ulasan', 'page' => $hal - 1))) ?>">Sebelumnya</a>
          <?php else: ?><span></span><?php endif; ?>
          <em><?= (int) $hal ?> / <?= (int) $hal_total ?></em>
          <?php if ($hal < $hal_total): ?>
            <a href="<?= html_escape($url(array('tab' => 'ulasan', 'page' => $hal + 1))) ?>">Berikutnya</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    </section>
  <?php endif; ?>

<?php else: ?>

  <!-- Chip kategori digeser mendatar, sama seperti di beranda. -->
  <div class="mb-geser mb-chips">
    <a href="<?= html_escape($url(array('kategori' => NULL))) ?>"
       class="mb-chip <?= $kategori === '' ? 'is-aktif' : '' ?>">
      Semua <em><?= (int) $statistik['produk'] ?></em>
    </a>
    <?php foreach ($kategori_toko as $k): ?>
      <a href="<?= html_escape($url(array('kategori' => $k['slug']))) ?>"
         class="mb-chip <?= $kategori === $k['slug'] ? 'is-aktif' : '' ?>">
        <?= html_escape($k['name']) ?> <em><?= (int) $k['jml'] ?></em>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="mb-urut">
    <span><?= (int) $total ?> produk</span>
    <label>
      <span class="visually-hidden">Urutkan</span>
      <select onchange="location = this.value;" aria-label="Urutkan produk">
        <?php foreach (array('baru' => 'Terbaru', 'murah' => 'Termurah',
                             'mahal' => 'Tertinggi', 'rating' => 'Rating') as $k => $label): ?>
          <option value="<?= html_escape($url(array('urut' => $k === 'baru' ? NULL : $k))) ?>"
            <?= $urut === $k ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>

  <?php if (! $produk): ?>
    <p class="mb-kosong">
      Belum ada produk di kategori ini.<br>
      <a href="<?= html_escape($url(array('kategori' => NULL))) ?>" style="color: var(--wn-biru); font-weight: 600;">
        Lihat semua produk toko
      </a>
    </p>
  <?php else: ?>

    <!-- Kartu yang SAMA dengan katalog & beranda versi HP (.mb-kartu),
         bukan kartu desktop: ukuran, jarak, dan potongan teksnya sudah
         disesuaikan untuk dua kolom di layar sempit. -->
    <div class="mb-produk">
      <?php foreach ($produk as $p): ?>
        <a href="<?= site_url('produk/' . $toko['slug'] . '/' . $p['slug']) ?>" class="mb-kartu">
          <div class="mb-kartu-foto">
            <img src="<?= base_url('upload/produk/' . $p['image']) ?>"
                 alt="<?= html_escape($p['name']) ?>" loading="lazy">
          </div>
          <div class="mb-kartu-isi">
            <span class="mb-kartu-nama"><?= html_escape($p['name']) ?></span>

            <?php if ((int) $p['rating_count'] > 0): ?>
              <span class="mb-kartu-rating">
                <svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
                <?= number_format($p['rating_avg'], 1, ',', '.') ?>
                <em>(<?= (int) $p['rating_count'] ?>)</em>
              </span>
            <?php elseif ($p['category_name']): ?>
              <span class="mb-kartu-toko"><?= html_escape($p['category_name']) ?></span>
            <?php endif; ?>

            <span class="mb-kartu-harga"><?= rupiah($p['price']) ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($hal_total > 1): ?>
      <nav class="mb-ul-halaman" aria-label="Halaman produk">
        <?php if ($hal > 1): ?>
          <a href="<?= html_escape($url(array('page' => $hal - 1))) ?>">Sebelumnya</a>
        <?php else: ?><span></span><?php endif; ?>
        <em><?= (int) $hal ?> / <?= (int) $hal_total ?></em>
        <?php if ($hal < $hal_total): ?>
          <a href="<?= html_escape($url(array('page' => $hal + 1))) ?>">Berikutnya</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
<?php endif; ?>

<script src="<?= aset('assets/js/media-penuh.js') ?>"></script>
<script src="<?= aset('assets/js/ulasan-potong.js') ?>"></script>
