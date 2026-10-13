<!-- application/views/v_otp.php -->
<div class="hero hero-page">
  <div class="container"><div class="row"><div class="col-lg-12">
    <div class="intro-excerpt text-center"><h1>Masuk dengan WhatsApp</h1></div>
  </div></div></div>
</div>

<div class="untree_co-section before-footer-section">
  <div class="container"><div class="row justify-content-center"><div class="col-md-7 col-lg-5">
    <?php $this->load->view('parts/auth_pesan'); ?>
    <?php $q = $next ? '?next=' . rawurlencode($next) : ''; ?>

    <div class="form-card">
      <?php if ($tahap === 'nomor'): ?>
        <p class="hint mb-4">Tidak perlu kata sandi. Kami kirim kode 6 angka ke WhatsApp-mu.</p>
        <?= form_open('auth/otp' . $q) ?>
          <input type="hidden" name="aksi" value="kirim">
          <div class="mb-4">
            <label class="form-label" for="phone">Nomor WhatsApp</label>
            <input type="tel" id="phone" name="phone" class="form-control form-control-lg" inputmode="numeric"
                   placeholder="081234567890" required autofocus autocomplete="tel">
          </div>
          <button type="submit" class="btn btn-primary w-100 btn-besar">Kirim kode</button>
        <?= form_close() ?>
      <?php else: ?>
        <p class="hint mb-4">Masukkan kode yang dikirim ke <strong><?= html_escape($nomor) ?></strong>. Berlaku 5 menit.</p>
        <?= form_open('auth/otp' . $q) ?>
          <input type="hidden" name="aksi" value="cek">
          <div class="mb-4">
            <label class="form-label" for="kode">Kode 6 angka</label>
            <input type="text" id="kode" name="kode" class="form-control form-control-lg otp-kotak" inputmode="numeric"
                   pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus>
          </div>
          <button type="submit" class="btn btn-primary w-100 btn-besar">Masuk</button>
        <?= form_close() ?>
        <p class="akun-alih">
          <a href="<?= site_url('auth/otp?ulang=1' . ($next ? '&next=' . rawurlencode($next) : '')) ?>">Ganti nomor / kirim ulang</a>
        </p>
      <?php endif; ?>

      <p class="akun-alih"><a href="<?= site_url('auth/login' . $q) ?>">Masuk dengan email &amp; kata sandi</a></p>
    </div>
  </div></div></div>
</div>
