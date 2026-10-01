<?php
/* =============================================================================
   Daftar ulasan.
   Diperlukan: $ulasan. Opsional: $tampilkan_produk (untuk tab ulasan toko).
   ========================================================================== */
?>
<?php if (! $ulasan): ?>
  <p class="ul-kosong">Belum ada ulasan yang cocok.</p>
<?php else: ?>
  <ul class="ul-daftar">
    <?php foreach ($ulasan as $u): ?>
      <li class="ul-item">

        <div class="ul-item-kepala">
          <span class="ul-avatar" aria-hidden="true">
            <?= html_escape(strtoupper(mb_substr($u['nama'], 0, 1))) ?>
          </span>
          <div>
            <?php $this->load->view('parts/ulasan_bintang', array('nilai' => (int) $u['rating'], 'ukuran' => 14)); ?>
            <p class="ul-nama">
              <?php
                /* Nama disamarkan sebagian: ulasan terbuka untuk umum, dan
                   nama lengkap pembeli tidak perlu ikut terbaca siapa pun. */
                $n = $u['nama'];
                $tampil = mb_strlen($n) > 2
                    ? mb_substr($n, 0, 1) . str_repeat('*', min(3, mb_strlen($n) - 2)) . mb_substr($n, -1)
                    : $n;
              ?>
              <?= html_escape($tampil) ?>
              <em><?= tgl_id(substr($u['created_at'], 0, 10)) ?></em>
            </p>
          </div>
        </div>

        <?php if (! empty($tampilkan_produk) && $u['produk_nama']): ?>
          <a class="ul-produk" href="<?= site_url('produk/' . $u['toko_slug'] . '/' . $u['produk_slug']) ?>">
            <?= html_escape($u['produk_nama']) ?>
          </a>
        <?php endif; ?>

        <?php if ($u['variant_name']): ?>
          <p class="ul-varian">Varian: <?= html_escape($u['variant_name']) ?></p>
        <?php endif; ?>

        <?php if ($u['isi']): ?>
          <p class="ul-isi"><?= nl2br(html_escape($u['isi'])) ?></p>
        <?php endif; ?>

        <?php if (! empty($u['media'])): ?>
          <?php $this->load->view('parts/ulasan_media', array(
              'media' => $u['media'], 'kelas' => 'kecil', 'maks' => 4,
          )); ?>
        <?php endif; ?>

        <?php if ($u['balasan']): ?>
          <div class="ul-balasan">
            <strong>Balasan penjual</strong>
            <p><?= nl2br(html_escape($u['balasan'])) ?></p>
          </div>
        <?php endif; ?>

      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
