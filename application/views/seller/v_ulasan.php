<!-- application/views/seller/v_ulasan.php -->
<?php
  $url = function ($s = NULL, $hal = NULL) {
    $q = array_filter(array('saring' => $s, 'page' => $hal));
    return site_url('seller/ulasan') . ($q ? '?' . http_build_query($q) : '');
  };
?>

<div class="panel-judul">
  <div>
    <h1>Ulasan</h1>
    <p class="hint">
      Membalas ulasan terbaca oleh semua calon pembeli, bukan hanya penulisnya.
      Ulasan buruk yang dibalas dengan baik sering lebih meyakinkan daripada
      ulasan bagus tanpa balasan.
    </p>
  </div>
  <a href="<?= site_url('toko/' . $toko_slug) ?>" class="btn btn-black-hover-outline">Lihat halaman toko</a>
</div>

<div class="ul-ringkas mb-4">
  <div class="ul-ringkas-nilai">
    <div class="ul-angka">
      <span class="ul-angka-ikon">
        <svg viewBox="0 0 24 24" width="36" height="36" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
      </span>
      <strong><?= number_format($ringkasan['rata'], 1, ',', '.') ?><span>/5</span></strong>
    </div>
    <p class="ul-jumlah"><?= (int) $ringkasan['total'] ?> ulasan</p>
  </div>

  <div class="ul-sebaran">
    <?php foreach (array(5, 4, 3, 2, 1) as $b): ?>
      <?php
        $n = (int) $ringkasan['sebaran'][$b];
        $persen = $ringkasan['total'] ? ($n / $ringkasan['total']) * 100 : 0;
      ?>
      <div class="ul-sebaran-baris is-mati">
        <span class="ul-sebaran-bintang">
          <svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
          <b><?= $b ?></b>
        </span>
        <span class="ul-sebaran-bar"><i style="width: <?= round($persen, 1) ?>%"></i></span>
        <span class="ul-sebaran-n"><?= $n ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<nav class="ul-tab" aria-label="Saringan ulasan">
  <a href="<?= html_escape($url()) ?>" class="<?= ! $saring ? 'is-aktif' : '' ?>">Semua</a>
  <a href="<?= html_escape($url('belum')) ?>" class="<?= $saring === 'belum' ? 'is-aktif' : '' ?>">
    Belum dibalas <?= $belum ? '<em>' . (int) $belum . '</em>' : '' ?>
  </a>
  <a href="<?= html_escape($url('rendah')) ?>" class="<?= $saring === 'rendah' ? 'is-aktif' : '' ?>">
    1&ndash;3 bintang
  </a>
</nav>

<?php if (! $ulasan): ?>
  <div class="empty-state">
    <p class="empty-title">
      <?= $saring === 'belum' ? 'Semua ulasan sudah dibalas' : 'Belum ada ulasan di sini' ?>
    </p>
  </div>
<?php else: ?>

  <ul class="ul-daftar">
    <?php foreach ($ulasan as $u): ?>
      <li class="ul-item">
        <div class="ul-item-kepala">
          <span class="ul-avatar" aria-hidden="true"><?= html_escape(strtoupper(mb_substr($u['nama'], 0, 1))) ?></span>
          <div>
            <?php $this->load->view('parts/ulasan_bintang', array('nilai' => (int) $u['rating'], 'ukuran' => 14)); ?>
            <p class="ul-nama">
              <?= html_escape($u['nama']) ?>
              <em><?= waktu_lalu($u['created_at']) ?></em>
            </p>
          </div>
        </div>

        <?php if ($u['produk_nama']): ?>
          <p class="ul-varian">
            <?= html_escape($u['produk_nama']) ?>
            <?php if ($u['variant_name']): ?> &middot; <?= html_escape($u['variant_name']) ?><?php endif; ?>
          </p>
        <?php endif; ?>

        <?php if ($u['isi']): ?>
          <p class="ul-isi"><?= nl2br(html_escape($u['isi'])) ?></p>
        <?php endif; ?>

        <?php if (! empty($u['media'])): ?>
          <?php $this->load->view('parts/ulasan_media', array('media' => $u['media'], 'kelas' => 'kecil')); ?>
        <?php endif; ?>

        <?php if ($u['balasan']): ?>
          <div class="ul-balasan">
            <strong>Balasanmu &middot; <?= waktu_lalu($u['balasan_at']) ?></strong>
            <p><?= nl2br(html_escape($u['balasan'])) ?></p>
          </div>
        <?php else: ?>
          <!-- Balasan tidak bisa diubah setelah terkirim: ulasan dan
               balasannya adalah catatan publik pada satu waktu, dan
               mengubahnya belakangan membuat percakapan tidak lagi
               mencerminkan apa yang sebenarnya terjadi. -->
          <?= form_open('seller/balas/' . (int) $u['id'], array('class' => 'ul-balas-form')) ?>
            <label class="visually-hidden" for="b<?= (int) $u['id'] ?>">Balasan untuk ulasan ini</label>
            <textarea id="b<?= (int) $u['id'] ?>" name="balasan" rows="2" maxlength="1000"
                      placeholder="Balas ulasan ini... (terbaca semua calon pembeli)" required></textarea>
            <button type="submit" class="btn btn-primary btn-sm">Kirim balasan</button>
          <?= form_close() ?>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>

  <?php if ($hal_total > 1): ?>
    <nav class="ul-halaman" aria-label="Halaman ulasan">
      <?php if ($hal > 1): ?>
        <a href="<?= html_escape($url($saring, $hal - 1)) ?>">Sebelumnya</a>
      <?php else: ?><span></span><?php endif; ?>
      <span>Halaman <?= (int) $hal ?> dari <?= (int) $hal_total ?></span>
      <?php if ($hal < $hal_total): ?>
        <a href="<?= html_escape($url($saring, $hal + 1)) ?>">Berikutnya</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>
