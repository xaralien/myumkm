<!-- application/views/v_ulasan_tulis.php - formulir ulasan untuk satu pesanan -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Beri ulasan</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-lg-8">

    <?php if ($this->session->flashdata('sukses')): ?>
      <div class="alert-box alert-ok mb-4"><?= html_escape($this->session->flashdata('sukses')) ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert-box alert-error mb-4"><?= html_escape($this->session->flashdata('error')) ?></div>
    <?php endif; ?>

    <p class="hint mb-4">
      Pesanan <strong><?= html_escape($order['order_number']) ?></strong>.
      Ulasanmu membantu pembeli lain memutuskan, dan membantu penjual memperbaiki layanannya.
    </p>

    <?php if (! $items && ! $sudah): ?>
      <div class="form-card text-center">
        <p>Ulasan bisa ditulis setelah pesanan selesai dan barang sudah kamu terima.</p>
        <a href="<?= site_url('checkout/done/' . $order['order_number'] . '/' . $order['access_token']) ?>"
           class="btn btn-black-hover-outline">Lihat pesanan</a>
      </div>
    <?php endif; ?>

    <?php foreach ($items as $it): ?>
      <div class="form-card">
        <div class="ul-tulis-produk">
          <?php if ($it['image']): ?>
            <img src="<?= base_url('upload/produk/' . $it['image']) ?>" alt="" loading="lazy">
          <?php endif; ?>
          <div>
            <strong><?= html_escape($it['product_name']) ?></strong>
            <?php if ($it['variant_name']): ?>
              <em><?= html_escape($it['variant_name']) ?></em>
            <?php endif; ?>
          </div>
        </div>

        <?= form_open_multipart('ulasan/kirim/' . $order['order_number'] . '/' . $order['access_token']) ?>
        <input type="hidden" name="item_id" value="<?= (int) $it['id'] ?>">

        <!-- Bintang dari radio sungguhan, bukan tombol + JavaScript: tetap
             bisa dipakai dengan keyboard dan pembaca layar, dan tetap
             berfungsi kalau skripnya gagal dimuat. -->
        <fieldset class="ul-pilih-bintang">
          <legend>Seberapa puas kamu?</legend>
          <div class="ul-bintang-pilih">
            <?php for ($b = 5; $b >= 1; $b--): ?>
              <input type="radio" id="b<?= $it['id'] . $b ?>" name="rating" value="<?= $b ?>"
                     <?= $b === 5 ? 'checked' : '' ?> required>
              <label for="b<?= $it['id'] . $b ?>" title="<?= $b ?> bintang">
                <svg viewBox="0 0 24 24" width="30" height="30" aria-hidden="true"><path d="M12 2.5l2.9 5.9 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.4l1.2-6.5L2.5 9.3l6.6-.9z" fill="currentColor"></path></svg>
                <span class="visually-hidden"><?= $b ?> bintang</span>
              </label>
            <?php endfor; ?>
          </div>
        </fieldset>

        <div class="mb-3">
          <label class="form-label" for="isi<?= $it['id'] ?>">Ceritakan pengalamanmu</label>
          <textarea id="isi<?= $it['id'] ?>" name="isi" class="form-control" rows="4" maxlength="1500"
                    placeholder="Bagaimana kualitas barangnya, kecepatan kirim, dan pelayanan penjual?"></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label" for="media<?= $it['id'] ?>">Foto &amp; video <span class="hint">(opsional)</span></label>
          <input type="file" id="media<?= $it['id'] ?>" name="media[]" class="form-control"
                 accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" multiple>
          <p class="hint">
            Maksimal 5 foto (5 MB per foto) dan 1 video (30 MB). Foto dari pembeli
            adalah bagian yang paling sering dilihat calon pembeli lain.
          </p>
        </div>

        <button type="submit" class="btn btn-primary">Kirim ulasan</button>
        <?= form_close() ?>
      </div>
    <?php endforeach; ?>

    <?php if ($sudah): ?>
      <h2 class="ul-sub mt-4">Sudah kamu ulas</h2>
      <?php $this->load->view('parts/ulasan_daftar', array('ulasan' => array_map(function ($u) {
          $u['media'] = $u['media'] ?? array();
          return $u;
      }, $sudah))); ?>
    <?php endif; ?>

  </div></div></div>
</div>
